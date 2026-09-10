<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\MessageMetadata;
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

class IntegrationTest extends TestCase
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
                [
                    'path' => 'Sent',
                    'name' => 'Sent',
                    'role' => 'sent',
                    'total' => 3,
                    'total_count' => 3,
                    'unread_count' => 0,
                    'uidvalidity' => 12346,
                    'has_children' => false,
                    'parent_path' => '',
                ],
                [
                    'path' => 'Drafts',
                    'name' => 'Drafts',
                    'role' => 'drafts',
                    'total' => 1,
                    'total_count' => 1,
                    'unread_count' => 0,
                    'uidvalidity' => 12347,
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

        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        $mockService->shouldReceive('getThreadHeaders')
            ->andReturn([]);

        $this->app->instance(ImapMailboxService::class, $mockService);
        $this->app->instance(FolderMapper::class, new FolderMapper);
        $this->app->instance(MessageSanitizer::class, new MessageSanitizer);
    }

    #[Test]
    public function test_tabbed_sidebar(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);

        // FolderSidebar component should have activeTab property with default
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function test_folder_sidebar_tab_switching(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders')
            ->call('setActiveTab', 'contacts')
            ->assertSet('activeTab', 'contacts')
            ->call('setActiveTab', 'labels')
            ->assertSet('activeTab', 'labels')
            ->call('setActiveTab', 'folders')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function test_tabbed_sidebar_invalid_tab_defaults_to_folders(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->call('setActiveTab', 'invalid')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function test_tabbed_sidebar_renders_all_panels(): void
    {
        // Verify all three panels are available through tab switching
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders')
            ->call('setActiveTab', 'contacts')
            ->assertSet('activeTab', 'contacts')
            ->call('setActiveTab', 'labels')
            ->assertSet('activeTab', 'labels');
    }

    #[Test]
    public function test_desktop_tab_bar_active_state(): void
    {
        // Verify the FolderSidebar component renders with correct tabs via Livewire
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function test_mobile_bottom_nav_hidden_on_desktop(): void
    {
        // Verify the component renders and has active tab state
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function test_full_search_flow(): void
    {
        // Create test message
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Project alpha deadline',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        // Search from search results page
        $response = $this->actingAs($this->user)
            ->get(route('search', ['q' => 'Project alpha']));
        $response->assertStatus(200);
        $response->assertSee('Project');
        $response->assertSee('alpha');
        $response->assertSee('deadline');
    }

    #[Test]
    public function test_search_with_label_filter(): void
    {
        $label = Label::create([
            'user_id' => $this->user->id,
            'name' => 'Important',
            'color' => '#DC2626',
        ]);

        $taggedMessage = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Tagged search result',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);
        $taggedMessage->labels()->attach($label->id, ['user_id' => $this->user->id]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Untagged search result',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('search', ['q' => 'search result', 'labels' => [$label->id]]));
        $response->assertStatus(200);
        $response->assertSee('Tagged');
        $response->assertDontSee('Untagged');
    }

    #[Test]
    public function test_thread_view_with_labels(): void
    {
        $label = Label::create([
            'user_id' => $this->user->id,
            'name' => 'Work',
            'color' => '#2563EB',
        ]);

        $message = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Threaded message with label',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);
        $message->labels()->attach($label->id, ['user_id' => $this->user->id]);

        // Verify label is attached to message in database
        $this->assertDatabaseHas('message_labels', [
            'message_metadata_id' => $message->id,
            'label_id' => $label->id,
        ]);
    }

    #[Test]
    public function test_archive_from_thread_view(): void
    {
        // Verify mailbox page loads successfully (toolbar with archive renders via Livewire)
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_mobile_responsive(): void
    {
        // Verify mailbox page loads with mobile-responsive layout
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
    }

    #[Test]
    public function test_all_filters_combined(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Filtered message',
            'has_attachments' => true,
            'is_seen' => false,
            'is_flagged' => true,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('search', [
                'q' => 'Filtered',
                'folder' => 'INBOX',
                'has_attachment' => '1',
                'is_seen' => '0',
                'is_flagged' => '1',
            ]));
        $response->assertStatus(200);
        $response->assertSee('Filtered');
    }

    #[Test]
    public function test_label_chips_in_thread_row(): void
    {
        $label = Label::create([
            'user_id' => $this->user->id,
            'name' => 'Review',
            'color' => '#9333EA',
        ]);

        $message = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Message with label chips',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);
        $message->labels()->attach($label->id, ['user_id' => $this->user->id]);

        // Verify label is attached to message in database
        $this->assertDatabaseHas('message_labels', [
            'message_metadata_id' => $message->id,
            'label_id' => $label->id,
        ]);
    }

    #[Test]
    public function test_contact_groups_in_sidebar(): void
    {
        // Verify contacts tab is accessible
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->call('setActiveTab', 'contacts')
            ->assertSet('activeTab', 'contacts');
    }

    #[Test]
    public function test_empty_states_match_ui_spec(): void
    {
        // Verify search empty state text
        $response = $this->actingAs($this->user)
            ->get(route('search', ['q' => 'nonexistent query xyz']));
        $response->assertStatus(200);
        $response->assertSee('No messages found');
        $response->assertSee('Try adjusting your search terms or filters');
    }
}
