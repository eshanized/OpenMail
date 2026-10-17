<?php

namespace App\Livewire\Mailbox;

use App\Models\EmailTemplate;
use App\Models\Signature;
use App\Services\AuditService;
use App\Services\ComposerService;
use App\Services\ContactAutocompleteService;
use App\Services\MessageSanitizer;
use App\Services\SignatureService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class Composer extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $mode = 'compose'; // compose, reply, replyAll, forward

    public string $to = '';

    public string $cc = '';

    public string $bcc = '';

    public string $subject = '';

    public string $body = '';

    public string $bodyHtml = '';

    public string $bodyText = '';

    public array $attachments = [];

    public ?array $replyToMessage = null;

    public string $compositionId;

    public ?string $draftUid = null;

    public bool $sending = false;

    public bool $showCc = false;

    public bool $showBcc = false;

    public $signatures;

    public ?array $defaultSignature = null;

    public array $templates = [];

    public string $autosaveStatus = 'idle'; // idle, saving, saved, error

    public ?string $lastSavedAt = null;

    public bool $showUndoToast = false;

    public ?int $pendingSendId = null;

    public int $undoSendDelay = 10;

    public bool $showSchedulePicker = false;

    public string $scheduledAt = '';

    protected $listeners = [
        'openComposer' => 'openComposer',
        'applyAiDraft' => 'applyAiDraft',
    ];

    protected function rules(): array
    {
        return [
            'to' => ['required', 'string', function ($attribute, $value, $fail) {
                $addresses = array_filter(array_map('trim', explode(',', $value)));
                if (empty($addresses)) {
                    $fail('The to field is required.');

                    return;
                }
                foreach ($addresses as $addr) {
                    $email = $addr;
                    if (preg_match('/<([^>]+)>$/', $addr, $matches)) {
                        $email = trim($matches[1]);
                    }
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail("The to field contains an invalid email address: {$addr}");
                    }
                }
            }],
            'cc' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (empty($value)) {
                    return;
                }
                $addresses = array_filter(array_map('trim', explode(',', $value)));
                foreach ($addresses as $addr) {
                    $email = $addr;
                    if (preg_match('/<([^>]+)>$/', $addr, $matches)) {
                        $email = trim($matches[1]);
                    }
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail("The cc field contains an invalid email address: {$addr}");
                    }
                }
            }],
            'bcc' => ['nullable', 'string', function ($attribute, $value, $fail) {
                if (empty($value)) {
                    return;
                }
                $addresses = array_filter(array_map('trim', explode(',', $value)));
                foreach ($addresses as $addr) {
                    $email = $addr;
                    if (preg_match('/<([^>]+)>$/', $addr, $matches)) {
                        $email = trim($matches[1]);
                    }
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $fail("The bcc field contains an invalid email address: {$addr}");
                    }
                }
            }],
            'subject' => 'required|max:255',
            'body' => ['required', function ($attribute, $value, $fail) {
                $hasBody = ! empty(trim(strip_tags($value)));
                $hasBodyHtml = ! empty(trim(strip_tags($this->bodyHtml)));
                $hasMedia = str_contains($this->bodyHtml, '<img') || str_contains($value, '<img');
                if (! $hasBody && ! $hasBodyHtml && ! $hasMedia) {
                    $fail('The body field is required.');
                }
            }],
            'attachments.*' => 'file|max:25600', // 25MB per file
        ];
    }

    public function mount(): void
    {
        $this->compositionId = (string) Str::uuid();
        $this->loadSignatures();
        $this->loadTemplates();
    }

    public function loadSignatures(): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $service = app(SignatureService::class);
        $this->signatures = $service->getAll($user);
        $default = $service->getDefault($user);
        $this->defaultSignature = $default ? [
            'id' => $default->id,
            'name' => $default->name,
            'content_html' => $default->content_html,
        ] : null;
    }

    public function loadTemplates(): void
    {
        $userId = auth()->id();
        if (! $userId) {
            $this->templates = [];

            return;
        }

        $this->templates = EmailTemplate::query()
            ->where('user_id', $userId)
            ->orderBy('name')
            ->get(['id', 'name', 'subject', 'body'])
            ->map(static fn (EmailTemplate $template): array => [
                'id' => (int) $template->id,
                'name' => $template->name,
                'subject' => $template->subject,
                'body' => $template->body,
            ])
            ->all();
    }

    public function applyTemplate(int $templateId): void
    {
        $template = EmailTemplate::query()
            ->where('user_id', auth()->id())
            ->find($templateId);

        if (! $template) {
            $this->dispatch('toast', 'Template not found.', 'warning');

            return;
        }

        $this->subject = $template->subject ?? $this->subject;
        $this->body = $template->body;
        $this->bodyText = $template->body;
        $this->bodyHtml = nl2br(e($template->body));

        $this->dispatch('load-template-content', html: $this->bodyHtml);
        $this->dispatch('toast', 'Template inserted. Review the message before sending.', 'success');
    }

    public function applyAiDraft(string $to, string $subject, string $body): void
    {
        if (! filter_var(trim($to), FILTER_VALIDATE_EMAIL)) {
            $this->dispatch('toast', 'AI reply could not be applied because the sender address is invalid.', 'warning');

            return;
        }

        $this->resetForm();
        $this->isOpen = true;
        $this->mode = 'compose';
        $this->to = trim($to);
        $this->subject = mb_substr(trim($subject), 0, 255);
        $this->body = trim($body);
        $this->bodyText = trim($body);
        $this->bodyHtml = nl2br(e(trim($body)));
        $this->dispatch('load-template-content', html: $this->bodyHtml);
    }

    public function openComposer(array $params = []): void
    {
        $this->resetForm();
        $this->loadSignatures();
        $this->loadTemplates();
        $this->mode = $params['mode'] ?? 'compose';

        if (in_array($this->mode, ['reply', 'replyAll', 'forward']) && isset($params['message'])) {
            $this->replyToMessage = $params['message'];
            $this->populateFromMessage($params['message']);
        }

        // Auto-insert default signature on new compose (not reply/forward)
        if ($this->mode === 'compose' && $this->defaultSignature && ! empty($this->defaultSignature['content_html'])) {
            $this->bodyHtml = '<br><br>'.$this->defaultSignature['content_html'];
            $this->body = strip_tags($this->bodyHtml);
        }

        $this->isOpen = true;
        $this->dispatch('show-modal', 'composer');
    }

    public function populateFromMessage(array $message): void
    {
        if ($this->mode === 'forward') {
            $this->to = '';
            $this->subject = 'Fwd: '.ltrim($message['subject'] ?? '', 'Re: Fwd: ');
        } else {
            $this->to = $message['from_email'] ?? '';
            $this->subject = 'Re: '.ltrim($message['subject'] ?? '', 'Re: Fwd: ');

            if ($this->mode === 'replyAll') {
                $ccAddresses = [];
                if (! empty($message['to_address'])) {
                    $ccAddresses[] = $message['to_address'];
                }
                if (! empty($message['cc_address'])) {
                    $ccAddresses[] = $message['cc_address'];
                }
                // Exclude own email from CC
                $ownEmail = auth()->user()->email ?? '';
                $this->cc = implode(', ', array_filter($ccAddresses, fn ($e) => strtolower($e) !== strtolower($ownEmail)));
            }
        }

        $this->body = $this->buildQuotedBody($message);
    }

    protected function buildQuotedBody(array $message): string
    {
        $sanitizer = app(MessageSanitizer::class);
        $date = $message['date_formatted'] ?? $message['date'] ?? '';
        $fromName = $message['from_name'] ?? '';
        $fromEmail = $message['from_email'] ?? '';
        $attribution = "On {$date}, {$fromName} <{$fromEmail}> wrote:\n\n";

        $quotedHtml = '';
        if (! empty($message['html_body'])) {
            $quotedHtml = $sanitizer->sanitizeHtml($message['html_body']);
        } elseif (! empty($message['text_body'])) {
            $quotedHtml = nl2br(e($message['text_body']));
        }

        if ($this->mode === 'forward') {
            $forwardHeader = "-------- Forwarded message --------\n";

            return $forwardHeader.$attribution."<blockquote>{$quotedHtml}</blockquote><br>";
        }

        return $attribution."<blockquote>{$quotedHtml}</blockquote><br>";
    }

    public function send(): void
    {
        // Synchronize body and bodyHtml before validation
        if (empty(trim($this->body)) && ! empty(trim($this->bodyHtml))) {
            $this->body = strip_tags($this->bodyHtml);
        }
        if (empty(trim($this->bodyHtml)) && ! empty(trim($this->body))) {
            $this->bodyHtml = $this->body;
        }

        $this->validate();

        $this->sending = true;

        try {
            $result = app(ComposerService::class)->sendMessage(
                auth()->id(),
                $this->getComposerData()
            );

            if ($result['queued']) {
                $this->dispatch('toast', 'Message scheduled', 'info');
                // Audit log: send scheduled
                $action = match ($this->mode) {
                    'reply' => 'reply',
                    'replyAll' => 'reply_all',
                    'forward' => 'forward',
                    default => 'send',
                };
                AuditService::send($action, [
                    'subject' => $this->subject,
                    'to' => $this->to,
                    'mode' => $this->mode,
                    'queued' => true,
                ]);

                // Show undo toast with real pending send ID and delay
                $this->showUndoToast = true;
                $this->pendingSendId = $result['pending_send_id'];
                $this->undoSendDelay = ($result['delay_seconds'] ?? 10) * 1000;
            } else {
                $this->dispatch('toast', 'Message sent', 'success');
                // Audit log: send action (SEC-05)
                $action = match ($this->mode) {
                    'reply' => 'reply',
                    'replyAll' => 'reply_all',
                    'forward' => 'forward',
                    default => 'send',
                };
                AuditService::send($action, [
                    'subject' => $this->subject,
                    'to' => $this->to,
                    'mode' => $this->mode,
                ]);
                $this->showUndoToast = false;
                $this->pendingSendId = null;
            }

            $this->dispatch('composer-sent');
            $this->resetForm(clearUndo: false);
            $this->isOpen = false;
        } catch (\Throwable $e) {
            Log::error('Mail send failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('toast', 'Failed to send message: '.$e->getMessage(), 'error');
        } finally {
            $this->sending = false;
        }
    }

    public function toggleSchedulePicker(): void
    {
        $this->showSchedulePicker = ! $this->showSchedulePicker;

        if ($this->showSchedulePicker && $this->scheduledAt === '') {
            $this->scheduledAt = CarbonImmutable::now(config('app.timezone'))
                ->addHour()
                ->startOfMinute()
                ->format('Y-m-d\\TH:i');
        }
    }

    public function scheduleSend(): void
    {
        if (empty(trim($this->body)) && ! empty(trim($this->bodyHtml))) {
            $this->body = strip_tags($this->bodyHtml);
        }

        if (empty(trim($this->bodyHtml)) && ! empty(trim($this->body))) {
            $this->bodyHtml = $this->body;
        }

        $this->validate();
        $this->validate([
            'scheduledAt' => ['required', 'date_format:Y-m-d\\TH:i'],
        ]);

        $scheduledAt = CarbonImmutable::createFromFormat(
            '!Y-m-d\\TH:i',
            $this->scheduledAt,
            config('app.timezone')
        );

        if (! $scheduledAt || $scheduledAt->lessThanOrEqualTo(CarbonImmutable::now(config('app.timezone'))->addMinute())) {
            $this->addError('scheduledAt', 'Choose a time at least one minute from now.');

            return;
        }

        if ($scheduledAt->greaterThan(CarbonImmutable::now(config('app.timezone'))->addYear())) {
            $this->addError('scheduledAt', 'Messages can be scheduled up to one year ahead.');

            return;
        }

        $this->sending = true;

        try {
            $result = app(ComposerService::class)->scheduleMessage(
                (int) auth()->id(),
                $this->getComposerData(),
                $scheduledAt,
                hash('sha256', 'openmail:schedule:'.auth()->id().':'.$this->compositionId)
            );

            AuditService::send('schedule_send', [
                'subject' => $this->subject,
                'to' => $this->to,
                'mode' => $this->mode,
                'scheduled_at' => $result['send_at'],
                'pending_send_id' => $result['pending_send_id'],
            ]);

            $this->dispatch('toast', 'Message scheduled successfully.', 'success');
            $this->dispatch('composer-sent');
            $this->dispatch('clear-draft-storage', ['compositionId' => $this->compositionId]);
            $this->showSchedulePicker = false;
            $this->resetForm();
            $this->isOpen = false;
        } catch (\Throwable $e) {
            Log::error('Scheduled email creation failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            $this->dispatch('toast', 'Could not schedule this message. Review the selected time and try again.', 'error');
        } finally {
            $this->sending = false;
        }
    }

    public function saveDraft(): void
    {
        try {
            $result = app(ComposerService::class)->saveDraft(
                auth()->id(),
                $this->getComposerData(),
                $this->draftUid
            );

            if ($result['success'] && $result['draft_uid']) {
                $this->draftUid = $result['draft_uid'];
            }

            $this->dispatch('toast', 'Draft saved', 'success');
        } catch (\Exception $e) {
            Log::error('Draft save failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->dispatch('toast', 'Failed to save draft. Please check your connection and try again.', 'error');
        }
    }

    public function discardDraft(): void
    {
        if ($this->draftUid) {
            try {
                app(ComposerService::class)->deleteDraft($this->draftUid);
            } catch (\Exception) {
                // Ignore errors
            }
        }

        $this->dispatch('clear-draft-storage', ['compositionId' => $this->compositionId]);
        $this->resetForm();
        $this->isOpen = false;
    }

    public function getComposerData(): array
    {
        $attachments = [];
        foreach ($this->attachments as $attachment) {
            $attachments[] = [
                'path' => $attachment->getRealPath(),
                'name' => $attachment->getClientOriginalName(),
                'mime' => $attachment->getMimeType(),
                'size' => $attachment->getSize(),
            ];
        }

        return [
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'body' => $this->bodyHtml ?: $this->body,
            'bodyText' => $this->bodyText ?: strip_tags($this->bodyHtml ?: $this->body),
            'attachments' => $attachments,
            'in_reply_to' => $this->replyToMessage['message_id'] ?? null,
            'references' => $this->buildReferences($this->replyToMessage),
        ];
    }

    protected function buildReferences(?array $message): ?string
    {
        if (! $message) {
            return null;
        }

        $refs = [];
        if (! empty($message['references'])) {
            $refs = array_merge($refs, explode(' ', $message['references']));
        }
        if (! empty($message['message_id'])) {
            $refs[] = $message['message_id'];
        }

        return empty($refs) ? null : implode(' ', $refs);
    }

    public function updatedAttachments(): void
    {
        $this->validateOnly('attachments.*');

        $allowedMimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain', 'text/csv',
            'application/zip', 'application/x-zip-compressed',
        ];

        foreach ($this->attachments as $attachment) {
            $mime = $attachment->getMimeType();
            if (! in_array($mime, $allowedMimes)) {
                $this->addError('attachments', "File type not allowed: {$attachment->getClientOriginalName()} ({$mime})");
                $this->attachments = [];

                return;
            }
        }

        $totalSize = collect($this->attachments)->sum('getSize');
        $maxTotalSize = config('openmail.max_total_attachment_size_mb', 50) * 1024 * 1024;

        if ($totalSize > $maxTotalSize) {
            $this->addError('attachments', 'Total attachments cannot exceed '.config('openmail.max_total_attachment_size_mb', 50).'MB');
            $this->attachments = [];
        }
    }

    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }

    public function updatedBodyHtml(): void
    {
        // Dispatch browser event for LocalStorage autosave (debounced client-side)
        $this->dispatch('autosave-draft', [
            'compositionId' => $this->compositionId,
            'to' => $this->to,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => $this->subject,
            'body' => $this->bodyHtml,
            'mode' => $this->mode,
            'replyToMessage' => $this->replyToMessage,
        ]);
    }

    public function saveDraftToLocalStorage(): void
    {
        // Dispatch browser event to save to LocalStorage
        $this->dispatch('save-to-localstorage', [
            'key' => 'openmail:draft:'.$this->compositionId,
            'data' => [
                'compositionId' => $this->compositionId,
                'to' => $this->to,
                'cc' => $this->cc,
                'bcc' => $this->bcc,
                'subject' => $this->subject,
                'body' => $this->bodyHtml,
                'mode' => $this->mode,
                'replyToMessage' => $this->replyToMessage,
                'savedAt' => now()->toISOString(),
            ],
        ]);
    }

    public function loadDraftFromLocalStorage(string $compositionId): void
    {
        $this->dispatch('load-from-localstorage', [
            'key' => 'openmail:draft:'.$compositionId,
        ]);
    }

    public function syncDraftToImap(): void
    {
        try {
            $result = app(ComposerService::class)->saveDraft(
                auth()->id(),
                $this->getComposerData(),
                $this->draftUid
            );

            if ($result['success'] && $result['draft_uid']) {
                $this->draftUid = $result['draft_uid'];
                $this->autosaveStatus = 'saved';
                $this->lastSavedAt = now()->toISOString();
            }
        } catch (\Exception $e) {
            $this->autosaveStatus = 'error';
        }
    }

    public function deleteDraftFromImap(): void
    {
        if ($this->draftUid) {
            try {
                app(ComposerService::class)->imapService->deleteFromDrafts($this->draftUid);
            } catch (\Exception) {
                // Ignore errors
            }
        }
    }

    public function undoSend(): void
    {
        if ($this->pendingSendId) {
            $result = app(ComposerService::class)->undoSend($this->pendingSendId, auth()->id());
            if ($result) {
                $this->dispatch('toast', 'Send cancelled. Message moved to Drafts.', 'success');
                $this->dispatch('draft-updated');
            } else {
                $this->dispatch('toast', 'Unable to cancel: message is already being sent.', 'warning');
            }
        }
        $this->showUndoToast = false;
        $this->pendingSendId = null;
    }

    public function searchContacts(string $query): array
    {
        if (strlen($query) < 1) {
            return [];
        }

        return app(ContactAutocompleteService::class)->search(auth()->id(), $query, 10)->toArray();
    }

    public function refreshContactCache(): void
    {
        $count = app(ContactAutocompleteService::class)->refreshCache(auth()->id());
        $this->dispatch('toast', "Contact cache refreshed ({$count} contacts)", 'success');
    }

    public function syncBodyFromEditor(string $html): void
    {
        $this->bodyHtml = $html;
        // Also keep body for backward compatibility with plain text fallback
        $this->body = strip_tags($html);
    }

    /**
     * Set a signature in the composer body.
     * Called by the signature-dropdown component.
     */
    public function setSignature(int $signatureId): void
    {
        $signature = Signature::where('id', $signatureId)
            ->where('user_id', auth()->id())
            ->first();

        if ($signature && $signature->content_html) {
            $this->bodyHtml = $this->bodyHtml.'<br><br>'.$signature->content_html;
            $this->body = strip_tags($this->bodyHtml);
        }
    }

    /**
     * Remove signature from composer body.
     * Called by the signature-dropdown component.
     */
    public function removeSignature(): void
    {
        // Remove the last signature block (after the last <br><br> separator)
        if (preg_match('/(<br><br>.*$)/s', $this->bodyHtml, $matches)) {
            $this->bodyHtml = substr($this->bodyHtml, 0, -strlen($matches[1]));
            $this->body = strip_tags($this->bodyHtml);
        }
    }

    protected function resetForm(bool $clearUndo = true): void
    {
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->body = '';
        $this->bodyHtml = '';
        $this->bodyText = '';
        $this->attachments = [];
        $this->replyToMessage = null;
        $this->draftUid = null;
        $this->showSchedulePicker = false;
        $this->scheduledAt = '';
        $this->showCc = false;
        $this->showBcc = false;
        $this->compositionId = (string) Str::uuid();
        $this->sending = false;
        $this->autosaveStatus = 'idle';
        $this->lastSavedAt = null;
        if ($clearUndo) {
            $this->showUndoToast = false;
            $this->pendingSendId = null;
        }
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mailbox.composer');
    }
}
