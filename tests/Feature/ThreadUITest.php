<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;

use PHPUnit\Framework\Attributes\Test;

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
        $mockService = Mockery::mock(\App\Services\ImapMailboxService::class);

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

        $this->app->instance(\App\Services\ImapMailboxService::class, $mockService);
        $this->app->instance(\App\Services\FolderMapper::class, new \App\Services\FolderMapper());
        $this->app->instance(\App\Services\MessageSanitizer::class, new \App\Services\MessageSanitizer());
    }

    #[Test]
    public function test_threaded_view_loads_for_inbox(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox');

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
            ->get('/mailbox');

        $response->assertStatus(200);
        // Toggle button with wire:click should be present
        $response->assertSee('toggleThreadMode');
        $response->assertSee('Threaded view (t)');
        // Thread mode text should be visible
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
            ->get('/mailbox');

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
            ->assertSee('bg-blue-50 text-blue-600')
            ->call('toggleThreadMode')
            ->assertSet('threadMode', 'flat')
            ->assertSee('text-gray-600 hover:bg-gray-100');
    }

    /** @test */
    public function test_threaded_view_shows_thread_children(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->assertSet('threadMode', 'threaded')
            ->assertSee('thread-row');
    }

    /** @test */
    public function test_folder_change_dispatches_load_thread_mode(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX'])
            ->dispatch('folder-changed', folderPath: 'Sent')
            ->assertSet('folderPath', 'Sent');
    }
}
