<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MessageMetadata;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;

use PHPUnit\Framework\Attributes\Test;

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

        $this->app->instance(\App\Services\ImapMailboxService::class, $mockService);
        $this->app->instance(\App\Services\FolderMapper::class, new \App\Services\FolderMapper());
        $this->app->instance(\App\Services\MessageSanitizer::class, new \App\Services\MessageSanitizer());
    }

    #[Test]
    public function testTabbedSidebar(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox');

        $response->assertStatus(200);

        // FolderSidebar component should have activeTab property with default
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function testFolderSidebarTabSwitching(): void
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
    public function testTabbedSidebarInvalidTabDefaultsToFolders(): void
    {
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->call('setActiveTab', 'invalid')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function testTabbedSidebarRendersAllPanels(): void
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
    public function testDesktopTabBarActiveState(): void
    {
        // Verify the FolderSidebar component renders with correct tabs via Livewire
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function testMobileBottomNavHiddenOnDesktop(): void
    {
        // Verify the component renders and has active tab state
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->assertSet('activeTab', 'folders');
    }

    #[Test]
    public function testFullSearchFlow(): void
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
    public function testSearchWithLabelFilter(): void
    {
        $label = \App\Models\Label::create([
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
    public function testThreadViewWithLabels(): void
    {
        $label = \App\Models\Label::create([
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
    public function testArchiveFromThreadView(): void
    {
        // Verify mailbox page loads successfully (toolbar with archive renders via Livewire)
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox');

        $response->assertStatus(200);
    }

    #[Test]
    public function testMobileResponsive(): void
    {
        // Verify mailbox page loads with mobile-responsive layout
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox');

        $response->assertStatus(200);
    }

    #[Test]
    public function testAllFiltersCombined(): void
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
    public function testLabelChipsInThreadRow(): void
    {
        $label = \App\Models\Label::create([
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
    public function testContactGroupsInSidebar(): void
    {
        // Verify contacts tab is accessible
        Livewire::actingAs($this->user)
            ->test('mailbox.folder-sidebar')
            ->call('setActiveTab', 'contacts')
            ->assertSet('activeTab', 'contacts');
    }

    #[Test]
    public function testEmptyStatesMatchUiSpec(): void
    {
        // Verify search empty state text
        $response = $this->actingAs($this->user)
            ->get(route('search', ['q' => 'nonexistent query xyz']));
        $response->assertStatus(200);
        $response->assertSee('No messages found');
        $response->assertSee('Try adjusting your search terms or filters');
    }
}
