<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ComposerService;
use App\Services\MessageSanitizer;
use App\Services\SignatureService;
use App\Services\ContactAutocompleteService;
use App\Services\AuditService;
use App\Models\Signature;
use Illuminate\Support\Str;

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
    public string $autosaveStatus = 'idle'; // idle, saving, saved, error
    public ?string $lastSavedAt = null;
    public bool $showUndoToast = false;
    public ?int $pendingSendId = null;
    public int $undoSendDelay = 10;

    protected $listeners = [
        'openComposer' => 'openComposer',
    ];

    protected $rules = [
        'to' => 'required|email',
        'subject' => 'required|max:255',
        'body' => 'required',
        'attachments.*' => 'file|max:25600', // 25MB per file
    ];

    public function mount(): void
    {
        $this->compositionId = (string) Str::uuid();
        $this->loadSignatures();
    }

    public function loadSignatures(): void
    {
        $user = auth()->user();
        if (!$user) return;

        $service = app(SignatureService::class);
        $this->signatures = $service->getAll($user);
        $default = $service->getDefault($user);
        $this->defaultSignature = $default ? [
            'id' => $default->id,
            'name' => $default->name,
            'content_html' => $default->content_html,
        ] : null;
    }

    public function openComposer(array $params = []): void
    {
        $this->resetForm();
        $this->loadSignatures();
        $this->mode = $params['mode'] ?? 'compose';

        if (in_array($this->mode, ['reply', 'replyAll', 'forward']) && isset($params['message'])) {
            $this->replyToMessage = $params['message'];
            $this->populateFromMessage($params['message']);
        }

        // Auto-insert default signature on new compose (not reply/forward)
        if ($this->mode === 'compose' && $this->defaultSignature && !empty($this->defaultSignature['content_html'])) {
            $this->bodyHtml = '<br><br>' . $this->defaultSignature['content_html'];
            $this->body = strip_tags($this->bodyHtml);
        }

        $this->isOpen = true;
        $this->dispatch('show-modal', 'composer');
    }

    public function populateFromMessage(array $message): void
    {
        if ($this->mode === 'forward') {
            $this->to = '';
            $this->subject = 'Fwd: ' . ltrim($message['subject'] ?? '', 'Re: Fwd: ');
        } else {
            $this->to = $message['from_email'] ?? '';
            $this->subject = 'Re: ' . ltrim($message['subject'] ?? '', 'Re: Fwd: ');

            if ($this->mode === 'replyAll') {
                $ccAddresses = [];
                if (!empty($message['to_address'])) {
                    $ccAddresses[] = $message['to_address'];
                }
                if (!empty($message['cc_address'])) {
                    $ccAddresses[] = $message['cc_address'];
                }
                // Exclude own email from CC
                $ownEmail = auth()->user()->email ?? '';
                $this->cc = implode(', ', array_filter($ccAddresses, fn($e) => strtolower($e) !== strtolower($ownEmail)));
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
        if (!empty($message['html_body'])) {
            $quotedHtml = $sanitizer->sanitizeHtml($message['html_body']);
        } elseif (!empty($message['text_body'])) {
            $quotedHtml = nl2br(e($message['text_body']));
        }

        if ($this->mode === 'forward') {
            $forwardHeader = "-------- Forwarded message --------\n";
            return $forwardHeader . $attribution . "<blockquote>{$quotedHtml}</blockquote><br>";
        }

        return $attribution . "<blockquote>{$quotedHtml}</blockquote><br>";
    }

    public function send(): void
    {
        $this->validate();

        $this->sending = true;

        try {
            $result = app(ComposerService::class)->sendMessage(
                auth()->id(),
                $this->getComposerData()
            );

            if ($result['success']) {
                $this->dispatch('toast', 'Message sent', 'success');
                // Audit log: send action (SEC-05)
                $action = match($this->mode) {
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
                // For successful immediate send, show undo toast with the configured delay
                $this->showUndoToast = true;
                $this->pendingSendId = null; // No pending send ID for immediate sends
                $this->undoSendDelay = config('openmail.undo_send_delay', 10) * 1000; // Convert to ms for toast
            } else {
                $this->dispatch('toast', 'Message queued for sending', 'warning');
                // Audit log: send queued (SEC-05)
                $action = match($this->mode) {
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
                // For queued sends, the pending_send_id is in the result
                $this->showUndoToast = true;
                $this->pendingSendId = $result['pending_send_id'] ?? null;
                $this->undoSendDelay = config('openmail.undo_send_delay', 10) * 1000;
            }

            $this->dispatch('composer-sent');
            $this->resetForm();
            $this->isOpen = false;
        } catch (\Exception $e) {
            $this->dispatch('toast', 'Failed to send: ' . $e->getMessage(), 'error');
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
            $this->dispatch('toast', 'Failed to save draft: ' . $e->getMessage(), 'error');
        }
    }

    public function discardDraft(): void
    {
        if ($this->draftUid) {
            try {
                app(ComposerService::class)->imapService->deleteFromDrafts($this->draftUid);
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
            'bodyText' => $this->bodyText,
            'attachments' => $attachments,
            'in_reply_to' => $this->replyToMessage['message_id'] ?? null,
            'references' => $this->buildReferences($this->replyToMessage),
        ];
    }

    protected function buildReferences(?array $message): ?string
    {
        if (!$message) {
            return null;
        }

        $refs = [];
        if (!empty($message['references'])) {
            $refs = array_merge($refs, explode(' ', $message['references']));
        }
        if (!empty($message['message_id'])) {
            $refs[] = $message['message_id'];
        }

        return empty($refs) ? null : implode(' ', $refs);
    }

    public function updatedAttachments(): void
    {
        $this->validate();

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
            if (!in_array($mime, $allowedMimes)) {
                $this->addError('attachments', "File type not allowed: {$attachment->getClientOriginalName()} ({$mime})");
                $this->attachments = [];
                return;
            }
        }

        $totalSize = collect($this->attachments)->sum('getSize');
        $maxTotalSize = config('openmail.max_total_attachment_size_mb', 50) * 1024 * 1024;

        if ($totalSize > $maxTotalSize) {
            $this->addError('attachments', "Total attachments cannot exceed " . config('openmail.max_total_attachment_size_mb', 50) . "MB");
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
            'key' => 'openmail:draft:' . $this->compositionId,
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
            'key' => 'openmail:draft:' . $compositionId,
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
                $this->dispatch('toast', 'Message moved to Drafts', 'success');
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
            $this->bodyHtml = $this->bodyHtml . '<br><br>' . $signature->content_html;
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

    protected function resetForm(): void
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
        $this->showCc = false;
        $this->showBcc = false;
        $this->compositionId = (string) Str::uuid();
        $this->sending = false;
        $this->autosaveStatus = 'idle';
        $this->lastSavedAt = null;
        $this->showUndoToast = false;
        $this->pendingSendId = null;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mailbox.composer');
    }
}