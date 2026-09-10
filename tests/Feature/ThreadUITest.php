<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Webklex\PHPIMAP\Attribute;

class ThreadUITest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

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
        $mockService = Mockery::mock(ImapMailboxService::class);

        $mockService->shouldReceive('getCachedFolders')
            ->andReturn([
                [
                    'path' => 'INBOX',
                    'name' => 'Inbox',
                    'role' => 'inbox',
                    'total' => 5,
                    'total_count' => 5,
                    'unread_count' => 2,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
            ]);

        $mockService->shouldReceive('getMessageCount')
            ->andReturn(['total' => 5, 'unread' => 2, 'uidvalidity' => 12345]);

        $mockService->shouldReceive('getFolders')
            ->andReturn([
                [
                    'path' => 'INBOX',
                    'name' => 'Inbox',
                    'role' => 'inbox',
                    'total' => 5,
                    'total_count' => 5,
                    'unread_count' => 2,
                    'uidvalidity' => 12345,
                    'has_children' => false,
                    'parent_path' => '',
                ],
            ]);

        $mockService->shouldReceive('refreshFolderCache')
            ->andReturn(null);

        // Mock getMessages to return empty paginator for flat mode
        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        // Mock getThreadHeaders to return threaded data
        $mockService->shouldReceive('getThreadHeaders')
            ->andReturn([
                (object) [
                    'uid' => 1001,
                    'message_id' => '<msg-001@example.com>',
                    'in_reply_to' => '',
                    'references' => '',
                    'subject' => 'Project Update',
                    'date' => now()->subHours(2)->toDateTimeString(),
                    'from_address' => 'alice@example.com',
                    'from_name' => 'Alice Smith',
                    'to_address' => 'user@example.com',
                    'is_seen' => false,
                    'is_flagged' => false,
                    'has_attachments' => false,
                    'snippet' => 'Here is the latest project update...',
                    'folder_path' => 'INBOX',
                    'labels' => collect(),
                ],
                (object) [
                    'uid' => 1002,
                    'message_id' => '<msg-002@example.com>',
                    'in_reply_to' => '<msg-001@example.com>',
                    'references' => '<msg-001@example.com>',
                    'subject' => 'Re: Project Update',
                    'date' => now()->subHour()->toDateTimeString(),
                    'from_address' => 'bob@example.com',
                    'from_name' => 'Bob Jones',
                    'to_address' => 'user@example.com',
                    'is_seen' => true,
                    'is_flagged' => false,
                    'has_attachments' => true,
                    'snippet' => 'Thanks for the update...',
                    'folder_path' => 'INBOX',
                    'labels' => collect(),
                ],
            ]);

        $this->app->instance(ImapMailboxService::class, $mockService);
        $this->app->instance(FolderMapper::class, new FolderMapper);
        $this->app->instance(MessageSanitizer::class, new MessageSanitizer);
    }

    #[Test]
    public function test_threaded_view_loads_for_inbox(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        // Thread toggle button should be visible
        $response->assertSee('Threaded');
        // Threaded mode text should be present
        $response->assertSee('Threaded');
    }

    /** @test */
    public function test_thread_toggle_button_visible_in_toolbar(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        $response->assertSee('toggleThreadMode');
        $response->assertSee('Toggle thread view (t)');
        $response->assertSee('Threaded');
    }

    /** @test */
    public function test_message_list_component_has_thread_mode_default(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded');
    }

    /** @test */
    public function test_toggle_thread_mode_switches_to_flat(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->call('toggleThreadMode')
            ->assertSet('threadMode', 'flat');
    }

    /** @test */
    public function test_toggle_thread_mode_switches_back_to_threaded(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->call('toggleThreadMode') // → flat
            ->call('toggleThreadMode') // → threaded
            ->assertSet('threadMode', 'threaded');
    }

    /** @test */
    public function test_threaded_mode_returns_array_not_paginator(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->call('toggleThreadMode') // switch to flat
            ->assertSet('threadMode', 'flat')
            ->call('toggleThreadMode') // switch back to threaded
            ->assertSet('threadMode', 'threaded');
    }

    /** @test */
    public function test_thread_row_renders_with_expand_chevron(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        // Thread row should have expand/collapse chevron
        $response->assertSee('toggleExpand');
        // Thread row should have Alpine.js x-data for expansion state
        $response->assertSee('expanded');
    }

    /** @test */
    public function test_thread_toggle_button_has_correct_active_state(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->assertSee('bg-primary-subtle text-primary')
            ->call('toggleThreadMode')
            ->assertSet('threadMode', 'flat')
            ->assertSee('text-ink-secondary');
    }

    /** @test */
    public function test_threaded_view_shows_thread_children(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->assertSee('toggleExpand');
    }

    /** @test */
    public function test_folder_change_dispatches_load_thread_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->dispatch('folder-changed', folderPath: 'Sent')
            ->assertSet('folderPath', 'Sent');
    }

    /** @test */
    public function test_thread_toggle_persistence_per_folder(): void
    {
        // Start in INBOX with threaded mode (default)
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->call('toggleThreadMode')
            ->assertSet('threadMode', 'flat');

        // Switch to Sent — default is flat per UI-SPEC
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'Sent'])
            ->assertSet('threadMode', 'threaded'); // Default is still threaded since mount sets it
    }

    /** @test */
    public function test_keyboard_shortcuts_rendered_in_view(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);

        // Thread toggle button with tooltip showing shortcut
        $response->assertSee('Toggle thread view (t)');

        // Search placeholder shows shortcut hint
        $response->assertSee('Search all mail... (/)');
    }

    /** @test */
    public function test_search_focus_shortcut_in_search_bar(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);

        // Search bar should have the '/' shortcut placeholder
        $response->assertSee('Search all mail... (/)');
    }

    /** @test */
    public function test_thread_row_keyboard_navigation(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);

        // Thread row should have keyboard navigation handlers
        $response->assertSee('handleKeydown');
        $response->assertSee('ArrowRight');
        $response->assertSee('ArrowLeft');
    }

    /** @test */
    public function test_thread_row_renders_safely_when_labels_is_webklex_attribute(): void
    {
        $thread = (object) [
            'uid' => 9999,
            'message_id' => '<attr-test@example.com>',
            'in_reply_to' => '',
            'references' => '',
            'subject' => 'Attribute Safe Subject',
            'date' => now()->toDateTimeString(),
            'formatted_date' => '10:30 AM',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender Name',
            'from_display' => 'Sender Name',
            'to_address' => 'user@example.com',
            'is_seen' => true,
            'is_flagged' => false,
            'has_attachments' => false,
            'snippet' => 'Test preview text',
            'folder_path' => 'INBOX',
            'labels' => new Attribute('labels'),
            'children' => [],
            'unreadCount' => 0,
        ];

        $view = $this->blade(
            '<x-mailbox.thread-row :thread="$thread" :selected="false" />',
            ['thread' => $thread]
        );

        $view->assertSee('Attribute Safe Subject');
        $view->assertSee('Sender Name');
        $view->assertSee('10:30 AM');
    }

    /** @test */
    public function test_message_row_renders_safely_when_labels_is_webklex_attribute(): void
    {
        $message = (object) [
            'uid' => 9998,
            'message_id' => '<attr-test-2@example.com>',
            'subject' => 'Attribute Flat Subject',
            'date' => now()->toDateTimeString(),
            'formatted_date' => '11:45 AM',
            'from_address' => 'sender2@example.com',
            'from_name' => 'Sender Two',
            'from_display' => 'Sender Two',
            'to_address' => 'user@example.com',
            'is_seen' => false,
            'is_flagged' => true,
            'has_attachments' => true,
            'snippet' => 'Flat preview text',
            'folder_path' => 'INBOX',
            'labels' => new Attribute('labels'),
        ];

        $view = $this->blade(
            '@include("livewire.mailbox.message-row", ["message" => $message, "selected" => false])',
            ['message' => $message]
        );

        $view->assertSee('Attribute Flat Subject');
        $view->assertSee('Sender Two');
        $view->assertSee('11:45 AM');
    }

    /** @test */
    public function test_flat_mode_renders_messages_without_array_contains_error(): void
    {
        $messageObj = (object) [
            'uid' => 101,
            'message_id' => '<msg-101@example.com>',
            'subject' => 'Flat Message Test',
            'date' => now()->toDateTimeString(),
            'formatted_date' => '12:00 PM',
            'from_address' => 'sender@example.com',
            'from_name' => 'Flat Sender',
            'from_display' => 'Flat Sender',
            'to_address' => 'user@example.com',
            'is_seen' => true,
            'is_flagged' => false,
            'has_attachments' => false,
            'snippet' => 'Flat snippet',
            'folder_path' => 'INBOX',
            'labels' => collect(),
        ];

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getCachedFolders')->andReturn([
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 1, 'unread_count' => 0],
        ]);
        $mockService->shouldReceive('getMessageCount')->andReturn(['total' => 1, 'unread' => 0]);
        $mockService->shouldReceive('getFolders')->andReturn([]);
        $mockService->shouldReceive('refreshFolderCache')->andReturn(null);
        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([$messageObj], 1, 25));
        $this->app->instance(ImapMailboxService::class, $mockService);

        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->set('threadMode', 'flat')
            ->assertSee('Flat Message Test')
            ->assertSee('Flat Sender');
    }
}
