<?php

namespace App\Services;

use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Header\UnstructuredHeader;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\PendingSend;

class ComposerService
{
    public function __construct(
        protected ImapMailboxService $imapService,
        protected MessageSanitizer $messageSanitizer
    ) {}

    /**
     * Build a MIME message from composer data.
     *
     * @param array $data Composer data: to, cc, bcc, subject, body, attachments, in_reply_to, references
     * @param string|null $userId
     * @return Email
     */
    public function buildMimeMessage(array $data, ?string $userId = null): Email
    {
        $user = $userId ? \App\Models\User::find($userId) : Auth::user();

        // Auto-insert default signature if composing (not reply/forward) and no signature already in body
        if (($data['mode'] ?? 'compose') === 'compose' && empty($data['signature_id'])) {
            $defaultSig = app(SignatureService::class)->getDefault($user);
            if ($defaultSig && $defaultSig->content_html && !str_contains($data['body'] ?? '', $defaultSig->content_html)) {
                $data['body'] = ($data['body'] ?? '') . '<br><br>' . $defaultSig->content_html;
            }
        }

        $email = (new Email())
            ->from(new Address($user->email, $user->name ?? ''));

        // Parse and set To addresses
        $toAddresses = $this->parseAddresses($data['to'] ?? '');
        $email->to(...$toAddresses);

        // Parse and set CC addresses
        $ccAddresses = $this->parseAddresses($data['cc'] ?? '');
        $email->cc(...$ccAddresses);

        // Parse and set BCC addresses
        $bccAddresses = $this->parseAddresses($data['bcc'] ?? '');
        $email->bcc(...$bccAddresses);

        $email->subject($data['subject'] ?? '(no subject)');

        // Set text body: convert HTML to plain text
        $textBody = $this->htmlToText($data['body'] ?? '');
        $email->text($textBody);

        // Set HTML body
        $email->html($data['body'] ?? '');

        // Attach files
        foreach ($data['attachments'] ?? [] as $att) {
            $email->attach(
                DataPart::fromPath(
                    $att['path'],
                    $att['name'],
                    $att['mime'] ?? 'application/octet-stream'
                )->setDisposition('attachment')
            );
        }

        // Generate Message-ID
        $messageId = Str::uuid() . '@' . config('app.domain', 'openmail.local');
        $email->getHeaders()->add(new \Symfony\Component\Mime\Header\IdentificationHeader('Message-ID', $messageId));

        // For replies/forwards: set In-Reply-To and References headers
        if (!empty($data['in_reply_to'])) {
            // Remove angle brackets if present
            $inReplyTo = trim($data['in_reply_to'], '<>');
            $email->getHeaders()->add(new \Symfony\Component\Mime\Header\IdentificationHeader('In-Reply-To', $inReplyTo));
        }
        if (!empty($data['references'])) {
            // Remove angle brackets from each reference
            $refs = array_map(fn($r) => trim($r, '<>'), explode(' ', $data['references']));
            $email->getHeaders()->add(new \Symfony\Component\Mime\Header\IdentificationHeader('References', $refs));
        }

        return $email;
    }

    /**
     * Send a message: IMAP APPEND to Sent first, then SMTP.
     *
     * @param int $userId
     * @param array $data Composer data
     * @return array ['success' => bool, 'sent_uid' => string|null, 'queued' => bool]
     */
    public function sendMessage(int $userId, array $data): array
    {
        $mimeMessage = $this->buildMimeMessage($data, (string) $userId);
        $mimeString = $mimeMessage->toString();

        // IMAP APPEND to Sent folder FIRST (per D-17)
        $sentUid = $this->imapService->appendToSent($mimeString);

        if (!$sentUid) {
            throw new \Exception('Failed to append message to Sent folder');
        }

        // Send via SMTP using raw MIME
        try {
            Mail::raw($mimeString, function ($message) use ($data) {
                $message->to($this->parseAddresses($data['to'] ?? ''))
                    ->cc($this->parseAddresses($data['cc'] ?? ''))
                    ->bcc($this->parseAddresses($data['bcc'] ?? ''))
                    ->subject($data['subject'] ?? '(no subject)');

                // Replace Symfony's generated MIME with our pre-built one
                $message->using(function (Email $symfonyMessage) use ($mimeMessage) {
                    // Copy all headers from our pre-built MIME message
                    foreach ($mimeMessage->getHeaders()->all() as $header) {
                        $symfonyMessage->getHeaders()->add($header);
                    }
                    // Set body from our pre-built message
                    $symfonyMessage->setBody($mimeMessage->getBody());
                });
            });

            return ['success' => true, 'sent_uid' => $sentUid, 'queued' => false];
        } catch (\Exception $e) {
            // On SMTP failure: queue for retry (per D-18)
            $delay = config('openmail.undo_send_delay', 10);
            $pendingSend = PendingSend::create([
                'user_id' => $userId,
                'message_json' => $data,
                'mime_message' => $mimeString,
                'send_at' => now()->addSeconds($delay),
                'status' => 'pending',
                'sent_folder_uid' => $sentUid,
                'retry_count' => 0,
            ]);

            return ['success' => false, 'queued' => true, 'sent_uid' => $sentUid, 'pending_send_id' => $pendingSend->id];
        }
    }

    /**
     * Save a draft to IMAP Drafts folder.
     *
     * @param int $userId
     * @param array $data Composer data
     * @param string|null $existingDraftUid
     * @return array ['success' => bool, 'draft_uid' => string|null]
     */
    public function saveDraft(int $userId, array $data, ?string $existingDraftUid = null): array
    {
        $mimeMessage = $this->buildMimeMessage($data, (string) $userId);
        $mimeString = $mimeMessage->toString();

        $uid = $this->imapService->appendToDrafts($mimeString, $existingDraftUid);

        return ['success' => true, 'draft_uid' => $uid];
    }

    /**
     * Undo a pending send.
     *
     * @param int $pendingSendId
     * @param int $userId
     * @return bool
     */
    public function undoSend(int $pendingSendId, int $userId): bool
    {
        $pendingSend = PendingSend::where('id', $pendingSendId)
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$pendingSend) {
            return false;
        }

        // Update status to cancelled
        $pendingSend->update(['status' => 'cancelled']);

        // If message was appended to Sent folder, delete it
        if ($pendingSend->sent_folder_uid) {
            $this->imapService->deleteFromSent($pendingSend->sent_folder_uid);
        }

        // Move to Drafts folder (per D-24)
        if ($pendingSend->mime_message) {
            $this->imapService->appendToDrafts($pendingSend->mime_message);
        }

        return true;
    }

    /**
     * Parse comma-separated email addresses into Address objects.
     *
     * @param string $addressString
     * @return Address[]
     */
    private function parseAddresses(string $addressString): array
    {
        $addresses = [];
        foreach (array_filter(array_map('trim', explode(',', $addressString))) as $addr) {
            // Try to parse "Name <email>" format
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $addr, $matches)) {
                $addresses[] = new Address(trim($matches[2]), trim($matches[1]));
            } else {
                $addresses[] = new Address($addr);
            }
        }
        return $addresses;
    }

    /**
     * Convert HTML to plain text for text part of MIME message.
     *
     * @param string $html
     * @return string
     */
    private function htmlToText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return wordwrap(trim($text), 78, "\n");
    }
}