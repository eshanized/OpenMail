<?php

namespace Tests\Feature\Composer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Livewire\Livewire;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;

class ComposerIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->mockImapService();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockImapService(): void
    {
        $mockService = Mockery::mock(\App\Services\ImapMailboxService::class);

        $mockService->shouldReceive('getCachedFolders')
            ->andReturn([
                [
                    'path' => 'INBOX',
                    'name' => 'Inbox',
                    'role' => 'inbox',
                    'total_count' => 5,
                    'unread_count' => 2,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
                [
                    'path' => 'Sent',
                    'name' => 'Sent',
                    'role' => 'sent',
                    'total_count' => 10,
                    'unread_count' => 0,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
                [
                    'path' => 'Drafts',
                    'name' => 'Drafts',
                    'role' => 'drafts',
                    'total_count' => 3,
                    'unread_count' => 0,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
            ]);

        $mockService->shouldReceive('getMessageCount')
            ->andReturn(['total_count' => 5, 'unread_count' => 2, 'uidvalidity' => 12345]);

        $mockService->shouldReceive('getFolders')
            ->andReturn([
                [
                    'path' => 'INBOX',
                    'name' => 'Inbox',
                    'role' => 'inbox',
                    'total_count' => 5,
                    'unread_count' => 2,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
                [
                    'path' => 'Sent',
                    'name' => 'Sent',
                    'role' => 'sent',
                    'total_count' => 10,
                    'unread_count' => 0,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
                [
                    'path' => 'Drafts',
                    'name' => 'Drafts',
                    'role' => 'drafts',
                    'total_count' => 3,
                    'unread_count' => 0,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
            ]);

        $mockService->shouldReceive('refreshFolderCache')
            ->andReturn(null);

        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        $this->app->instance(\App\Services\ImapMailboxService::class, $mockService);

        // Also bind the FolderMapper and MessageSanitizer
        $this->app->instance(\App\Services\FolderMapper::class, new \App\Services\FolderMapper());
        $this->app->instance(\App\Services\MessageSanitizer::class, new \App\Services\MessageSanitizer());
    }

    public function test_composer_modal_renders_when_authenticated()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $component = Livewire::test('mailbox.composer')
            ->call('openComposer', ['mode' => 'compose'])
            ->assertSet('isOpen', true)
            ->assertSet('mode', 'compose');
    }

    public function test_compose_button_is_visible_in_mailbox_view()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $response = $this->get(route('mailbox'));
        $response->assertStatus(200);
        $response->assertSee('Compose');
    }

    public function test_unauthenticated_access_redirects_to_login()
    {
        $response = $this->get(route('mailbox'));
        $response->assertRedirect(route('login'));
    }

    public function test_composer_livewire_component_mounts_correctly()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        Livewire::test('mailbox.composer')
            ->assertSet('isOpen', false)
            ->assertSet('mode', 'compose')
            ->assertSet('to', '')
            ->assertSet('subject', '')
            ->assertSet('body', '')
            ->assertSet('attachments', []);
    }

    public function test_open_composer_for_reply_populates_fields()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $messageData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'to_address' => $this->user->email,
            'cc_address' => 'cc@example.com',
            'subject' => 'Original Subject',
            'date_formatted' => 'Jan 15, 2024',
            'html_body' => '<p>Original message body</p>',
            'text_body' => 'Original message body',
        ];

        $component = Livewire::test('mailbox.composer')
            ->call('openComposer', [
                'mode' => 'reply',
                'message' => $messageData,
            ]);

        $this->assertTrue($component->get('isOpen'));
        $this->assertEquals('reply', $component->get('mode'));
        $this->assertEquals('sender@example.com', $component->get('to'));
        $this->assertStringContainsString('Re: Original Subject', $component->get('subject'));
        $this->assertStringContainsString('On Jan 15, 2024, Sender Name <sender@example.com> wrote:', $component->get('body'));
    }

    public function test_open_composer_for_reply_all_includes_cc()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $messageData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'to_address' => $this->user->email,
            'cc_address' => 'cc@example.com',
            'subject' => 'Original Subject',
            'date_formatted' => 'Jan 15, 2024',
            'html_body' => '<p>Original message body</p>',
            'text_body' => 'Original message body',
        ];

        $component = Livewire::test('mailbox.composer')
            ->call('openComposer', [
                'mode' => 'replyAll',
                'message' => $messageData,
            ]);

        $this->assertEquals('replyAll', $component->get('mode'));
        $this->assertEquals('sender@example.com', $component->get('to'));
        $this->assertStringContainsString('cc@example.com', $component->get('cc'));
    }

    public function test_open_composer_for_forward_populates_correctly()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $messageData = [
            'message_id' => '<test@example.com>',
            'from_email' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'to_address' => $this->user->email,
            'cc_address' => 'cc@example.com',
            'subject' => 'Original Subject',
            'date_formatted' => 'Jan 15, 2024',
            'html_body' => '<p>Original message body</p>',
            'text_body' => 'Original message body',
        ];

        $component = Livewire::test('mailbox.composer')
            ->call('openComposer', [
                'mode' => 'forward',
                'message' => $messageData,
            ]);

        $this->assertEquals('forward', $component->get('mode'));
        $this->assertEquals('', $component->get('to'));
        $this->assertStringContainsString('Fwd: Original Subject', $component->get('subject'));
        $this->assertStringContainsString('-------- Forwarded message --------', $component->get('body'));
        $this->assertStringContainsString('On Jan 15, 2024, Sender Name <sender@example.com> wrote:', $component->get('body'));
    }

    public function test_composer_validation_requires_to_subject_body()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        Livewire::test('mailbox.composer')
            ->call('openComposer', ['mode' => 'compose'])
            ->set('to', 'invalid-email')
            ->set('subject', '')
            ->set('body', '')
            ->call('send')
            ->assertHasErrors(['to', 'subject', 'body']);
    }

    public function test_composer_validation_passes_with_valid_data()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        $component = Livewire::test('mailbox.composer')
            ->call('openComposer', ['mode' => 'compose'])
            ->set('to', 'recipient@example.com')
            ->set('subject', 'Valid Subject')
            ->set('body', '<p>Valid body</p>')
            ->call('send');

        // Should not have validation errors for these fields
        $component->assertHasNoErrors('to');
        $component->assertHasNoErrors('subject');
        $component->assertHasNoErrors('body');
    }

    public function test_discard_draft_clears_form()
    {
        $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')]);

        Livewire::test('mailbox.composer')
            ->call('openComposer', ['mode' => 'compose'])
            ->set('to', 'recipient@example.com')
            ->set('subject', 'Test Subject')
            ->set('body', '<p>Test body</p>')
            ->call('discardDraft')
            ->assertSet('isOpen', false)
            ->assertSet('to', '')
            ->assertSet('subject', '')
            ->assertSet('body', '');
    }

    public function test_attachment_validation_enforces_size_limits()
    {
        $this->markTestSkipped('Disk quota exceeded in test environment');
    }
}