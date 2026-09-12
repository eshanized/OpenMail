<?php

namespace Tests\Unit\Services;

use App\Livewire\Mailbox\Composer;
use App\Models\User;
use App\Services\ComposerService;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class ComposerSendTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_parse_append_uid_extracts_uid_from_appenduid_response(): void
    {
        $service = new ImapMailboxService();
        $method = new ReflectionMethod($service, 'parseAppendUid');
        $method->setAccessible(true);

        // IMAP server with UIDPLUS
        $response = ['OK [APPENDUID 12345 789] APPEND completed'];
        $this->assertEquals('789', $method->invoke($service, $response));

        // String response
        $this->assertEquals('999', $method->invoke($service, 'OK [APPENDUID 1 999] APPEND completed'));

        // IMAP server without UIDPLUS (fallback)
        $this->assertEquals('appended', $method->invoke($service, ['OK APPEND completed']));

        // Array with 'uid' key (mocked or legacy format)
        $this->assertEquals('555', $method->invoke($service, ['uid' => 555]));
    }

    public function test_composer_accepts_multiple_and_formatted_recipients(): void
    {
        $user = User::factory()->create();

        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('getCachedFolders')->andReturn([]);
        $mockImap->shouldReceive('refreshFolderCache')->andReturn(null);
        $mockImap->shouldReceive('appendToSent')->andReturn('sent-uid-123');
        $this->app->instance(ImapMailboxService::class, $mockImap);

        $this->actingAs($user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $component = Livewire::test(Composer::class)
            ->call('openComposer', ['mode' => 'compose'])
            ->set('to', 'Jane Doe <jane@example.com>, bob@example.com')
            ->set('cc', 'Charlie <charlie@example.com>')
            ->set('bcc', 'david@example.com')
            ->set('subject', 'Test Subject')
            ->set('body', '<p>Test email body</p>')
            ->call('send');

        $component->assertHasNoErrors(['to', 'cc', 'bcc', 'subject', 'body']);
    }

    public function test_composer_fails_validation_for_invalid_recipient_format(): void
    {
        $user = User::factory()->create();

        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('getCachedFolders')->andReturn([]);
        $this->app->instance(ImapMailboxService::class, $mockImap);

        $this->actingAs($user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $component = Livewire::test(Composer::class)
            ->call('openComposer', ['mode' => 'compose'])
            ->set('to', 'not-an-email, Jane <not-an-email-either>')
            ->set('subject', 'Test Subject')
            ->set('body', '<p>Test email body</p>')
            ->call('send');

        $component->assertHasErrors(['to']);
    }

    public function test_composer_service_sends_via_mail_raw_without_method_or_header_errors(): void
    {
        config(['mail.default' => 'array']);

        $user = User::factory()->create([
            'name' => 'Sender User',
            'email' => 'sender@example.com',
        ]);

        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('appendToSent')
            ->once()
            ->andReturn('12345');

        $sanitizer = new MessageSanitizer();
        $service = new ComposerService($mockImap, $sanitizer);

        $data = [
            'to' => 'Jane <jane@example.com>, bob@example.com',
            'cc' => 'cc@example.com',
            'bcc' => 'bcc@example.com',
            'subject' => 'Integration Test Subject',
            'body' => '<p>Hello world body</p>',
            'attachments' => [],
        ];

        $result = $service->sendMessage($user->id, $data);

        $this->assertTrue($result['success']);
        $this->assertEquals('12345', $result['sent_uid']);
        $this->assertFalse($result['queued']);
    }
}
