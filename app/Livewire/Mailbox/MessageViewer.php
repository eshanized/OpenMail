<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;

class MessageViewer extends Component
{
    public string $folderPath = 'INBOX';
    public int $uid = 0;
    protected ?object $message = null;
    public ?string $sanitizedHtml = null;
    public ?string $textBody = null;
    public bool $showImages = false;
    public array $folders = [];
    
    // Extracted message data for view (serializable)
    public string $subject = '';
    public string $fromDisplay = '';
    public string $fromAddress = '';
    public string $toDisplay = '';
    public string $formattedDate = '';
    public string $messageId = '';
    public string $inReplyTo = '';
    public string $references = '';
    public string $ccDisplay = '';
    public string $bccDisplay = '';
    public bool $isSeen = false;
    public bool $isFlagged = false;
    public array $attachments = [];

    public function mount(string $folderPath, int $uid): void
    {
        $this->folderPath = $folderPath;
        $this->uid = $uid;
        $this->loadFolders();
        $this->loadMessage();
        $this->markAsRead();
    }

    public function loadFolders(): void
    {
        $this->folders = app(ImapMailboxService::class)->getCachedFolders();
    }

    public function loadMessage(): void
    {
        $service = app(ImapMailboxService::class);
        $this->message = $service->getMessageWithBody($this->folderPath, $this->uid);

        if (!$this->message) {
            $this->dispatch('message-not-found');
            return;
        }

        $sanitizer = app(MessageSanitizer::class);

        // Extract all needed data as simple types
        $this->subject = $this->message->subject ?? '(No subject)';
        $this->fromDisplay = $this->message->from_name ?? $this->message->from_address ?? '';
        $this->fromAddress = $this->message->from_address ?? '';
        $this->toDisplay = $this->getToDisplayFromMessage($this->message);
        $this->formattedDate = $this->message->formatted_date ?? $this->message->date ?? '';
        $this->messageId = $this->message->message_id ?? '';
        $this->inReplyTo = $this->message->in_reply_to ?? '';
        $this->references = $this->message->references ?? '';
        $this->ccDisplay = $this->getCcDisplayFromMessage($this->message);
        $this->bccDisplay = $this->getBccDisplayFromMessage($this->message);
        $this->isSeen = $this->message->is_seen ?? false;
        $this->isFlagged = $this->message->is_flagged ?? false;

        // Get HTML body and sanitize
        if (method_exists($this->message, 'getHTMLBody')) {
            $htmlBody = $this->message->getHTMLBody();
            if ($htmlBody) {
                $this->sanitizedHtml = $sanitizer->sanitizeHtml($htmlBody);
            }
        }

        // Get plain text body
        if (method_exists($this->message, 'getTextBody')) {
            $textBody = $this->message->getTextBody();
            if ($textBody) {
                $this->textBody = $textBody;
            }
        }

        // Extract attachments as simple array
        $this->attachments = $this->extractAttachments($this->message);
    }

    protected function getToDisplayFromMessage(object $message): string
    {
        $addresses = [];
        if (!empty($message->to)) {
            foreach ($message->to as $addr) {
                $addresses[] = $addr->personal ?? ($addr->mailbox . '@' . $addr->host);
            }
        }
        return implode(', ', $addresses);
    }

    protected function getCcDisplayFromMessage(object $message): string
    {
        $addresses = [];
        if (!empty($message->cc)) {
            foreach ($message->cc as $addr) {
                $addresses[] = $addr->personal ?? ($addr->mailbox . '@' . $addr->host);
            }
        }
        return implode(', ', $addresses);
    }

    protected function getBccDisplayFromMessage(object $message): string
    {
        $addresses = [];
        if (!empty($message->bcc)) {
            foreach ($message->bcc as $addr) {
                $addresses[] = $addr->personal ?? ($addr->mailbox . '@' . $addr->host);
            }
        }
        return implode(', ', $addresses);
    }

