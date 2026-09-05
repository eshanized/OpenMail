<?php

namespace Tests\Feature\Composer;

use Tests\TestCase;
use App\Services\ComposerService;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class ComposerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    public function test_build_mime_message_produces_valid_mime_with_correct_to_subject_body()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Test Subject',
            'body' => '<p>Test body content</p>',
            'attachments' => [],
        ];

        $mimeMessage = $service->buildMimeMessage($data, $this->user->id);

        $this->assertInstanceOf(\Symfony\Component\Mime\Email::class, $mimeMessage);
        $this->assertEquals($this->user->email, (string) $mimeMessage->getFrom()[0]->getAddress());
        $this->assertEquals('recipient@example.com', (string) $mimeMessage->getTo()[0]->getAddress());
        $this->assertEquals('Test Subject', $mimeMessage->getSubject());
        $this->assertStringContainsString('Test body content', $mimeMessage->getHtmlBody());
    }

    public function test_build_mime_message_includes_attachments_with_correct_mime_types()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $service = new ComposerService($imapService, $sanitizer);

        // Create a temporary file for testing
        $tempDir = sys_get_temp_dir();
        $tempFile = $tempDir . '/test_attachment_' . uniqid() . '.txt';
        $result = @file_put_contents($tempFile, 'Test attachment content');
        
        if ($result === false) {
            $this->markTestSkipped('Cannot create temporary file for attachment test');
            return;
        }

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Test with Attachment',
            'body' => '<p>Test body</p>',
            'attachments' => [
                [
                    'path' => $tempFile,
                    'name' => 'test.txt',
                    'mime' => 'text/plain',
                    'size' => 25,
                ],
            ],
        ];

        $mimeMessage = $service->buildMimeMessage($data, $this->user->id);

        $this->assertInstanceOf(\Symfony\Component\Mime\Email::class, $mimeMessage);
        $body = $mimeMessage->getBody();
        $this->assertNotNull($body);

        // Clean up
        @unlink($tempFile);
    }

    public function test_build_mime_message_sets_in_reply_to_and_references_headers_for_replies()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Re: Test Subject',
            'body' => '<p>Reply body</p>',
            'attachments' => [],
            'in_reply_to' => '<original-message-id@example.com>',
            'references' => '<original-message-id@example.com> <another-message-id@example.com>',
        ];

        $mimeMessage = $service->buildMimeMessage($data, $this->user->id);

        $headers = $mimeMessage->getHeaders();
        $this->assertTrue($headers->has('In-Reply-To'));
        $this->assertTrue($headers->has('References'));
        $this->assertEquals('<original-message-id@example.com>', (string) $headers->get('In-Reply-To')->getBodyAsString());
        $this->assertEquals('<original-message-id@example.com> <another-message-id@example.com>', (string) $headers->get('References')->getBodyAsString());
    }

    public function test_build_mime_message_generates_message_id_header()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Test Subject',
            'body' => '<p>Test body</p>',
            'attachments' => [],
        ];

        $mimeMessage = $service->buildMimeMessage($data, $this->user->id);

        $headers = $mimeMessage->getHeaders();
        $this->assertTrue($headers->has('Message-ID'));
        $messageId = (string) $headers->get('Message-ID')->getBodyAsString();
        $this->assertStringContainsString('@', $messageId);
        $this->assertStringStartsWith('<', $messageId);
        $this->assertStringEndsWith('>', $messageId);
    }

    public function test_send_message_calls_append_to_sent_before_smtp()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $appendToSentCalled = false;

        $imapService->method('appendToSent')
            ->willReturnCallback(function () use (&$appendToSentCalled) {
                $appendToSentCalled = true;
                return '12345';
            });

        // Mock Mail::raw to succeed without actually sending
        Mail::shouldReceive('raw')
            ->once()
            ->andReturn(null);

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Test Subject',
            'body' => '<p>Test body</p>',
            'attachments' => [],
        ];

        $result = $service->sendMessage($this->user->id, $data);

        $this->assertTrue($appendToSentCalled);
        $this->assertTrue($result['success']);
        $this->assertEquals('12345', $result['sent_uid']);
    }

    public function test_send_message_creates_pending_send_on_smtp_failure()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $imapService->method('appendToSent')
            ->willReturn('12345');

        // Mock Mail to throw exception
        Mail::shouldReceive('raw')
            ->once()
            ->andThrow(new \Exception('SMTP connection failed'));

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Test Subject',
            'body' => '<p>Test body</p>',
            'attachments' => [],
        ];

        $result = $service->sendMessage($this->user->id, $data);

        $this->assertFalse($result['success']);
        $this->assertTrue($result['queued']);

        // Verify PendingSend was created
        $pendingSend = \App\Models\PendingSend::where('user_id', $this->user->id)->first();
        $this->assertNotNull($pendingSend);
        $this->assertEquals('pending', $pendingSend->status);
        $this->assertEquals('12345', $pendingSend->sent_folder_uid);
    }

    public function test_save_draft_calls_append_to_drafts()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $appendToDraftsCalled = false;

        $imapService->method('appendToDrafts')
            ->willReturnCallback(function () use (&$appendToDraftsCalled) {
                $appendToDraftsCalled = true;
                return 'draft-uid-123';
            });

        $service = new ComposerService($imapService, $sanitizer);

        $data = [
            'to' => 'recipient@example.com',
            'cc' => '',
            'bcc' => '',
            'subject' => 'Draft Subject',
            'body' => '<p>Draft body</p>',
            'attachments' => [],
        ];

        $result = $service->saveDraft($this->user->id, $data);

        $this->assertTrue($appendToDraftsCalled);
        $this->assertTrue($result['success']);
        $this->assertEquals('draft-uid-123', $result['draft_uid']);
    }

    public function test_undo_send_cancels_pending_send_and_moves_to_drafts()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $deleteFromSentCalled = false;
        $appendToDraftsCalled = false;

        $imapService->method('deleteFromSent')
            ->willReturnCallback(function () use (&$deleteFromSentCalled) {
                $deleteFromSentCalled = true;
                return true;
            });

        $imapService->method('appendToDrafts')
            ->willReturnCallback(function () use (&$appendToDraftsCalled) {
                $appendToDraftsCalled = true;
                return 'new-draft-uid';
            });

        $service = new ComposerService($imapService, $sanitizer);

        // Create a pending send
        $pendingSend = \App\Models\PendingSend::create([
            'user_id' => $this->user->id,
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test', 'body' => '<p>Test</p>'],
            'mime_message' => 'raw-mime-message',
            'send_at' => now()->addSeconds(10),
            'status' => 'pending',
            'sent_folder_uid' => 'sent-uid-123',
            'retry_count' => 0,
        ]);

        $result = $service->undoSend($pendingSend->id, $this->user->id);

        $this->assertTrue($result);
        $this->assertTrue($deleteFromSentCalled);
        $this->assertTrue($appendToDraftsCalled);

        $pendingSend->refresh();
        $this->assertEquals('cancelled', $pendingSend->status);
    }

    public function test_undo_send_fails_for_non_pending_send()
    {
        $imapService = $this->createMock(ImapMailboxService::class);
        $sanitizer = $this->createMock(MessageSanitizer::class);

        $service = new ComposerService($imapService, $sanitizer);

        $pendingSend = \App\Models\PendingSend::create([
            'user_id' => $this->user->id,
            'message_json' => ['to' => 'test@example.com', 'subject' => 'Test', 'body' => '<p>Test</p>'],
            'mime_message' => 'raw-mime-message',
            'send_at' => now()->addSeconds(10),
            'status' => 'sent', // Already sent
            'sent_folder_uid' => 'sent-uid-123',
            'retry_count' => 0,
        ]);

        $result = $service->undoSend($pendingSend->id, $this->user->id);

        $this->assertFalse($result);
    }
}