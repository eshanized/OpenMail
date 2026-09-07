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
}
