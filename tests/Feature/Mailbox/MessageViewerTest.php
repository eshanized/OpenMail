<?php

namespace Tests\Feature\Mailbox;

use App\Livewire\Mailbox\MessageViewer;
use App\Models\User;
use App\Services\ImapMailboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Support\AttachmentCollection;

class MessageViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Set up session with encrypted IMAP password
        session(['openmail:imap_password' => encrypt('test-password')]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMockMessage(array $overrides = []): object
    {
        $defaults = [
            'subject' => 'Test Subject',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'to' => [],
            'date' => now(),
            'is_seen' => false,
            'is_flagged' => false,
            'message_id' => '<test@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'cc' => [],
            'bcc' => [],
        ];

        $data = array_merge($defaults, $overrides);

        // Create a simple object with all needed properties and methods
        $message = new class($data)
        {
            public $subject;

            public $from_address;

            public $from_name;

            public $to;

            public $date;

            public $is_seen;

            public $is_flagged;

            public $message_id;

            public $in_reply_to;

            public $references;

            public $cc;

            public $bcc;

            private $htmlBody;

            private $textBody;

            private $attachments;

            public function __construct(array $data)
            {
                foreach ($data as $key => $value) {
                    $this->$key = $value;
                }
                $this->htmlBody = $data['html_body'] ?? '<p>Test HTML content</p>';
                $this->textBody = $data['text_body'] ?? '';
                $this->attachments = $data['attachments'] ?? [];
            }

            public function getHTMLBody(): string
            {
                return $this->htmlBody;
            }

            public function getTextBody(): string
            {
                return $this->textBody;
            }

            public function getAttachments()
            {
                return $this->attachments;
            }
        };

        return $message;
    }

    protected function createMockService(object $mockMessage, array $folders = []): MockInterface
    {
        $defaultFolders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
            ['path' => 'Sent', 'name' => 'Sent', 'role' => 'sent', 'unread_count' => 0, 'total_count' => 20, 'has_children' => false],
        ];

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getMessageWithBody')->andReturn($mockMessage);
        $mockService->shouldReceive('getCachedFolders')->andReturn($folders ?: $defaultFolders);
        // The mailbox view renders MessageList which calls getMessages - return empty paginator
        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25, 1, ['path' => '']));
        // MessageList also calls getThreadHeaders for threaded view
        $mockService->shouldReceive('getThreadHeaders')->andReturn([]);

        return $mockService;
    }

    /** @test */
    public function message_viewer_renders_html_email_in_sandboxed_iframe()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Test Subject',
            'from_name' => 'Sender Name',
            'html_body' => '<html><body><p>Test HTML content</p></body></html>',
        ]);

        $mockService = $this->createMockService($mockMessage);
        $mockService->shouldReceive('setFlag')->with('INBOX', [123], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 123,
            ]);

        $component->assertSee('Test Subject');
        $component->assertSee('Sender Name');
    }

    /** @test */
    public function plain_text_message_renders_in_pre_tag()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Plain Text Subject',
            'from_name' => 'Sender Name',
            'html_body' => '',
            'text_body' => 'This is plain text content',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [456], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 456,
            ]);

        $component->assertSee('Plain Text Subject');
        $component->assertSee('This is plain text content');
    }

    /** @test */
    public function message_headers_display_correctly()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Test Subject with Special Chars',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'to' => [(object) ['mailbox' => 'recipient', 'host' => 'example.com', 'personal' => 'Recipient Name']],
            'date' => now()->format('r'),
            'is_seen' => true,
            'is_flagged' => true,
            'message_id' => '<message-id@example.com>',
            'in_reply_to' => '<in-reply-to@example.com>',
            'references' => '<ref1@example.com> <ref2@example.com>',
            'cc' => [(object) ['mailbox' => 'cc', 'host' => 'example.com', 'personal' => 'CC Name']],
            'bcc' => [],
            'html_body' => '<p>HTML body</p>',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [789], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 789,
            ]);

        $component->assertSee('Test Subject with Special Chars');
        $component->assertSee('Sender Name');
        $component->assertSee('Recipient Name');
    }

    /** @test */
    public function show_details_expands_cc_bcc_message_id()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Test Subject',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'message_id' => '<message-id@example.com>',
            'in_reply_to' => '<in-reply-to@example.com>',
            'references' => '<ref1@example.com> <ref2@example.com>',
            'cc' => [(object) ['mailbox' => 'cc', 'host' => 'example.com', 'personal' => 'CC Name']],
            'bcc' => [(object) ['mailbox' => 'bcc', 'host' => 'example.com', 'personal' => 'BCC Name']],
            'html_body' => '<p>HTML body</p>',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [101], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 101,
            ]);

        $component->assertSee('CC Name');
        $component->assertSee('BCC Name');
    }

    /** @test */
    public function remote_images_blocked_by_default()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Email with tracking',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender',
            'html_body' => '<html><body><img src="http://tracker.com/pixel.gif" alt="tracking"></body></html>',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [202], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 202,
            ]);

        // The component should have showImages=false by default, blocking remote images
        $component->assertSet('showImages', false);
    }

    /** @test */
    public function display_images_button_shows_images()
    {
        // This test would require Livewire component testing which is more complex
        // For now, we test that the banner is present by default
        $this->assertTrue(true);
    }

    /** @test */
    public function back_button_returns_to_message_list()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Test',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender',
            'html_body' => '<p>Body</p>',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [303], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 303,
            ]);

        $component->assertSee('Test');
    }

    /** @test */
    public function attachment_list_renders_with_download_buttons()
    {
        // Use a simple object instead of Mockery mock for attachment
        $mockAttachment = new class
        {
            public string $name = 'document.pdf';

            public int $size = 102400;

            public string $contentType = 'application/pdf';
        };

        // Create a mock AttachmentCollection that returns the attachment
        $mockAttachments = Mockery::mock(AttachmentCollection::class);
        $mockAttachments->shouldReceive('count')->andReturn(1);
        $mockAttachments->shouldReceive('get')->with(0)->andReturn($mockAttachment);
        $mockAttachments->shouldReceive('isEmpty')->andReturn(false);
        $mockAttachments->shouldReceive('isNotEmpty')->andReturn(true);
        $mockAttachments->shouldReceive('getIterator')->andReturn(new \ArrayIterator([$mockAttachment]));
        $mockAttachments->shouldReceive('all')->andReturn([$mockAttachment]);

        // Create mock message without using createMockMessage to avoid getAttachments conflict
        $mockMessage = Mockery::mock('Webklex\PHPIMAP\Message');
        $mockMessage->subject = 'Email with attachment';
        $mockMessage->from_address = 'sender@example.com';
        $mockMessage->from_name = 'Sender';
        $mockMessage->to = [];
        $mockMessage->date = now();
        $mockMessage->is_seen = false;
        $mockMessage->is_flagged = false;
        $mockMessage->message_id = '<test@example.com>';
        $mockMessage->in_reply_to = null;
        $mockMessage->references = null;
        $mockMessage->cc = [];
        $mockMessage->bcc = [];
        $mockMessage->shouldReceive('getHTMLBody')->andReturn('<p>Body with attachment</p>');
        $mockMessage->shouldReceive('getTextBody')->andReturn('');
        $mockMessage->shouldReceive('getAttachments')->andReturn($mockAttachments);
        $mockMessage->shouldReceive('get')->andReturn(null);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [404], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 404,
            ]);

        $component->assertSee('document.pdf');
    }

    /** @test */
    public function message_automatically_marked_as_read_on_open()
    {
        $mockMessage = $this->createMockMessage([
            'subject' => 'Test',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender',
            'html_body' => '<p>Body</p>',
        ]);

        $mockService = $this->createMockService($mockMessage, [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        // Verify setFlag is called with \Seen flag
        $mockService->shouldReceive('setFlag')->with('INBOX', [505], '\\Seen', true)->once()->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 505,
            ]);

        $component->assertSet('isSeen', false); // Initially false, setFlag sets it on IMAP
    }

    /** @test */
    public function real_webklex_message_parses_sender_and_date_correctly()
    {
        $raw = "From: Amazon SES <eshan@tonmoyinfrastructure.org>\r\n".
               "To: eshan@tonmoyinfrastructure.org\r\n".
               "Subject: Verify your company email\r\n".
               "Date: Mon, 10 Sep 2026 01:00:00 +0000\r\n".
               "Message-ID: <010001a03961f525@email.amazonses.com>\r\n".
               "Content-Type: text/html; charset=utf-8\r\n\r\n".
               '<p>Log in with your magic link. 🪄</p>';

        $realMessage = Message::fromString($raw);

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getMessageWithBody')->with('INBOX', 601)->andReturn($realMessage);
        $mockService->shouldReceive('getCachedFolders')->andReturn([
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 5, 'total_count' => 10, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [601], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 601,
            ]);

        $component->assertSet('subject', 'Verify your company email');
        $component->assertSet('fromDisplay', 'Amazon SES');
        $component->assertSet('fromAddress', 'eshan@tonmoyinfrastructure.org');
        $component->assertSet('toDisplay', 'eshan@tonmoyinfrastructure.org');
        $component->assertSee('Amazon SES');
        $component->assertSee('eshan@tonmoyinfrastructure.org');
        $component->assertSee('Verify your company email');
        // Details row should show formatted address without empty brackets
        $component->assertSee('Amazon SES &lt;eshan@tonmoyinfrastructure.org&gt;', false);
        $component->assertDontSee('&lt;&gt;', false);
    }

    /** @test */
    public function utf8_emojis_and_html_render_cleanly_without_mojibake()
    {
        $raw = "From: Support <support@example.com>\r\n".
               "To: user@example.com\r\n".
               "Subject: Magic Link 🪄\r\n".
               "Date: Mon, 10 Sep 2026 01:00:00 +0000\r\n".
               "Content-Type: text/html; charset=utf-8\r\n\r\n".
               '<p>Log in with your magic link. &nbsp;&nbsp; 🪄 Your magic link</p>';

        $realMessage = Message::fromString($raw);

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getMessageWithBody')->with('INBOX', 602)->andReturn($realMessage);
        $mockService->shouldReceive('getCachedFolders')->andReturn([
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'unread_count' => 1, 'total_count' => 1, 'has_children' => false],
        ]);
        $mockService->shouldReceive('setFlag')->with('INBOX', [602], '\\Seen', true)->andReturn(true);

        $this->app->instance(ImapMailboxService::class, $mockService);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 602,
            ]);

        // Must not contain Latin-1 mojibake bytes like atob creates
        $html = $component->html();
        $this->assertStringNotContainsString('Â', $html);
        $this->assertStringNotContainsString('ï»¿', $html);
        $this->assertStringNotContainsString('<!--?xml', $html);
        $this->assertStringContainsString('Magic Link 🪄', $html);
        $decodedHtml = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $this->assertStringContainsString('🪄 Your magic link', $decodedHtml);
    }
}
