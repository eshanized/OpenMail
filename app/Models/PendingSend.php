<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class PendingSend extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_SENT = 'sent';

    public const STATUS_RETRY_WAITING = 'retry_waiting';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_CANCELLING = 'cancelling';

    public const STATUS_FAILED = 'failed';

    public const STATUS_UNCERTAIN = 'uncertain';

    public const CATEGORY_TRANSPORT = 'transport';

    public const CATEGORY_REJECTION = 'rejection';

    public const CATEGORY_UNCERTAIN = 'uncertain';

    public const CATEGORY_SENT_SYNC = 'sent_sync';

    public const CATEGORY_NONE = 'none';

    protected $table = 'pending_sends';

    protected $fillable = [
        'user_id',
        'idempotency_key',
        'message_id',
        'message_json',
        'mime_message',
        'send_at',
        'status',
        'claim_token',
        'claimed_at',
        'sent_folder_uid',
        'sent_folder_synced',
        'smtp_accepted',
        'delivered_at',
        'retry_count',
        'max_retries',
        'last_error_category',
        'last_error',
    ];

    protected $casts = [
        'message_json' => 'array',
        'send_at' => 'datetime',
        'claimed_at' => 'datetime',
        'delivered_at' => 'datetime',
        'status' => 'string',
        'sent_folder_synced' => 'boolean',
        'smtp_accepted' => 'boolean',
        'retry_count' => 'integer',
        'max_retries' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for records due to be claimed and delivered.
     */
    public function scopeDue(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_RETRY_WAITING])
            ->where('send_at', '<=', now());
    }

    /**
     * Scope for records that were claimed but abandoned by a crashed worker.
     */
    public function scopeStaleClaimed(Builder $query, int $leaseSeconds = 300): Builder
    {
        return $query->whereIn('status', [self::STATUS_CLAIMED, self::STATUS_PROCESSING])
            ->where('claimed_at', '<=', now()->subSeconds($leaseSeconds))
            ->where('smtp_accepted', false);
    }

    /**
     * Check whether the pending send is still cancellable by the user.
     */
    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_RETRY_WAITING], true)
            && ($this->send_at === null || $this->send_at->isFuture());
    }

    /**
     * Atomically claim a pending send record for this worker process.
     */
    public function atomicClaim(string $token): bool
    {
        $affected = DB::table($this->getTable())
            ->where('id', $this->id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_RETRY_WAITING])
            ->where('send_at', '<=', now())
            ->update([
                'status' => self::STATUS_CLAIMED,
                'claim_token' => $token,
                'claimed_at' => now(),
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Atomically transition to processing state under lease.
     */
    public function markProcessing(string $token): bool
    {
        $affected = DB::table($this->getTable())
            ->where('id', $this->id)
            ->where('claim_token', $token)
            ->whereIn('status', [self::STATUS_CLAIMED])
            ->update([
                'status' => self::STATUS_PROCESSING,
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Atomically mark as successfully sent.
     */
    public function markSent(?string $sentFolderUid = null): void
    {
        $updates = [
            'status' => self::STATUS_SENT,
            'smtp_accepted' => true,
            'delivered_at' => now(),
            'claim_token' => null,
            'claimed_at' => null,
        ];

        if ($sentFolderUid !== null) {
            $updates['sent_folder_uid'] = $sentFolderUid;
            $updates['sent_folder_synced'] = true;
        }

        $this->update($updates);
    }

    /**
     * Atomically cancel if still cancellable.
     */
    public function atomicCancel(): bool
    {
        $affected = DB::table($this->getTable())
            ->where('id', $this->id)
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_RETRY_WAITING])
            ->update([
                'status' => self::STATUS_CANCELLED,
                'updated_at' => now(),
            ]);

        if ($affected > 0) {
            $this->refresh();

            return true;
        }

        return false;
    }

    /**
     * Record uncertain acceptance (e.g. timeout during DATA phase where server may have accepted).
     */
    public function markUncertain(string $error): void
    {
        $this->update([
            'status' => self::STATUS_UNCERTAIN,
            'last_error' => $error,
            'last_error_category' => self::CATEGORY_UNCERTAIN,
            'claim_token' => null,
            'claimed_at' => null,
        ]);
    }

    /**
     * Schedule a retry with exponential backoff or mark permanently failed.
     */
    public function recordFailure(string $error, string $category, bool $canRetry = true): void
    {
        $newRetryCount = $this->retry_count + 1;

        if (! $canRetry || $newRetryCount >= $this->max_retries || $category === self::CATEGORY_REJECTION) {
            $this->update([
                'status' => self::STATUS_FAILED,
                'retry_count' => $newRetryCount,
                'last_error' => $error,
                'last_error_category' => $category,
                'claim_token' => null,
                'claimed_at' => null,
            ]);

            return;
        }

        // Exponential backoff: 2^attempt minutes (2, 4, 8 min)
        $backoffMinutes = (int) pow(2, $newRetryCount);

        $this->update([
            'status' => self::STATUS_RETRY_WAITING,
            'retry_count' => $newRetryCount,
            'send_at' => now()->addMinutes($backoffMinutes),
            'last_error' => $error,
            'last_error_category' => $category,
            'claim_token' => null,
            'claimed_at' => null,
        ]);
    }
}
