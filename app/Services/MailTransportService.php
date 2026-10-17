<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\IdentificationHeader;
use Symfony\Component\Mime\RawMessage;

class MailTransportService
{
    public function __construct(
        protected ?TransportInterface $transport = null
    ) {}

    /**
     * Get or initialize the Symfony Mailer transport.
     */
    public function getTransport(): TransportInterface
    {
        if ($this->transport !== null) {
            return $this->transport;
        }

        return Mail::mailer()->getSymfonyTransport();
    }

    /**
     * Set a custom transport (useful for unit/integration tests).
     */
    public function setTransport(TransportInterface $transport): self
    {
        $this->transport = $transport;

        return $this;
    }

    /**
     * Create Email instance from data and userId.
     */
    public function createEmail(array $data, ?string $userId = null, ?string $existingMessageId = null): Email
    {
        $user = $userId ? User::find($userId) : null;

        return $this->buildCanonicalEmail($data, $user, $existingMessageId);
    }

    /**
     * Send a canonical Email or a previously serialized MIME payload.
     *
     * Raw MIME is sent without parsing or rewriting it. For queued sends, supply the
     * authenticated sender and composer recipient data so the SMTP envelope can be
     * rebuilt independently of message headers (especially Bcc).
     *
     * @param mixed $mimeOrEmail
     * @param array<string, mixed> $envelopeData
     * @return array{success: bool, message_id: string, mime_string: string, category: string|null, error: string|null, uncertain: bool}
     */
    public function sendMime(mixed $mimeOrEmail, ?User $user = null, array $envelopeData = []): array
    {
        if ($mimeOrEmail instanceof Email) {
            return $this->deliver($mimeOrEmail);
        }

        if (! is_string($mimeOrEmail) || trim($mimeOrEmail) === '') {
            throw new \\InvalidArgumentException('A non-empty MIME message is required.');
        }

        if (! $user) {
            throw new \\InvalidArgumentException('A sender is required to deliver a persisted MIME message.');
        }

        return $this->deliverRawMime($mimeOrEmail, $user, $envelopeData);
    }

