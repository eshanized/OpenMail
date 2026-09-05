<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ComposerService;
use App\Services\MessageSanitizer;
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
    public array $attachments = [];
    public ?array $replyToMessage = null;
    public string $compositionId;
    public ?string $draftUid = null;
    public bool $sending = false;
    public bool $showCc = false;
    public bool $showBcc = false;

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
    }

    public function openComposer(array $params = []): void
    {
        $this->resetForm();
        $this->mode = $params['mode'] ?? 'compose';

        if (in_array($this->mode, ['reply', 'replyAll', 'forward']) && isset($params['message'])) {
            $this->replyToMessage = $params['message'];
            $this->populateFromMessage($params['message']);
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
                $this->dispatch('undo-send', ['delay' => config('openmail.undo_send_delay', 10)]);
            } else {
                $this->dispatch('toast', 'Message queued for sending', 'warning');
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
            'body' => $this->body,
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

    protected function resetForm(): void
    {
        $this->to = '';
        $this->cc = '';
        $this->bcc = '';
        $this->subject = '';
        $this->body = '';
        $this->attachments = [];
        $this->replyToMessage = null;
        $this->draftUid = null;
        $this->showCc = false;
        $this->showBcc = false;
        $this->compositionId = (string) Str::uuid();
        $this->sending = false;
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.mailbox.composer');
    }
}