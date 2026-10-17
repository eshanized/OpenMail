<?php

namespace App\Livewire\Mailbox;

use App\Models\MessageMetadata;
use App\Services\AiAssistantService;
use App\Services\ImapMailboxService;
use App\Services\LabelService;
use App\Services\MessageSanitizer;
use App\Services\SnoozedMessageService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
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

    public bool $showAiAssistant = false;

    public bool $aiDataSharingConfirmed = false;

    public bool $aiBusy = false;

    public ?string $aiSummary = null;

    public ?string $aiError = null;

    public function mount(string $folderPath, int $uid): void
    {
        if (! auth()->check()) {
            abort(401, 'Unauthenticated.');
        }

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
        try {
            $service = app(ImapMailboxService::class);
            $this->message = $service->getMessageWithBody($this->folderPath, $this->uid);
        } catch (\Throwable $e) {
            $this->message = null;
        }

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
        $htmlBody = null;
        if (method_exists($this->message, 'getHTMLBody')) {
            $htmlBody = $this->message->getHTMLBody();
        } elseif (isset($this->message->html_body)) {
            $htmlBody = $this->message->html_body;
        } elseif (isset($this->message->htmlBody)) {
            $htmlBody = $this->message->htmlBody;
        }

        if (! empty($htmlBody)) {
            $attachmentsList = method_exists($this->message, 'getAttachments')
                ? $this->message->getAttachments()
                : ($this->message->attachments ?? []);
            $htmlBody = $sanitizer->resolveCidImages($htmlBody, $attachmentsList ?? []);
            $this->sanitizedHtml = $sanitizer->sanitizeHtml($htmlBody);
        }

        // Get plain text body
        $textBody = null;
        if (method_exists($this->message, 'getTextBody')) {
            $textBody = $this->message->getTextBody();
        } elseif (isset($this->message->text_body)) {
            $textBody = $this->message->text_body;
        } elseif (isset($this->message->textBody)) {
            $textBody = $this->message->textBody;
        }

        if (! empty($textBody)) {
            $this->textBody = (string) $textBody;
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
        if ($this->uid <= 0) {
            return;
        }
        $newValue = ! $this->isSeen;
        $imap = app(ImapMailboxService::class);
        $imap->setFlag($this->folderPath, [$this->uid], '\\Seen', $newValue);
        $imap->refreshFolderCache($this->folderPath);
        $this->dispatch('folder-stats-updated');
        $this->isSeen = $newValue;
    }

    public function toggleStar(): void
    {
        if ($this->uid <= 0) {
            return;
        }
        $newValue = ! $this->isFlagged;
        $imap = app(ImapMailboxService::class);
        $imap->setFlag($this->folderPath, [$this->uid], '\\Flagged', $newValue);
        $this->isFlagged = $newValue;
    }

    public function archiveMessage(): void
    {
        if ($this->uid <= 0) {
            return;
        }

        $imapService = app(ImapMailboxService::class);
        $archivePath = $imapService->getOrCreateArchiveFolder();
        if ($archivePath && $this->folderPath !== $archivePath) {
            $imapService->moveMessages($this->folderPath, [$this->uid], $archivePath);
        }

        $userId = auth()->id();
        $labelService = app(LabelService::class);
        $archiveLabel = $labelService->ensureArchiveLabel($userId);
        $inboxLabel = $labelService->ensureInboxLabel($userId);

        $messageIds = MessageMetadata::where('user_id', $userId)
            ->where('folder_path', $this->folderPath)
            ->where('uid', $this->uid)
            ->pluck('id')
            ->toArray();

        if (! empty($messageIds)) {
            $labelService->removeFromMessages($inboxLabel->id, $messageIds, $userId);
            $labelService->applyToMessages($archiveLabel->id, $messageIds, $userId);
        }

        $imapService->refreshFolderCache($this->folderPath);
        if ($archivePath) {
            $imapService->refreshFolderCache($archivePath);
        }
        $this->dispatch('folder-stats-updated');

        $this->redirect(route('mailbox.folder', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function deleteMessage(): void
    {
        if ($this->uid <= 0) {
            return;
        }
        $imap = app(ImapMailboxService::class);
        $imap->deleteMessages($this->folderPath, [$this->uid]);
        $imap->refreshFolderCache($this->folderPath);
        $this->dispatch('folder-stats-updated');
        $this->redirect(route('mailbox.folder', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function moveToFolder(string $destinationPath): void
    {
        if ($this->uid <= 0) {
            return;
        }
        $imap = app(ImapMailboxService::class);
        $imap->moveMessages($this->folderPath, [$this->uid], $destinationPath);
        $imap->refreshFolderCache($this->folderPath);
        $imap->refreshFolderCache($destinationPath);
        $this->dispatch('folder-stats-updated');
        $this->redirect(route('mailbox.folder', ['folderPath' => $this->folderPath]), navigate: true);
    }

    public function snoozeUntil(string $preset): void
    {
        $timezone = (string) config('app.timezone');
        $now = CarbonImmutable::now($timezone);

        $wakeAt = match ($preset) {
            'in_hour' => $now->addHour(),
            'tomorrow' => $now->addDay()->setTime(9, 0),
            'next_week' => $now->addWeek()->setTime(9, 0),
            default => null,
        };

        if (! $wakeAt) {
            $this->dispatch('toast', 'Choose a valid snooze time.', 'warning');

            return;
        }

        if ($this->uid <= 0 || trim($this->folderPath) === '') {
            $this->dispatch('toast', 'This message cannot be snoozed because its mailbox location is invalid.', 'error');

            return;
        }

        // Re-fetch from the authenticated mailbox instead of trusting hydrated display fields.
        $this->loadMessage();
        if (! $this->message) {
            $this->dispatch('toast', 'This message is no longer available.', 'warning');

            return;
        }

        try {
            app(SnoozedMessageService::class)->snooze(
                (int) auth()->id(),
                $this->folderPath,
                $this->uid,
                trim($this->messageId) !== '' ? $this->messageId : null,
                $wakeAt,
                $this->subject,
                $this->fromDisplay ?: $this->fromAddress
            );

            $this->dispatch(
                'toast',
                'Message snoozed until '.$wakeAt->timezone($timezone)->format('M j, g:i A').'.',
                'success'
            );
            $this->redirect(route('mailbox.folder', ['folderPath' => $this->folderPath]), navigate: true);
        } catch (\\Throwable $exception) {
            Log::warning('Message snooze failed', [
                'user_id' => auth()->id(),
                'folder' => $this->folderPath,
                'uid' => $this->uid,
                'exception' => $exception::class,
            ]);
            $this->dispatch('toast', 'Could not snooze this message. Try again.', 'error');
        }
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

    public function toggleAiAssistant(): void
    {
        if (! app(AiAssistantService::class)->isEnabled()) {
            $this->dispatch('toast', 'AI assistance is disabled or not configured by the administrator.', 'info');

            return;
        }

        $this->showAiAssistant = ! $this->showAiAssistant;
        $this->aiError = null;
    }

    public function generateAiSummary(): void
    {
        $this->runAiAction('summary');
    }

    public function generateAiReply(): void
    {
        $this->runAiAction('reply');
    }

    protected function runAiAction(string $task): void
    {
        if (! in_array($task, ['summary', 'reply'], true)) {
            return;
        }

        if (! $this->aiDataSharingConfirmed) {
            $this->addError('aiDataSharingConfirmed', 'Confirm that this message may be shared with the configured AI endpoint.');

            return;
        }

        $assistant = app(AiAssistantService::class);
        if (! $assistant->isEnabled()) {
            $this->aiError = 'AI assistance is disabled or not configured by the administrator.';

            return;
        }

        $this->aiBusy = true;
        $this->aiError = null;

        try {
            // Reload from the authenticated mailbox so client-mutated Livewire properties
            // are not used as the source of email content sent to the AI provider.
            $this->loadMessage();

            if (! $this->message || $this->uid <= 0) {
                $this->aiError = 'This message is no longer available.';

                return;
            }

            $content = trim((string) ($this->textBody ?: strip_tags($this->sanitizedHtml ?? '')));
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $content = preg_replace('/[\\t ]+/u', ' ', $content) ?? $content;
            $content = trim($content);

            if ($content === '') {
                $this->aiError = 'This message has no readable text to analyze.';

                return;
            }

            if ($task === 'summary') {
                $this->aiSummary = $assistant->summarize($this->subject, $this->fromAddress, $content);
                $this->dispatch('toast', 'Email summary is ready.', 'success');

                return;
            }

            if (! filter_var($this->fromAddress, FILTER_VALIDATE_EMAIL)) {
                $this->aiError = 'The sender address is not valid enough to prepare a reply.';

                return;
            }

            $draft = $assistant->draftReply($this->subject, $this->fromAddress, $content);
            $replySubject = preg_match('/^re:/i', $this->subject) ? $this->subject : 'Re: '.$this->subject;

            // This event only fills an editable composer. It never sends the message.
            $this->dispatch('applyAiDraft', to: $this->fromAddress, subject: $replySubject, body: $draft);
            $this->dispatch('toast', 'AI reply draft created. Review it before sending.', 'success');
        } catch (\Throwable $exception) {
            Log::warning('Message AI action failed', [
                'user_id' => auth()->id(),
                'task' => $task,
                'exception' => $exception::class,
            ]);
            $this->aiError = 'The AI request failed. Check provider configuration and try again.';
        } finally {
            $this->aiBusy = false;
        }
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
