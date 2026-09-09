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
        $this->subject = (string) ($this->message->subject ?? '(No subject)');
        if (empty($this->subject)) {
            $this->subject = '(No subject)';
        }

        $fromObj = null;
        if (isset($this->message->from)) {
            if ($this->message->from instanceof \Webklex\PHPIMAP\Attribute) {
                $fromObj = $this->message->from->first();
            } elseif (is_iterable($this->message->from)) {
                $fromObj = collect($this->message->from)->first();
            } else {
                $fromObj = $this->message->from;
            }
        }

        $fromAddress = '';
        $fromName = '';
        if (is_object($fromObj)) {
            $fromAddress = $fromObj->mail ?? '';
            $fromName = $fromObj->personal ?? '';
        } elseif (is_string($fromObj)) {
            $fromAddress = $fromObj;
        }

        $this->fromAddress = (string) ($fromAddress ?: ($this->message->from_address ?? ''));
        $this->fromDisplay = (string) (!empty($this->message->from_name) ? $this->message->from_name : ($fromName ?: ($this->fromAddress ?: ($this->message->from_display ?? ''))));
        $this->toDisplay = $this->getToDisplayFromMessage($this->message);
        $this->formattedDate = (string) ($this->message->formatted_date ?? $this->message->date ?? '');
        $this->messageId = (string) ($this->message->message_id ?? '');
        $this->inReplyTo = (string) ($this->message->in_reply_to ?? '');
        $this->references = (string) ($this->message->references ?? '');
        $this->ccDisplay = $this->getCcDisplayFromMessage($this->message);
        $this->bccDisplay = $this->getBccDisplayFromMessage($this->message);

        if (isset($this->message->is_seen) && !($this->message->is_seen instanceof \Webklex\PHPIMAP\Attribute)) {
            $this->isSeen = (bool) $this->message->is_seen;
        } elseif (!($this->message instanceof \Mockery\LegacyMockInterface) && method_exists($this->message, 'hasFlag')) {
            $this->isSeen = $this->message->hasFlag('seen');
        } elseif (!($this->message instanceof \Mockery\LegacyMockInterface) && method_exists($this->message, 'getFlags')) {
            $flags = $this->message->getFlags();
            $this->isSeen = (bool) ($flags?->has('seen') ?? false);
        } else {
            $this->isSeen = false;
        }

        if (isset($this->message->is_flagged) && !($this->message->is_flagged instanceof \Webklex\PHPIMAP\Attribute)) {
            $this->isFlagged = (bool) $this->message->is_flagged;
        } elseif (!($this->message instanceof \Mockery\LegacyMockInterface) && method_exists($this->message, 'hasFlag')) {
            $this->isFlagged = $this->message->hasFlag('flagged');
        } elseif (!($this->message instanceof \Mockery\LegacyMockInterface) && method_exists($this->message, 'getFlags')) {
            $flags = $this->message->getFlags();
            $this->isFlagged = (bool) ($flags?->has('flagged') ?? false);
        } else {
            $this->isFlagged = false;
        }

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
        $rawTo = $message->to ?? null;
        if ($rawTo instanceof \Webklex\PHPIMAP\Attribute) {
            $rawTo = $rawTo->all();
        } elseif (!is_iterable($rawTo) && $rawTo !== null) {
            $rawTo = [$rawTo];
        }

        if (is_iterable($rawTo)) {
            foreach ($rawTo as $addr) {
                if (is_object($addr)) {
                    $addresses[] = $addr->personal ?: ($addr->mail ?? (isset($addr->mailbox, $addr->host) ? $addr->mailbox . '@' . $addr->host : (string) $addr));
                } elseif (is_array($addr)) {
                    $addresses[] = $addr['personal'] ?? ($addr['mail'] ?? (isset($addr['mailbox'], $addr['host']) ? $addr['mailbox'] . '@' . $addr['host'] : ''));
                } elseif (is_string($addr)) {
                    $addresses[] = $addr;
                }
            }
        }

        return implode(', ', array_filter($addresses));
    }

    protected function getCcDisplayFromMessage(object $message): string
    {
        $addresses = [];
        $rawCc = $message->cc ?? null;
        if ($rawCc instanceof \Webklex\PHPIMAP\Attribute) {
            $rawCc = $rawCc->all();
        } elseif (!is_iterable($rawCc) && $rawCc !== null) {
            $rawCc = [$rawCc];
        }

        if (is_iterable($rawCc)) {
            foreach ($rawCc as $addr) {
                if (is_object($addr)) {
                    $addresses[] = $addr->personal ?: ($addr->mail ?? (isset($addr->mailbox, $addr->host) ? $addr->mailbox . '@' . $addr->host : (string) $addr));
                } elseif (is_array($addr)) {
                    $addresses[] = $addr['personal'] ?? ($addr['mail'] ?? (isset($addr['mailbox'], $addr['host']) ? $addr['mailbox'] . '@' . $addr['host'] : ''));
                } elseif (is_string($addr)) {
                    $addresses[] = $addr;
                }
            }
        }

        return implode(', ', array_filter($addresses));
    }

    protected function getBccDisplayFromMessage(object $message): string
    {
        $addresses = [];
        $rawBcc = $message->bcc ?? null;
        if ($rawBcc instanceof \Webklex\PHPIMAP\Attribute) {
            $rawBcc = $rawBcc->all();
        } elseif (!is_iterable($rawBcc) && $rawBcc !== null) {
            $rawBcc = [$rawBcc];
        }

        if (is_iterable($rawBcc)) {
            foreach ($rawBcc as $addr) {
                if (is_object($addr)) {
                    $addresses[] = $addr->personal ?: ($addr->mail ?? (isset($addr->mailbox, $addr->host) ? $addr->mailbox . '@' . $addr->host : (string) $addr));
                } elseif (is_array($addr)) {
                    $addresses[] = $addr['personal'] ?? ($addr['mail'] ?? (isset($addr['mailbox'], $addr['host']) ? $addr['mailbox'] . '@' . $addr['host'] : ''));
                } elseif (is_string($addr)) {
                    $addresses[] = $addr;
                }
            }
        }

        return implode(', ', array_filter($addresses));
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
        $replyBehavior = auth()->user()->setting('reply_behavior', 'reply');
        $mode = $replyBehavior === 'reply_all' ? 'replyAll' : 'reply';
        
        $this->dispatch('openComposer', [
            'mode' => $mode,
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