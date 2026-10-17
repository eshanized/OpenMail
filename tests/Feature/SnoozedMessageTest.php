<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\SnoozedMessages;
use App\Models\SnoozedMessage;
use App\Models\User;
use App\Services\SnoozedMessageService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SnoozedMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_snooze_persists_a_future_return_time_and_updates_existing_location(): void
    {
        $user = User::factory()->create();
        $service = app(SnoozedMessageService::class);
        $wakeAt = CarbonImmutable::now(config('app.timezone'))->addHours(2);

        $first = $service->snooze(
            $user->id,
            'INBOX',
            123,
            '<message-123@example.test>',
            $wakeAt,
            'Quarterly review',
            'Sender <sender@example.test>'
        );

        $updated = $service->snooze(
            $user->id,
            'INBOX',
            123,
            '<message-123@example.test>',
            $wakeAt->addHour(),
            'Quarterly review',
            'Sender <sender@example.test>'
        );

        $this->assertSame($first->id, $updated->id);
        $this->assertDatabaseCount('snoozed_messages', 1);
        $this->assertSame('Quarterly review', $updated->subject);
        $this->assertSame('Sender <sender@example.test>', $updated->sender);
        $this->assertEquals($wakeAt->addHour()->timestamp, $updated->wake_at->timestamp);
    }

    public function test_snooze_rejects_invalid_locations_and_unsafe_time_windows(): void
    {
        $user = User::factory()->create();
        $service = app(SnoozedMessageService::class);
        $now = CarbonImmutable::now(config('app.timezone'));

        foreach ([
            ['', 10, $now->addHours(2)],
            ['INBOX', 0, $now->addHours(2)],
            ['INBOX', 10, $now->addSeconds(20)],
            ['INBOX', 10, $now->addYears(2)],
        ] as [$folder, $uid, $wakeAt]) {
            try {
                $service->snooze($user->id, $folder, $uid, null, $wakeAt);
                $this->fail('Invalid snooze request must be rejected.');
            } catch (\InvalidArgumentException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_snoozed_message_listing_and_return_now_are_user_scoped(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $ownerMessage = SnoozedMessage::create([
            'user_id' => $owner->id,
            'folder_path' => 'INBOX',
            'uid' => 101,
            'message_id' => '<owned@example.test>',
            'subject' => 'My snoozed message',
            'sender' => 'owner@example.test',
            'wake_at' => now()->addHour(),
            'status' => SnoozedMessage::STATUS_SNOOZED,
        ]);
        SnoozedMessage::create([
            'user_id' => $other->id,
            'folder_path' => 'INBOX',
            'uid' => 202,
            'message_id' => '<private@example.test>',
            'subject' => 'Private message subject',
            'sender' => 'private@example.test',
            'wake_at' => now()->addHour(),
            'status' => SnoozedMessage::STATUS_SNOOZED,
        ]);

        Livewire::actingAs($owner)
            ->test(SnoozedMessages::class)
            ->assertSee('My snoozed message')
            ->assertDontSee('Private message subject')
            ->call('returnNow', $ownerMessage->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('snoozed_messages', [
            'id' => $ownerMessage->id,
            'status' => SnoozedMessage::STATUS_RETURNED,
        ]);
        $this->assertDatabaseHas('snoozed_messages', [
            'user_id' => $other->id,
            'status' => SnoozedMessage::STATUS_SNOOZED,
        ]);
    }

    public function test_expired_snooze_is_no_longer_active_and_message_returns_to_mailbox_view(): void
    {
        $user = User::factory()->create();
        $message = SnoozedMessage::create([
            'user_id' => $user->id,
            'folder_path' => 'INBOX',
            'uid' => 303,
            'message_id' => '<expired@example.test>',
            'subject' => 'Returned after wake time',
            'wake_at' => now()->subMinute(),
            'status' => SnoozedMessage::STATUS_SNOOZED,
        ]);

        $active = app(SnoozedMessageService::class)->activeForFolder($user->id, 'INBOX');

        $this->assertTrue($active->isEmpty());
        $this->assertSame(SnoozedMessage::STATUS_SNOOZED, $message->fresh()->status);
    }
}
