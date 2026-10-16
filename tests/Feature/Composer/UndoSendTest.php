<?php

namespace Tests\Feature\Composer;

use App\Models\PendingSend;
use App\Models\User;
use App\Services\ComposerService;
use App\Services\ImapMailboxService;
use App\Services\MailTransportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UndoSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_sends_record_created_on_send_with_correct_send_at_timestamp()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $composerService = app(ComposerService::class);
        $result = $composerService->prepareSend($user->id, [
            'to' => 'test@example.com',
            'subject' => 'Test Send',
            'body' => '<p>Test content</p>',
            'attachments' => [],
        ], 10);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['delayed']);
        $this->assertNotNull($result['pending_send_id']);
        $this->assertEquals(10, $result['delay_seconds']);

        // Verify pending_sends record
        $pendingSend = PendingSend::find($result['pending_send_id']);
        $this->assertNotNull($pendingSend);
        $this->assertEquals($user->id, $pendingSend->user_id);
        $this->assertEquals(PendingSend::STATUS_PENDING, $pendingSend->status);
        $this->assertNotNull($pendingSend->send_at);
        $this->assertTrue($pendingSend->send_at->greaterThanOrEqualTo(now()));
        $this->assertTrue($pendingSend->send_at->lessThanOrEqualTo(now()->addSeconds(15)));
    }

    public function test_undo_send_cancels_pending_send_and_updates_status()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-1',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test'],
            'mime_message' => 'test mime message',
            'send_at' => now()->addSeconds(10),
            'status' => PendingSend::STATUS_PENDING,
            'sent_folder_uid' => 'sent-uid-123',
            'retry_count' => 0,
        ]);

        $mockImapService = \Mockery::mock(ImapMailboxService::class);
        $mockImapService->shouldReceive('deleteFromSent')
            ->with('sent-uid-123')
            ->once()
            ->andReturn(true);
        $mockImapService->shouldReceive('appendToDrafts')
            ->once()
            ->andReturn('draft-uid-456');

        $this->app->instance(ImapMailboxService::class, $mockImapService);

        $composerService = app(ComposerService::class);
        $result = $composerService->undoSend($pendingSend->id, $user->id);

        $this->assertTrue($result);

        $pendingSend->refresh();
        $this->assertEquals(PendingSend::STATUS_CANCELLED, $pendingSend->status);
    }

    public function test_undo_send_cannot_cancel_already_claimed_or_sent_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-sent',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test'],
            'mime_message' => 'test mime message',
            'send_at' => now()->subSeconds(10),
            'status' => PendingSend::STATUS_SENT,
            'retry_count' => 0,
        ]);

        $composerService = app(ComposerService::class);
        $result = $composerService->undoSend($pendingSend->id, $user->id);

        $this->assertFalse($result);
        $pendingSend->refresh();
        $this->assertEquals(PendingSend::STATUS_SENT, $pendingSend->status);
    }

    public function test_concurrency_safe_atomic_claim_prevents_duplicate_workers()
    {
        $user = User::factory()->create();

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-claim-race',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test'],
            'mime_message' => 'test mime message',
            'send_at' => now()->subSeconds(5),
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
        ]);

        // First worker claims record
        $claimedFirst = $pendingSend->atomicClaim('worker-token-A');
        $this->assertTrue($claimedFirst);
        $this->assertEquals('worker-token-A', $pendingSend->claim_token);

        // Second worker tries to claim the same record
        $claimedSecond = $pendingSend->atomicClaim('worker-token-B');
        $this->assertFalse($claimedSecond);
        $this->assertEquals('worker-token-A', $pendingSend->claim_token);
    }

    public function test_process_pending_sends_processes_due_records()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-due',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test', 'body' => '<p>Test</p>'],
            'mime_message' => 'test mime message',
            'send_at' => now()->subSeconds(5), // Already due
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 0,
        ]);

        $mockTransport = \Mockery::mock(MailTransportService::class);
        $mockTransport->shouldReceive('sendMime')
            ->once()
            ->andReturn([
                'success' => true,
                'category' => PendingSend::CATEGORY_NONE,
                'message_id' => '<sent-123@example.com>',
            ]);
        $this->app->instance(MailTransportService::class, $mockTransport);

        $mockImapService = \Mockery::mock(ImapMailboxService::class);
        $mockImapService->shouldReceive('appendToSent')
            ->once()
            ->andReturn('sent-uid-789');
        $this->app->instance(ImapMailboxService::class, $mockImapService);

        $this->artisan('pending-sends:process')
            ->assertExitCode(0);

        $pendingSend->refresh();
        $this->assertEquals(PendingSend::STATUS_SENT, $pendingSend->status);
        $this->assertTrue($pendingSend->smtp_accepted);
        $this->assertTrue($pendingSend->sent_folder_synced);
    }

    public function test_process_pending_sends_retries_failed_sends_with_backoff()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-retry',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test', 'body' => '<p>Test</p>'],
            'mime_message' => 'test mime message',
            'send_at' => now()->subSeconds(5),
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 1,
            'max_retries' => 3,
        ]);

        $mockTransport = \Mockery::mock(MailTransportService::class);
        $mockTransport->shouldReceive('sendMime')
            ->once()
            ->andReturn([
                'success' => false,
                'category' => PendingSend::CATEGORY_TRANSPORT,
                'error' => 'Connection timed out',
            ]);
        $this->app->instance(MailTransportService::class, $mockTransport);

        $this->artisan('pending-sends:process')
            ->assertExitCode(0);

        $pendingSend->refresh();
        $this->assertEquals(2, $pendingSend->retry_count);
        $this->assertEquals(PendingSend::STATUS_RETRY_WAITING, $pendingSend->status);
        $this->assertTrue($pendingSend->send_at->greaterThan(now()));
    }

    public function test_process_pending_sends_marks_as_failed_after_max_retries()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $pendingSend = PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'idemp-fail-max',
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test', 'body' => '<p>Test</p>'],
            'mime_message' => 'test mime message',
            'send_at' => now()->subSeconds(5),
            'status' => PendingSend::STATUS_PENDING,
            'retry_count' => 2,
            'max_retries' => 3,
        ]);

        $mockTransport = \Mockery::mock(MailTransportService::class);
        $mockTransport->shouldReceive('sendMime')
            ->once()
            ->andReturn([
                'success' => false,
                'category' => PendingSend::CATEGORY_TRANSPORT,
                'error' => 'Permanent connection reset',
            ]);
        $this->app->instance(MailTransportService::class, $mockTransport);

        $this->artisan('pending-sends:process')
            ->assertExitCode(0);

        $pendingSend->refresh();
        $this->assertEquals(3, $pendingSend->retry_count);
        $this->assertEquals(PendingSend::STATUS_FAILED, $pendingSend->status);
    }
}
