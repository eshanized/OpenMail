<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FollowUpReminder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class FollowUpReminderService
{
    public function schedule(
        int $userId,
        string $folderPath,
        int $uid,
        ?string $messageId,
        string $subject,
        string $sender,
        CarbonImmutable $remindAt
    ): FollowUpReminder {
        if ($userId <= 0 || $uid <= 0 || trim($folderPath) === '') {
            throw new InvalidArgumentException('A valid user, folder, and message UID are required.');
        }

        $now = CarbonImmutable::now(config('app.timezone'));

        if ($remindAt->lessThanOrEqualTo($now->addMinute())) {
            throw new InvalidArgumentException('Reminder time must be at least one minute in the future.');
        }

        if ($remindAt->greaterThan($now->addYear())) {
            throw new InvalidArgumentException('Reminder time cannot be more than one year in the future.');
        }

        return DB::transaction(function () use ($userId, $folderPath, $uid, $messageId, $subject, $sender, $remindAt): FollowUpReminder {
            return FollowUpReminder::query()->updateOrCreate(
                [
                    'user_id' => $userId,
                    'folder_path' => $folderPath,
                    'uid' => $uid,
                ],
                [
                    'message_id' => $messageId !== null ? mb_substr(trim($messageId), 0, 255) : null,
                    'subject' => mb_substr(trim($subject), 0, 255),
                    'sender' => mb_substr(trim($sender), 0, 320),
                    'remind_at' => $remindAt,
                    'status' => FollowUpReminder::STATUS_OPEN,
                    'completed_at' => null,
                ]
            );
        });
    }

    public function complete(int $userId, int $reminderId): bool
    {
        return $this->updateStatus($userId, $reminderId, FollowUpReminder::STATUS_COMPLETED);
    }

    public function dismiss(int $userId, int $reminderId): bool
    {
        return $this->updateStatus($userId, $reminderId, FollowUpReminder::STATUS_DISMISSED);
    }

    protected function updateStatus(int $userId, int $reminderId, string $status): bool
    {
        if ($userId <= 0 || $reminderId <= 0) {
            return false;
        }

        $reminder = FollowUpReminder::query()
            ->where('user_id', $userId)
            ->whereKey($reminderId)
            ->where('status', FollowUpReminder::STATUS_OPEN)
            ->first();

        if (! $reminder) {
            return false;
        }

        return DB::transaction(function () use ($reminder, $status): bool {
            $updated = FollowUpReminder::query()
                ->whereKey($reminder->id)
                ->where('user_id', $reminder->user_id)
                ->where('status', FollowUpReminder::STATUS_OPEN)
                ->update([
                    'status' => $status,
                    'completed_at' => now(),
                    'updated_at' => now(),
                ]);

            return $updated === 1;
        });
    }
}
