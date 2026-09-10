<?php

namespace Tests\Feature\Mailbox;

use App\Livewire\Mailbox\FolderSidebar;
use App\Models\User;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class MailboxIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the IMAP service to avoid real connections in tests
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

        // Mock getMessages to return a LengthAwarePaginator with empty data
        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        // Mock getThreadHeaders for threaded view
        $mockService->shouldReceive('getThreadHeaders')->andReturn([]);

        $this->app->instance(ImapMailboxService::class, $mockService);

        // Also bind the FolderMapper
        $this->app->instance(FolderMapper::class, new FolderMapper);
        $this->app->instance(MessageSanitizer::class, new MessageSanitizer);
    }

    public function test_unauthenticated_user_redirected_from_mailbox(): void
    {
        $response = $this->get('/mailbox/INBOX');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_mailbox(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
    }

    public function test_folder_sidebar_component_renders(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        // The folder sidebar is a Livewire component, so check for its wire:key attribute
        $response->assertSee('wire:key');
    }

    public function test_mailbox_layout_extends_app_layout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession(['openmail:imap_password' => Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        $response->assertSee('OpenMail');
    }

    public function test_folder_sidebar_sorts_folders_logically(): void
    {
        $component = new FolderSidebar;
        $unsorted = [
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash'],
            ['path' => 'Zeta', 'name' => 'Zeta', 'role' => null],
            ['path' => 'Spam', 'name' => 'Spam', 'role' => 'spam'],
            ['path' => 'Alpha', 'name' => 'Alpha', 'role' => null],
            ['path' => 'Sent', 'name' => 'Sent', 'role' => 'sent'],
            ['path' => 'Drafts', 'name' => 'Drafts', 'role' => 'drafts'],
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox'],
        ];

        $sorted = $component->sortFolders($unsorted);
        $roles = array_column($sorted, 'role');

        $this->assertEquals('inbox', $roles[0]);
        $this->assertEquals('drafts', $roles[1]);
        $this->assertEquals('sent', $roles[2]);
        $this->assertEquals('spam', $roles[3]);
        $this->assertEquals('trash', $roles[4]);
        $this->assertEquals('Alpha', $sorted[5]['name']);
        $this->assertEquals('Zeta', $sorted[6]['name']);
    }

    public function test_sidebar_has_compose_and_sync_elements(): void
    {
        $user = User::factory()->create();
        $response = Livewire::actingAs($user)
            ->test(FolderSidebar::class);

        $response->assertSee('New Message');
        $response->assertSee('Sync Mailbox');
        $response->assertSee('wire:target="refreshFolders"', false);
    }
}
