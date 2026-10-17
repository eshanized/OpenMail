<?php

namespace App\Services;

use App\Models\PendingSend;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class ComposerService
{
    public function __construct(
        protected ImapMailboxService $imapService,
        protected MessageSanitizer $messageSanitizer,
        protected ?MailTransportService $transportService = null
    ) {
        $this->transportService = $this->transportService ?? app(MailTransportService::class);
    }

    /**
     * Build a canonical MIME message from composer data.
     *
     * @param  array  $data  Composer data: to, cc, bcc, subject, body, attachments, in_reply_to, references
     * @param  string|null  $userId  User ID
     * @param  string|null  $existingMessageId  Stable Message-ID
     */
    public function buildMimeMessage(array $data, ?string $userId = null, ?string $existingMessageId = null): Email
    {
        $user = $userId ? User::find($userId) : Auth::user();

        // Auto-insert default signature if composing (not reply/forward) and no signature already present
        if (($data['mode'] ?? 'compose') === 'compose' && empty($data['signature_id']) && $user) {
            $defaultSig = app(SignatureService::class)->getDefault($user);
            if ($defaultSig && ! empty($defaultSig->content_html) && ! str_contains($data['body'] ?? '', $defaultSig->content_html)) {
                $data['body'] = ($data['body'] ?? '').'<br><br>'.$defaultSig->content_html;
            }
        }

        return (new MailTransportService)->buildCanonicalEmail($data, $user, $existingMessageId);
    }

    /**
     * Prepare a pending send record with a durable canonical MIME representation and delay.
     */
    public function prepareSend(int $userId, array $data, int|string|null $delayOrIdempotency = null, ?string $idempotencyKey = null, ?CarbonImmutable $scheduledAt = null): PendingSend
    {
        $delaySeconds = (int) config('openmail.undo_send_delay', 10);
        if (is_int($delayOrIdempotency)) {
            $delaySeconds = max(0, min(30, $delayOrIdempotency));
        } elseif (is_string($delayOrIdempotency) && empty($idempotencyKey)) {
            $idempotencyKey = $delayOrIdempotency;
        }

        $user = User::find($userId) ?? Auth::user();
        $idempotencyKey = $idempotencyKey ?: ('send_'.Str::uuid());

        // Check if idempotency key already exists
        $existing = PendingSend::where('user_id', $userId)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if ($existing) {
            $existing->setAttribute('success', true);
            $existing->setAttribute('delayed', $delaySeconds > 0);
            $existing->setAttribute('pending_send_id', $existing->id);
            $existing->setAttribute('delay_seconds', $delaySeconds);

            return $existing;
        }

        $email = $this->buildMimeMessage($data, (string) $userId);
        $messageId = $email->getHeaders()->get('Message-ID')?->getBodyAsString() ?? (string) Str::uuid();
        $mimeString = $email->toString();

        $pendingSend = PendingSend::create([
            'user_id' => $userId,
            'idempotency_key' => $idempotencyKey,
            'message_id' => trim($messageId, '<> '),
            'message_json' => $data,
            'mime_message' => $mimeString,
            'send_at' => $scheduledAt ?? now()->addSeconds($delaySeconds),
            'is_scheduled' => $scheduledAt !== null,
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
            'max_retries' => 3,
            'sent_folder_synced' => false,
            'smtp_accepted' => false,
        ]);

        $pendingSend->setAttribute('success', true);
        $pendingSend->setAttribute('delayed', $delaySeconds > 0);
        $pendingSend->setAttribute('pending_send_id', $pendingSend->id);
        $pendingSend->setAttribute('delay_seconds', $delaySeconds);

        return $pendingSend;
    }

    /**
     * Persist a message for future delivery without attempting SMTP immediately.
     *
     * Scheduled messages use the same durable state machine and worker as ordinary
     * delayed sends, but are explicitly marked so they can be listed and managed.
     *
     * @return array{success: bool, pending_send_id: int, queued: bool, scheduled: bool, send_at: string}
     */
    public function scheduleMessage(
        int $userId,
        array $data,
        CarbonImmutable $scheduledAt,
        ?string $idempotencyKey = null
    ): array {
        $now = CarbonImmutable::now(config('app.timezone'));

        if ($scheduledAt->lessThanOrEqualTo($now->addMinute())) {
            throw new \\InvalidArgumentException('Scheduled send time must be at least one minute in the future.');
        }

        if ($scheduledAt->greaterThan($now->addYear())) {
            throw new \\InvalidArgumentException('Scheduled send time cannot be more than one year in the future.');
        }

        $pendingSend = $this->prepareSend(
            $userId,
            $data,
            0,
            $idempotencyKey,
            $scheduledAt
        );

        return [
            'success' => true,
            'pending_send_id' => (int) $pendingSend->id,
            'queued' => true,
            'scheduled' => true,
            'send_at' => $pendingSend->send_at->toIso8601String(),
        ];
    }

    /**
     * Send a message through the explicit delivery pipeline.
     *
     * @return array{
     *   success: bool,
     *   pending_send_id: int,
     *   queued: bool,
     *   delay_seconds: int,
     *   sent_uid: string|null,
     *   error?: string
     * }
     */
    public function sendMessage(int $userId, array $data, ?string $idempotencyKey = null): array
    {
        $workerToken = (string) Str::uuid();
        $pendingSend = $this->prepareSend($userId, $data, 0, $idempotencyKey);

        if ($pendingSend->atomicClaim($workerToken)) {
            $deliveryResult = $this->deliverPendingSend($pendingSend, $workerToken);

            if (! empty($deliveryResult['success'])) {
                return [
                    'success' => true,
                    'pending_send_id' => $pendingSend->id,
                    'queued' => false,
                    'delay_seconds' => 0,
                    'sent_uid' => $deliveryResult['sent_uid'] ?? null,
                ];
            }

            // On SMTP failure, release claim so pending send can be retried by worker
            $pendingSend->update([
                'status' => PendingSend::STATUS_PENDING,
                'claim_token' => null,
                'claimed_at' => null,
            ]);

            return [
                'success' => false,
                'queued' => true,
                'sent_uid' => null,
                'pending_send_id' => $pendingSend->id,
                'error' => $deliveryResult['error'] ?? 'SMTP delivery failed',
            ];
        }

        return [
            'success' => false,
            'queued' => true,
            'pending_send_id' => $pendingSend->id,
            'sent_uid' => null,
        ];
    }

    /**
     * Concurrency-safe delivery of an atomically claimed pending send.
     */
    public function deliverPendingSend(PendingSend $send, string $workerToken): array
    {
        // 1. Transition to processing
        if (! $send->markProcessing($workerToken)) {
            return [
                'success' => false,
                'error' => 'Could not transition record to processing state',
            ];
        }

        $user = User::find($send->user_id);
        $data = $send->message_json ?? [];

        // 2. Reconstruct canonical email with identical Message-ID
        $email = (new MailTransportService)->buildCanonicalEmail($data, $user, $send->message_id);

        // 3. Deliver via SMTP transport
        $delivery = $this->transportService->sendMime($email);

        if (! empty($delivery['success'])) {
            // SMTP accepted delivery!
            $sentFolderUid = null;
            $sentSynced = false;

            // 4. Independently synchronize with IMAP Sent folder
            try {
                $mimeString = $delivery['mime_string'] ?? $email->toString();
                $sentFolderUid = $this->imapService->appendToSent($mimeString);
                $sentSynced = $sentFolderUid !== null;
            } catch (\Throwable $e) {
                Log::warning('Sent-folder IMAP synchronization failed after accepted SMTP delivery', [
                    'pending_send_id' => $send->id,
                    'error' => $e->getMessage(),
                ]);
                $send->update([
                    'last_error' => 'Sent-folder synchronization failed: '.$e->getMessage(),
                    'last_error_category' => PendingSend::CATEGORY_SENT_SYNC,
                ]);
            }

            // 5. Mark sent with delivery timestamp and Sent UID
            $send->markSent($sentFolderUid);
            if ($sentSynced) {
                $send->update(['sent_folder_synced' => true]);
            }

            return [
                'success' => true,
                'sent_uid' => $sentFolderUid,
                'message_id' => $delivery['message_id'] ?? $send->message_id,
            ];
        }

        // Handle delivery failures
        if (! empty($delivery['uncertain'])) {
            $send->markUncertain($delivery['error'] ?? 'Uncertain delivery state');

            return [
                'success' => false,
                'uncertain' => true,
                'error' => $delivery['error'] ?? null,
            ];
        }

        if (($delivery['category'] ?? null) === PendingSend::CATEGORY_REJECTION) {
            $send->recordFailure($delivery['error'] ?? 'SMTP delivery rejected', PendingSend::CATEGORY_REJECTION, false);

            return [
                'success' => false,
                'permanent' => true,
                'error' => $delivery['error'] ?? null,
            ];
        }

        // Retryable transport failure
        $send->recordFailure($delivery['error'] ?? 'SMTP transport error', PendingSend::CATEGORY_TRANSPORT, true);

        return [
            'success' => false,
            'retry_scheduled' => true,
            'error' => $delivery['error'] ?? null,
        ];
    }

    /**
     * Atomically undo a pending send during its cancellation window.
     */
    public function undoSend(int $pendingSendId, int $userId): bool
    {
        $pendingSend = PendingSend::where('id', $pendingSendId)
            ->where('user_id', $userId)
            ->first();

        if (! $pendingSend || ! $pendingSend->isCancellable()) {
            return false;
        }

        // Atomically cancel
        if (! $pendingSend->atomicCancel()) {
            return false;
        }

        try {
            if ($pendingSend->sent_folder_uid) {
                try {
                    $this->imapService->deleteFromSent($pendingSend->sent_folder_uid);
                } catch (\Throwable) {
                }
            }

            // Save as draft so the user doesn't lose their compose content
            if ($pendingSend->mime_message) {
                $this->imapService->appendToDrafts($pendingSend->mime_message);
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Could not save cancelled pending send to IMAP Drafts: '.$e->getMessage(), [
                'pending_send_id' => $pendingSendId,
            ]);

            // Still returns true because the send itself was successfully cancelled before SMTP delivery!
            return true;
        }
    }

    /**
     * Save draft to IMAP Drafts folder safely.
     */
    public function saveDraft(int $userId, array $data, ?string $existingDraftUid = null): array
    {
        $email = $this->buildMimeMessage($data, (string) $userId);
        $mimeString = $email->toString();

        $uid = $this->imapService->appendToDrafts($mimeString, $existingDraftUid);

        if (! $uid) {
            throw new \RuntimeException('Failed to persist draft to IMAP server');
        }

        return ['success' => true, 'draft_uid' => $uid];
    }

    /**
     * Delete draft from IMAP Drafts folder.
     */
    public function deleteDraft(string $draftUid): bool
    {
        return $this->imapService->deleteFromDrafts($draftUid);
    }

    /**
     * Get underlying IMAP mailbox service.
     */
    public function getImapService(): ImapMailboxService
    {
        return $this->imapService;
    }
}
