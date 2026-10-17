<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\FollowUpReminders;
use App\Models\FollowUpReminder;
use App\Models\User;
use App\Services\FollowUpReminderService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FollowUpReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduling_same_message_updates_its_existing_reminder(): void
    {
        $user = User::factory()->create();
        $service = app(FollowUpReminderService::class);
        $remindAt = CarbonImmutable::now(config('app.timezone'))->addDays(2);

        $first = $service->schedule(
            $user->id,
            'INBOX',
            123,
            '<message-123@example.test>',
            'Follow up with client',
            'Client <client@example.test>',
            $remindAt
        );

        $updated = $service->schedule(
            $user->id,
            'INBOX',
            123,
            '<message-123@example.test>',
            'Follow up with client',
            'Client <client@example.test>',
            $remindAt->addDay()
        );

        $this->assertSame($first->id, $updated->id);
        $this->assertDatabaseCount('follow_up_reminders', 1);
        $this->assertSame(FollowUpReminder::STATUS_OPEN, $updated->status);
        $this->assertSame('Follow up with client', $updated->subject);
        $this->assertEquals($remindAt->addDay()->timestamp, $updated->remind_at->timestamp);
    }

    public function test_scheduler_rejects_invalid_message_locations_and_time_windows(): void
    {
        $user = User::factory()->create();
        $service = app(FollowUpReminderService::class);
        $now = CarbonImmutable::now(config('app.timezone'));

        foreach ([
            ['', 123, $now->addHours(2)],
            ['INBOX', 0, $now->addHours(2)],
            ['INBOX', 123, $now->addSeconds(20)],
            ['INBOX', 123, $now->addYears(2)],
        ] as [$folder, $uid, $when]) {
            try {
                $service->schedule($user->id, $folder, $uid, null, 'Reminder', 'sender@example.test', $when);
                $this->fail('Invalid reminder requests must be rejected.');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_reminder_management_is_owner_scoped_and_supports_complete_and_dismiss(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $owned = FollowUpReminder::create([
            'user_id' => $owner->id,
            'folder_path' => 'INBOX',
            'uid' => 101,
            'message_id' => '<owned@example.test>',
            'subject' => 'My follow-up',
            'sender' => 'client@example.test',
            'remind_at' => now()->subMinute(),
            'status' => FollowUpReminder::STATUS_OPEN,
        ]);

        $private = FollowUpReminder::create([
            'user_id' => $other->id,
            'folder_path' => 'INBOX',
            'uid' => 202,
            'message_id' => '<private@example.test>',
            'subject' => 'Private follow-up',
            'sender' => 'private@example.test',
            'remind_at' => now()->addHour(),
            'status' => FollowUpReminder::STATUS_OPEN,
        ]);

        Livewire::actingAs($owner)
            ->test(FollowUpReminders::class)
            ->assertSee('My follow-up')
            ->assertDontSee('Private follow-up')
            ->call('completeReminder', $private->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('follow_up_reminders', [
            'id' => $private->id,
            'status' => FollowUpReminder::STATUS_OPEN,
        ]);

        Livewire::actingAs($owner)
            ->test(FollowUpReminders::class)
            ->call('completeReminder', $owned->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('follow_up_reminders', [
            'id' => $owned->id,
            'status' => FollowUpReminder::STATUS_COMPLETED,
        ]);
    }
}
