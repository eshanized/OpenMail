<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SnoozedMessage;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class SnoozedMessageService
{
    public function snooze(
        int $userId,
        string $folderPath,
        int $uid,
        ?string $messageId,
        CarbonImmutable $wakeAt,
        ?string $subject = null,
        ?string $sender = null
    ): SnoozedMessage {
        if ($userId <= 0 || $uid <= 0 || trim($folderPath) === '') {
            throw new InvalidArgumentException('A valid user, folder, and message UID are required.');
        }

        $now = CarbonImmutable::now(config('app.timezone'));
        if ($wakeAt->lessThanOrEqualTo($now->addMinute())) {
            throw new InvalidArgumentException('Snooze time must be at least one minute in the future.');
        }
        if ($wakeAt->greaterThan($now->addYear())) {
            throw new InvalidArgumentException('Snooze time cannot be more than one year in the future.');
        }

        return DB::transaction(function () use ($userId, $folderPath, $uid, $messageId, $wakeAt, $subject, $sender): SnoozedMessage {
            return SnoozedMessage::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'folder_path' => $folderPath,
                    'uid' => $uid,
                ],
                [
                    'message_id' => $messageId !== null ? mb_substr(trim($messageId), 0, 255) : null,
                    'subject' => $subject !== null ? mb_substr(trim($subject), 0, 255) : null,
                    'sender' => $sender !== null ? mb_substr(trim($sender), 0, 320) : null,
                    'wake_at' => $wakeAt,
                    'status' => SnoozedMessage::STATUS_SNOOZED,
                ]
            );
        });
    }

    public function returnNow(int $userId, int $snoozedMessageId): bool
    {
        $message = SnoozedMessage::query()
            ->where('user_id', $userId)
            ->whereKey($snoozedMessageId)
            ->where('status', SnoozedMessage::STATUS_SNOOZED)
            ->first();

        if (! $message) {
            return false;
        }

        $message->update(['status' => SnoozedMessage::STATUS_RETURNED]);

        return true;
    }

    /**
     * Return only messages which are still due to remain snoozed.
     *
     * @return Collection<int, SnoozedMessage>
     */
    public function activeForFolder(int $userId, string $folderPath): Collection
    {
        return SnoozedMessage::query()
            ->where('user_id', $userId)
            ->where('folder_path', $folderPath)
            ->active()
            ->get(['id', 'user_id', 'folder_path', 'uid', 'message_id', 'subject', 'sender', 'wake_at', 'status']);
    }
}