    protected function extractAttachments(object $message): array
    {
        $attachments = [];
        if (method_exists($message, 'getAttachments')) {
            $attachmentCollection = $message->getAttachments();
            if ($attachmentCollection) {
                // Handle both AttachmentCollection and plain arrays
                if (is_array($attachmentCollection)) {
                    foreach ($attachmentCollection as $index => $attachment) {
                        $attachments[] = [
                            'index' => $index,
                            'name' => $attachment->name ?? $attachment['name'] ?? 'attachment',
                            'size' => $attachment->size ?? $attachment['size'] ?? 0,
                            'contentType' => $attachment->contentType ?? $attachment['contentType'] ?? 'application/octet-stream',
                        ];
                    }
                } else {
                    foreach ($attachmentCollection as $index => $attachment) {
                        $attachments[] = [
                            'index' => $index,
                            'name' => $attachment->name ?? 'attachment',
                            'size' => $attachment->size ?? 0,
                            'contentType' => $attachment->contentType ?? 'application/octet-stream',
                        ];
                    }
                }
            }
        }
        return $attachments;
    }

    public function markAsRead(): void
    {
        if ($this->message && !$this->isSeen) {
            app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Seen', true);
        }
    }

    public function toggleImages(): void
    {
        $this->showImages = !$this->showImages;
        $this->loadMessage();
    }

    public function toggleRead(): void
    {
        if (!$this->message) {
            return;
        }
        $newValue = !$this->isSeen;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Seen', $newValue);
        $this->loadMessage();
    }

    public function toggleStar(): void
    {
        if (!$this->message) {
            return;
        }
        $newValue = !$this->isFlagged;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Flagged', $newValue);
        $this->loadMessage();
    }

    public function deleteMessage(): void
    {
        if (!$this->message) {
            return;
        }
        app(ImapMailboxService::class)->deleteMessages($this->folderPath, [$this->uid]);
        $this->redirect(route('mailbox', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function moveToFolder(string $destinationPath): void
    {
        if (!$this->message) {
            return;
        }
        app(ImapMailboxService::class)->moveMessages($this->folderPath, [$this->uid], $destinationPath);
        $this->redirect(route('mailbox', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function reply(): void
    {
        $this->dispatch('openComposer', [
            'mode' => 'reply',
            'message' => $this->getMessageDataForComposer(),
        ]);
    }

    public function replyAll(): void
    {
        $this->dispatch('openComposer', [
            'mode' => 'replyAll',
            'message' => $this->getMessageDataForComposer(),
        ]);
    }

    public function forward(): void
    {
        $this->dispatch('openComposer', [
            'mode' => 'forward',
            'message' => $this->getMessageDataForComposer(),
        ]);
    }

    protected function getMessageDataForComposer(): array
    {
        return [
            'message_id' => $this->messageId,
            'references' => $this->references,
            'from_email' => $this->fromAddress,
            'from_name' => $this->fromDisplay,
            'to_address' => $this->toDisplay,
            'cc_address' => $this->ccDisplay,
            'subject' => $this->subject,
            'date_formatted' => $this->formattedDate,
            'html_body' => $this->sanitizedHtml,
            'text_body' => $this->textBody,
        ];
    }

    public function render()
    {
        return view('livewire.mailbox.message-viewer', [
            'sanitizedHtml' => $this->sanitizedHtml,
            'textBody' => $this->textBody,
            'showImages' => $this->showImages,
            'folders' => $this->folders,
            'folderPath' => $this->folderPath,
            'uid' => $this->uid,
            // Message data
            'subject' => $this->subject,
            'fromDisplay' => $this->fromDisplay,
            'fromAddress' => $this->fromAddress,
            'toDisplay' => $this->toDisplay,
            'formattedDate' => $this->formattedDate,
            'messageId' => $this->messageId,
            'inReplyTo' => $this->inReplyTo,
            'references' => $this->references,
            'ccDisplay' => $this->ccDisplay,
            'bccDisplay' => $this->bccDisplay,
            'isSeen' => $this->isSeen,
            'isFlagged' => $this->isFlagged,
            'attachments' => $this->attachments,
        ]);
    }
}