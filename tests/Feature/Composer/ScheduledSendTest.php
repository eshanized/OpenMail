<?php

declare(strict_types=1);

namespace Tests\Feature\Composer;

use App\Livewire\Mailbox\ScheduledSends;
use App\Models\PendingSend;
use App\Models\User;
use App\Services\ComposerService;
use App\Services\ImapMailboxService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ScheduledSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_message_persists_for_future_delivery_without_immediate_smtp_attempt(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $scheduledAt = CarbonImmutable::now(config('app.timezone'))->addHours(3)->startOfMinute();
        $result = app(ComposerService::class)->scheduleMessage($user->id, [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Quarterly review',
            'body' => '<p>Meeting notes attached.</p>',
            'attachments' => [],
        ], $scheduledAt);

        $send = PendingSend::query()->findOrFail($result['pending_send_id']);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['queued']);
        $this->assertTrue($result['scheduled']);
        $this->assertSame(PendingSend::STATUS_PENDING, $send->status);
        $this->assertTrue($send->is_scheduled);
        $this->assertSame($user->id, $send->user_id);
        $this->assertEquals($scheduledAt->timestamp, $send->send_at->timestamp);
    }

    public function test_scheduled_mime_keeps_attachments_after_temporary_upload_file_is_removed(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $path = tempnam(sys_get_temp_dir(), 'openmail-scheduled-');
        $this->assertNotFalse($path);
        file_put_contents($path, 'attachment content that must survive until scheduled delivery');

        try {
            $result = app(ComposerService::class)->scheduleMessage($user->id, [
                'to' => 'recipient@example.com',
                'subject' => 'Persistent attachment',
                'body' => '<p>See attached.</p>',
                'attachments' => [[
                    'path' => $path,
                    'name' => 'report.txt',
                    'mime' => 'text/plain',
                    'size' => filesize($path),
                ]],
            ], CarbonImmutable::now(config('app.timezone'))->addHours(2));

            $send = PendingSend::query()->findOrFail($result['pending_send_id']);
            $this->assertNotEmpty($send->mime_message);

            // A scheduled send must not depend on the temporary upload existing later.
            unlink($path);
            $restoredEmail = \\Symfony\\Component\\Mime\\Email::fromString($send->mime_message);

            $this->assertCount(1, $restoredEmail->getAttachments());
            $this->assertSame('report.txt', $restoredEmail->getAttachments()[0]->getFilename());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_schedule_message_rejects_times_that_are_too_soon_or_too_far_away(): void
    {
        $user = User::factory()->create();
        $service = app(ComposerService::class);
        $data = [
            'to' => 'recipient@example.com',
            'subject' => 'Schedule boundary test',
            'body' => 'hello',
            'attachments' => [],
        ];

        try {
            $service->scheduleMessage($user->id, $data, CarbonImmutable::now(config('app.timezone'))->addSeconds(30));
            $this->fail('A schedule less than one minute away must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('at least one minute', $exception->getMessage());
        }

        try {
            $service->scheduleMessage($user->id, $data, CarbonImmutable::now(config('app.timezone'))->addYears(2));
            $this->fail('A schedule more than one year away must be rejected.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('one year', $exception->getMessage());
        }
    }

    public function test_scheduled_messages_are_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        PendingSend::create([
            'user_id' => $owner->id,
            'idempotency_key' => 'schedule-owner',
            'message_json' => ['to' => 'visible@example.com', 'subject' => 'My scheduled subject'],
            'mime_message' => 'mime-owner',
            'send_at' => now()->addHour(),
            'is_scheduled' => true,
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
        ]);

        PendingSend::create([
            'user_id' => $otherUser->id,
            'idempotency_key' => 'schedule-other',
            'message_json' => ['to' => 'private@example.com', 'subject' => 'Other users private subject'],
            'mime_message' => 'mime-other',
            'send_at' => now()->addHour(),
            'is_scheduled' => true,
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
        ]);

        Livewire::actingAs($owner)
            ->test(ScheduledSends::class)
            ->assertSee('My scheduled subject')
            ->assertDontSee('Other users private subject');
    }

    public function test_cancelling_a_scheduled_send_marks_it_cancelled_and_preserves_a_draft(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $send = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'schedule-cancel',
            'message_json' => ['to' => 'recipient@example.com', 'subject' => 'Cancelable message'],
            'mime_message' => 'canonical mime for draft',
            'send_at' => now()->addHour(),
            'is_scheduled' => true,
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
        ]);

        $imap = \Mockery::mock(ImapMailboxService::class);
        $imap->shouldReceive('appendToDrafts')
            ->once()
            ->with('canonical mime for draft')
            ->andReturn('draft-uid-1');
        $this->app->instance(ImapMailboxService::class, $imap);

        Livewire::actingAs($user)
            ->test(ScheduledSends::class)
            ->call('cancelScheduled', $send->id)
            ->assertDispatched('toast');

        $this->assertSame(PendingSend::STATUS_CANCELLED, $send->fresh()->status);
    }
}