    /**
     * Deliver previously serialized MIME while keeping envelope recipients separate
     * from message headers. This preserves attachments and avoids leaking Bcc.
     *
     * @param array<string, mixed> $envelopeData
     */
    protected function deliverRawMime(string $mimeString, User $user, array $envelopeData): array
    {
        $recipients = array_merge(
            $this->parseAddresses((string) ($envelopeData['to'] ?? '')),
            $this->parseAddresses((string) ($envelopeData['cc'] ?? '')),
            $this->parseAddresses((string) ($envelopeData['bcc'] ?? ''))
        );

        $uniqueRecipients = [];
        foreach ($recipients as $recipient) {
            $uniqueRecipients[strtolower($recipient->getAddress())] = $recipient;
        }

        $messageId = $this->extractMessageIdFromMime($mimeString);
        if ($uniqueRecipients === []) {
            return [
                'success' => false,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => 'rejection',
                'error' => 'The message has no valid envelope recipients.',
                'uncertain' => false,
            ];
        }

        $rawMessage = new RawMessage($mimeString);
        $envelope = new Envelope(
            new Address($user->email),
            array_values($uniqueRecipients)
        );

        try {
            $this->getTransport()->send($rawMessage, $envelope);

            return [
                'success' => true,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => null,
                'error' => null,
                'uncertain' => false,
            ];
        } catch (TransportExceptionInterface $e) {
            $classification = $this->classifyError($e->getMessage());

            Log::warning('SMTP delivery of persisted MIME failed', [
                'message_id' => $messageId,
                'category' => $classification['category'],
                'uncertain' => $classification['uncertain'],
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => $classification['category'],
                'error' => $e->getMessage(),
                'uncertain' => $classification['uncertain'],
            ];
        } catch (\\Throwable $e) {
            Log::error('Unexpected error delivering persisted MIME', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => 'rejection',
                'error' => $e->getMessage(),
                'uncertain' => false,
            ];
        }
    }

    protected function extractMessageIdFromMime(string $mimeString): string
    {
        if (preg_match('/^Message-ID:\\s*<?([^>\\r\\n]+)>?/im', $mimeString, $matches)) {
            return trim($matches[1]);
        }

        return (string) Str::uuid();
    }

    /**
     * Get envelope recipients including To, Cc, and Bcc.
     *
     * @return Address[]
     */
    public function getEnvelopeRecipients(Email $email, array $bccStrings = []): array
    {
        $recipients = array_merge($email->getTo(), $email->getCc(), $email->getBcc());

        foreach ($bccStrings as $bcc) {
            foreach ($this->parseAddresses($bcc) as $addr) {
                $recipients[] = $addr;
            }
        }

        $unique = [];
        foreach ($recipients as $recipient) {
            $unique[$recipient->getAddress()] = $recipient;
        }

        return array_values($unique);
    }

    /**
     * Build a canonical Symfony Email object.
     *
     * @param  array  $data  Composer fields (to, cc, bcc, subject, body, attachments, in_reply_to, references, mode)
     * @param  User|null  $user  Authenticated sender
     * @param  string|null  $existingMessageId  Stable Message-ID for retries/pending sends
     */
    public function buildCanonicalEmail(array $data, ?User $user, ?string $existingMessageId = null): Email
    {
        $email = new Email;

        // Sender
        if ($user) {
            $email->from(new Address($user->email, $user->name ?? ''));
        }

        // Recipients
        $toAddresses = $this->parseAddresses($data['to'] ?? '');
        if (! empty($toAddresses)) {
            $email->to(...$toAddresses);
        }

        $ccAddresses = $this->parseAddresses($data['cc'] ?? '');
        if (! empty($ccAddresses)) {
            $email->cc(...$ccAddresses);
        }

        $bccAddresses = $this->parseAddresses($data['bcc'] ?? '');
        if (! empty($bccAddresses)) {
            $email->bcc(...$bccAddresses);
        }

        // Subject (Unicode & Emoji safe)
        $email->subject($data['subject'] ?? '(no subject)');

        // Body: Plain text & HTML
        $htmlBody = $data['body'] ?? '';
        $textBody = $data['bodyText'] ?? $this->htmlToText($htmlBody);

        $email->text($textBody);
        if (! empty($htmlBody)) {
            $email->html($htmlBody);
        }

        // Attachments
        foreach ($data['attachments'] ?? [] as $att) {
            $filePath = $att['path'] ?? null;
            if ($filePath && file_exists($filePath)) {
                $email->attachFromPath(
                    $filePath,
                    $att['name'] ?? basename($filePath),
                    $att['mime'] ?? null
                );
            }
        }

        // Canonical Message-ID: generate once or preserve across retries
        $domain = config('openmail.domain') ?: (config('app.domain') ?: 'openmail.local');
        $messageId = $existingMessageId ?: (Str::uuid().'@'.$domain);
        $cleanId = trim($messageId, '<>');
        $email->getHeaders()->add(new IdentificationHeader('Message-ID', $cleanId));

        // Threading headers: In-Reply-To and References
        if (! empty($data['in_reply_to'])) {
            $inReplyTo = trim($data['in_reply_to'], '<>');
            $email->getHeaders()->add(new IdentificationHeader('In-Reply-To', $inReplyTo));
        }

        if (! empty($data['references'])) {
            $refs = array_filter(array_map(fn ($r) => trim($r, '<>'), explode(' ', $data['references'])));
            if (! empty($refs)) {
                $email->getHeaders()->add(new IdentificationHeader('References', $refs));
            }
        }

        return $email;
    }

    /**
     * Send an email over SMTP transport with explicit outcome categorization.
     *
     * @return array{
     *   success: bool,
     *   message_id: string,
     *   mime_string: string,
     *   category: string|null,
     *   error: string|null,
     *   uncertain: bool
     * }
     */
    public function deliver(Email $email): array
    {
        $messageId = $this->extractMessageId($email);
        $mimeString = $email->toString();

        try {
            $transport = $this->getTransport();
            $transport->send($email);

            return [
                'success' => true,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => null,
                'error' => null,
                'uncertain' => false,
            ];
        } catch (TransportExceptionInterface $e) {
            $errorMessage = $e->getMessage();
            $classification = $this->classifyError($errorMessage);

            Log::warning('SMTP delivery failed', [
                'message_id' => $messageId,
                'category' => $classification['category'],
                'uncertain' => $classification['uncertain'],
                'error' => $errorMessage,
            ]);

            return [
                'success' => false,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => $classification['category'],
                'error' => $errorMessage,
                'uncertain' => $classification['uncertain'],
            ];
        } catch (\Throwable $e) {
            Log::error('Unexpected error during SMTP delivery', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message_id' => $messageId,
                'mime_string' => $mimeString,
                'category' => 'rejection',
                'error' => $e->getMessage(),
                'uncertain' => false,
            ];
        }
    }

    /**
     * Parse email strings into Address objects.
     */
    public function parseAddresses(string $raw): array
    {
        if (trim($raw) === '') {
            return [];
        }

        $addresses = [];
        // Split by comma or semicolon
        $parts = preg_split('/[,;]/', $raw);

        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            // Matches "Name <email@example.com>" or just "email@example.com"
            if (preg_match('/^(.*?)\s*<([^>]+)>$/u', $part, $matches)) {
                $name = trim($matches[1], ' "\'');
                $addr = trim($matches[2]);
                if (filter_var($addr, FILTER_VALIDATE_EMAIL)) {
                    $addresses[] = new Address($addr, $name);
                }
            } elseif (filter_var($part, FILTER_VALIDATE_EMAIL)) {
                $addresses[] = new Address($part);
            }
        }

        return $addresses;
    }

    /**
     * Simple plain-text converter from HTML.
     */
    public function htmlToText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/\n{3,}/", "\n\n", $text));
    }

    /**
     * Classify SMTP errors into rejection, transport retry, or uncertain acceptance.
     */
    protected function classifyError(string $message): array
    {
        // Uncertain acceptance: connection timed out or closed during or after DATA command
        if (preg_match('/(timeout|timed out|connection reset|connection closed|broken pipe).*?(data|reading response)/i', $message) ||
            preg_match('/after sending message body/i', $message)) {
            return [
                'category' => 'uncertain',
                'uncertain' => true,
            ];
        }

        // Definite rejection: 5xx SMTP codes
        if (preg_match('/\b5\d{2}\b/', $message) || preg_match('/rejected|recipient unknown|user not found|relay access denied/i', $message)) {
            return [
                'category' => 'rejection',
                'uncertain' => false,
            ];
        }

        // Default: retryable transport failure (e.g. 4xx codes, connection refused before transfer)
        return [
            'category' => 'transport',
            'uncertain' => false,
        ];
    }

    /**
     * Extract Message-ID from Symfony Email headers.
     */
    protected function extractMessageId(Email $email): string
    {
        $header = $email->getHeaders()->get('Message-ID');
        if ($header instanceof IdentificationHeader) {
            $ids = $header->getIds();

            return ! empty($ids) ? $ids[0] : (string) Str::uuid();
        }

        return (string) Str::uuid();
    }
}
