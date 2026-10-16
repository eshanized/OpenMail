<?php

namespace App\Services;

use App\Models\PendingSend;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\Mime\Email;

class ComposerService
{
    public function __construct(
        protected ImapMailboxService $imapService,
        protected MessageSanitizer $messageSanitizer,
        protected MailTransportService $transportService
    ) {}

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

        return $this->transportService->buildCanonicalEmail($data, $user, $existingMessageId);
    }

    /**
     * Prepare a pending send record with a durable canonical MIME representation and delay.
     */
    public function prepareSend(int $userId, array $data, ?string $idempotencyKey = null): PendingSend
    {
        $user = User::find($userId) ?? Auth::user();
        $idempotencyKey = $idempotencyKey ?: ('send_'.Str::uuid());

        // Check if idempotency key already exists
        $existing = PendingSend::where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            return $existing;
        }

        $email = $this->buildMimeMessage($data, (string) $userId);
        $messageId = $email->getHeaders()->get('Message-ID')?->getBodyAsString() ?? (string) Str::uuid();
        $mimeString = $email->toString();

        $configuredDelay = (int) config('openmail.undo_send_delay', 10);
        $delaySeconds = max(0, min(30, $configuredDelay));

        return PendingSend::create([
            'user_id' => $userId,
            'idempotency_key' => $idempotencyKey,
            'message_id' => trim($messageId, '<> '),
            'message_json' => $data,
            'mime_message' => $mimeString,
            'send_at' => now()->addSeconds($delaySeconds),
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
            'max_retries' => 3,
            'sent_folder_synced' => false,
            'smtp_accepted' => false,
        ]);
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
        $pendingSend = $this->prepareSend($userId, $data, $idempotencyKey);
        $configuredDelay = (int) config('openmail.undo_send_delay', 10);
        $delaySeconds = max(0, min(30, $configuredDelay));

        // If delay is configured (> 0), hold for the undo window
        if ($delaySeconds > 0) {
            return [
                'success' => true,
                'pending_send_id' => $pendingSend->id,
                'queued' => true,
                'delay_seconds' => $delaySeconds,
                'sent_uid' => null,
            ];
        }

        // If delay is 0, claim and dispatch immediately
        $workerToken = (string) Str::uuid();
        if ($pendingSend->atomicClaim($workerToken)) {
            $deliveryResult = $this->deliverPendingSend($pendingSend, $workerToken);

            return [
                'success' => ! empty($deliveryResult['success']),
                'pending_send_id' => $pendingSend->id,
                'queued' => false,
                'delay_seconds' => 0,
                'sent_uid' => $deliveryResult['sent_uid'] ?? null,
                'error' => $deliveryResult['error'] ?? null,
            ];
        }

        return [
            'success' => true,
            'pending_send_id' => $pendingSend->id,
            'queued' => true,
            'delay_seconds' => 0,
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
        $email = $this->transportService->buildCanonicalEmail($data, $user, $send->message_id);

        // 3. Deliver via SMTP transport
        $delivery = $this->transportService->deliver($email);

        if ($delivery['success']) {
            // SMTP accepted delivery!
            $sentFolderUid = null;
            $sentSynced = false;

            // 4. Independently synchronize with IMAP Sent folder
            try {
                $sentFolderUid = $this->imapService->appendToSent($delivery['mime_string']);
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
                'message_id' => $delivery['message_id'],
            ];
        }

        // Handle delivery failures
        if ($delivery['uncertain']) {
            $send->markUncertain($delivery['error'] ?? 'Uncertain delivery state');

            return [
                'success' => false,
                'uncertain' => true,
                'error' => $delivery['error'],
            ];
        }

        if ($delivery['category'] === PendingSend::CATEGORY_REJECTION) {
            $send->recordFailure($delivery['error'] ?? 'SMTP delivery rejected', PendingSend::CATEGORY_REJECTION, false);

            return [
                'success' => false,
                'permanent' => true,
                'error' => $delivery['error'],
            ];
        }

        // Retryable transport failure
        $send->recordFailure($delivery['error'] ?? 'SMTP transport error', PendingSend::CATEGORY_TRANSPORT, true);

        return [
            'success' => false,
            'retry_scheduled' => true,
            'error' => $delivery['error'],
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
