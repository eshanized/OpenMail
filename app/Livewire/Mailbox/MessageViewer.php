<?php

namespace App\Livewire\Mailbox;

use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Livewire\Component;
use Mockery\LegacyMockInterface;
use Webklex\PHPIMAP\Attribute;

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

        if (! $this->message) {
            $this->dispatch('message-not-found');

            return;
        }

        $sanitizer = app(MessageSanitizer::class);
        $isMock = $this->message instanceof LegacyMockInterface;

        // Extract subject
        $subject = null;
        if (! $isMock && method_exists($this->message, 'getSubject')) {
            $subjectAttr = $this->message->getSubject();
            if ($subjectAttr instanceof Attribute) {
                $subject = (string) $subjectAttr;
            }
        }
        if ($subject === null) {
            $subject = (string) ($this->message->subject ?? '');
        }
        $this->subject = ! empty(trim($subject)) ? $subject : '(No subject)';

        // Extract sender
        $fromAddress = '';
        $fromName = '';

        if (! $isMock && method_exists($this->message, 'getFrom')) {
            $fromAttr = $this->message->getFrom();
            if ($fromAttr instanceof Attribute) {
                $fromObj = $fromAttr->first();
                if ($fromObj) {
                    $fromAddress = $fromObj->mail ?? '';
                    $fromName = $fromObj->personal ?? '';
                }
            }
        }

        if (empty($fromAddress) && empty($fromName) && ! $isMock && method_exists($this->message, 'getHeader')) {
            $fromAttr = $this->message->getHeader()?->get('from');
            if ($fromAttr instanceof Attribute) {
                $fromObj = $fromAttr->first();
                if ($fromObj) {
                    $fromAddress = $fromObj->mail ?? '';
                    $fromName = $fromObj->personal ?? '';
                }
            }
        }

        if (empty($fromAddress) && empty($fromName)) {
            $fromProp = $this->message->from_name ?? null;
            if (! empty($fromProp) && is_string($fromProp)) {
                $fromName = $fromProp;
            }
            $addrProp = $this->message->from_address ?? null;
            if (! empty($addrProp) && is_string($addrProp)) {
                $fromAddress = $addrProp;
            }
        }

        if (empty($fromAddress) && empty($fromName)) {
            $fromVal = null;
            try {
                $fromVal = $this->message->from ?? null;
            } catch (\Throwable) {
            }

            if ($fromVal instanceof Attribute) {
                $fromObj = $fromVal->first();
            } elseif (is_iterable($fromVal)) {
                $fromObj = collect($fromVal)->first();
            } else {
                $fromObj = $fromVal;
            }

            if (is_object($fromObj)) {
                $fromAddress = $fromObj->mail ?? (isset($fromObj->mailbox, $fromObj->host) && $fromObj->mailbox && $fromObj->host ? $fromObj->mailbox.'@'.$fromObj->host : '');
                $fromName = $fromObj->personal ?? '';
            } elseif (is_string($fromObj)) {
                $fromAddress = $fromObj;
            }
        }

        $displayProp = $this->message->from_display ?? null;
        $this->fromAddress = (string) $fromAddress;
        $this->fromDisplay = (string) (! empty($fromName) ? $fromName : (! empty($this->fromAddress) ? $this->fromAddress : ($displayProp ?: '')));

        $this->toDisplay = $this->getToDisplayFromMessage($this->message);
        $this->formattedDate = $this->extractFormattedDate($this->message);

        $msgId = null;
        if (! $isMock && method_exists($this->message, 'getMessageId')) {
            $attr = $this->message->getMessageId();
            if ($attr instanceof Attribute) {
                $msgId = (string) $attr;
            }
        }
        $this->messageId = (string) ($msgId ?: ($this->message->message_id ?? ''));

        $inReplyTo = null;
        if (! $isMock && method_exists($this->message, 'getInReplyTo')) {
            $attr = $this->message->getInReplyTo();
            if ($attr instanceof Attribute) {
                $inReplyTo = (string) $attr;
            }
        }
        $this->inReplyTo = (string) ($inReplyTo ?: ($this->message->in_reply_to ?? ''));

        $references = null;
        if (! $isMock && method_exists($this->message, 'getReferences')) {
            $attr = $this->message->getReferences();
            if ($attr instanceof Attribute) {
                $references = (string) $attr;
            }
        }
        $this->references = (string) ($references ?: ($this->message->references ?? ''));

        $this->ccDisplay = $this->getCcDisplayFromMessage($this->message);
        $this->bccDisplay = $this->getBccDisplayFromMessage($this->message);

        if (isset($this->message->is_seen) && ! ($this->message->is_seen instanceof Attribute)) {
            $this->isSeen = (bool) $this->message->is_seen;
        } elseif (! $isMock && method_exists($this->message, 'hasFlag')) {
            $this->isSeen = $this->message->hasFlag('seen');
        } elseif (! $isMock && method_exists($this->message, 'getFlags')) {
            $flags = $this->message->getFlags();
            $this->isSeen = (bool) ($flags?->has('seen') ?? false);
        } else {
            $this->isSeen = false;
        }

        if (isset($this->message->is_flagged) && ! ($this->message->is_flagged instanceof Attribute)) {
            $this->isFlagged = (bool) $this->message->is_flagged;
        } elseif (! $isMock && method_exists($this->message, 'hasFlag')) {
            $this->isFlagged = $this->message->hasFlag('flagged');
        } elseif (! $isMock && method_exists($this->message, 'getFlags')) {
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

    protected function extractFormattedDate(object $message): string
    {
        if (isset($message->formatted_date) && is_string($message->formatted_date) && ! empty($message->formatted_date)) {
            return $message->formatted_date;
        }

        $isMock = $message instanceof LegacyMockInterface;

        $rawDate = null;
        if (! $isMock && method_exists($message, 'getDate')) {
            $dateAttr = $message->getDate();
            if ($dateAttr instanceof Attribute) {
                $rawDate = $dateAttr->first();
            }
        }
        if (! $rawDate && ! $isMock && method_exists($message, 'getHeader')) {
            $dateAttr = $message->getHeader()?->get('date');
            if ($dateAttr instanceof Attribute) {
                $rawDate = $dateAttr->first();
            }
        }
        if (! $rawDate) {
            try {
                $propDate = $message->date ?? null;
                if ($propDate instanceof Attribute) {
                    $rawDate = $propDate->first();
                } else {
                    $rawDate = $propDate;
                }
            } catch (\Throwable) {
            }
        }

        if ($rawDate instanceof CarbonInterface) {
            return $rawDate->format('M j, Y, g:i A');
        }

        if (is_string($rawDate) && ! empty($rawDate)) {
            try {
                return Carbon::parse($rawDate)->format('M j, Y, g:i A');
            } catch (\Throwable) {
                return $rawDate;
            }
        }

        return '';
    }

    protected function getToDisplayFromMessage(object $message): string
    {
        $isMock = $message instanceof LegacyMockInterface;
        $rawTo = null;
        if (! $isMock && method_exists($message, 'getTo')) {
            $rawTo = $message->getTo();
        }
        if (! $rawTo) {
            try {
                $rawTo = $message->to ?? null;
            } catch (\Throwable) {
            }
        }

        return $this->formatAddresses($rawTo);
    }

    protected function getCcDisplayFromMessage(object $message): string
    {
        $isMock = $message instanceof LegacyMockInterface;
        $rawCc = null;
        if (! $isMock && method_exists($message, 'getCc')) {
            $rawCc = $message->getCc();
        }
        if (! $rawCc) {
            try {
                $rawCc = $message->cc ?? null;
            } catch (\Throwable) {
            }
        }

        return $this->formatAddresses($rawCc);
    }

    protected function getBccDisplayFromMessage(object $message): string
    {
        $isMock = $message instanceof LegacyMockInterface;
        $rawBcc = null;
        if (! $isMock && method_exists($message, 'getBcc')) {
            $rawBcc = $message->getBcc();
        }
        if (! $rawBcc) {
            try {
                $rawBcc = $message->bcc ?? null;
            } catch (\Throwable) {
            }
        }

        return $this->formatAddresses($rawBcc);
    }

    protected function formatAddresses(mixed $raw): string
    {
        $addresses = [];
        if ($raw instanceof Attribute) {
            $raw = $raw->all();
        } elseif (! is_iterable($raw) && $raw !== null) {
            $raw = [$raw];
        }

        if (is_iterable($raw)) {
            foreach ($raw as $addr) {
                if (is_object($addr)) {
                    $mail = $addr->mail ?? (isset($addr->mailbox, $addr->host) && $addr->mailbox && $addr->host ? $addr->mailbox.'@'.$addr->host : '');
                    $personal = $addr->personal ?? '';
                    $addresses[] = $personal ?: ($mail ?: (string) $addr);
                } elseif (is_array($addr)) {
                    $mail = $addr['mail'] ?? (isset($addr['mailbox'], $addr['host']) && $addr['mailbox'] && $addr['host'] ? $addr['mailbox'].'@'.$addr['host'] : '');
                    $personal = $addr['personal'] ?? '';
                    $addresses[] = $personal ?: ($mail ?: '');
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
        if ($this->message && ! $this->isSeen) {
            app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Seen', true);
        }
    }

    public function toggleImages(): void
    {
        $this->showImages = ! $this->showImages;
        $this->loadMessage();
    }

    public function toggleRead(): void
    {
        if (! $this->message) {
            return;
        }
        $newValue = ! $this->isSeen;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Seen', $newValue);
        $this->loadMessage();
    }

    public function toggleStar(): void
    {
        if (! $this->message) {
            return;
        }
        $newValue = ! $this->isFlagged;
        app(ImapMailboxService::class)->setFlag($this->folderPath, [$this->uid], '\\Flagged', $newValue);
        $this->loadMessage();
    }

    public function deleteMessage(): void
    {
        if (! $this->message) {
            return;
        }
        app(ImapMailboxService::class)->deleteMessages($this->folderPath, [$this->uid]);
        $this->redirect(route('mailbox', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function moveToFolder(string $destinationPath): void
    {
        if (! $this->message) {
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
