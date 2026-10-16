<?php

namespace Tests\Feature;

use App\Livewire\Mailbox\FolderSidebar;
use App\Models\User;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class FolderDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected FolderMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->mapper = app(FolderMapper::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_single_trash_folder_is_mapped_correctly(): void
    {
        $folders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 10, 'unread' => 2],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 5, 'unread' => 0],
            ['path' => 'Archive', 'name' => 'Archive', 'role' => 'archive', 'total' => 20, 'unread' => 0],
        ];

        $normalized = $this->mapper->normalizeFolders($folders);

        $trashFolders = array_filter($normalized, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $trashFolders);
        $trash = array_values($trashFolders)[0];
        $this->assertEquals('Trash', $trash['path']);
        $this->assertEquals('Trash', $trash['name']);
    }

    public function test_trash_alias_and_bin_alias_are_deduplicated_to_single_system_folder(): void
    {
        // Simulating the exact screenshot defect:
        // Server returns both "Trash" and "Bin" aliases for trash mailbox
        $folders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 10, 'unread' => 1],
            ['path' => 'Bin', 'name' => 'Trash', 'role' => 'trash', 'total' => 0, 'unread' => 0],
            ['path' => 'Spam', 'name' => 'Spam', 'role' => 'spam', 'total' => 2, 'unread' => 2],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 8, 'unread' => 0],
            ['path' => 'Deleted Items', 'name' => 'Trash', 'role' => 'trash', 'total' => 0, 'unread' => 0],
        ];

        $normalized = $this->mapper->normalizeFolders($folders);

        $trashFolders = array_filter($normalized, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $trashFolders, 'Multiple trash aliases must be consolidated to exactly one system Trash folder');

        $canonicalTrash = array_values($trashFolders)[0];
        // The one with messages/exact leaf name 'Trash' is prioritized as canonical
        $this->assertEquals('Trash', $canonicalTrash['path']);
        $this->assertEquals('Trash', $canonicalTrash['name']);
        $this->assertEquals(8, $canonicalTrash['total']);
    }

    public function test_gmail_style_system_folder_names_resolve_consistently(): void
    {
        $folders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 15, 'unread' => 3],
            ['path' => '[Gmail]/Sent Mail', 'name' => 'Sent', 'role' => 'sent', 'total' => 50, 'unread' => 0],
            ['path' => '[Gmail]/Drafts', 'name' => 'Drafts', 'role' => 'drafts', 'total' => 2, 'unread' => 0],
            ['path' => '[Gmail]/All Mail', 'name' => 'Archive', 'role' => 'archive', 'total' => 100, 'unread' => 0],
            ['path' => '[Gmail]/Spam', 'name' => 'Spam', 'role' => 'spam', 'total' => 4, 'unread' => 4],
            ['path' => '[Gmail]/Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 12, 'unread' => 0],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 0, 'unread' => 0], // Local redundant alias
        ];

        $normalized = $this->mapper->normalizeFolders($folders);

        $trashFolders = array_filter($normalized, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $trashFolders, 'Gmail trash and root trash alias must consolidate to single Gmail system trash');

        $canonicalTrash = array_values($trashFolders)[0];
        $this->assertEquals('[Gmail]/Trash', $canonicalTrash['path']);
        $this->assertEquals('Trash', $canonicalTrash['name']);
    }

    public function test_user_created_folders_resembling_trash_are_preserved(): void
    {
        $folders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 5, 'unread' => 0],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 2, 'unread' => 0],
            // Custom user-created mailboxes whose names contain 'trash' or 'bin'
            ['path' => 'Trash Projects', 'name' => 'Trash Projects', 'role' => null, 'total' => 14, 'unread' => 1],
            ['path' => 'Bin Invoices 2024', 'name' => 'Bin Invoices 2024', 'role' => null, 'total' => 7, 'unread' => 0],
        ];

        $normalized = $this->mapper->normalizeFolders($folders);

        // System trash is exactly 1
        $trashFolders = array_filter($normalized, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $trashFolders);

        // Custom folders are preserved and NOT merged or lost
        $customFolders = array_filter($normalized, fn($f) => empty($f['role']));
        $this->assertCount(2, $customFolders);

        $customPaths = array_column($customFolders, 'path');
        $this->assertContains('Trash Projects', $customPaths);
        $this->assertContains('Bin Invoices 2024', $customPaths);
    }

    public function test_repeated_folder_refreshes_remain_stable_and_deduplicated(): void
    {
        $rawFolders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 10, 'unread' => 0],
            ['path' => 'Bin', 'name' => 'Trash', 'role' => 'trash', 'total' => 0, 'unread' => 0],
            ['path' => 'Spam', 'name' => 'Spam', 'role' => 'spam', 'total' => 1, 'unread' => 0],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 3, 'unread' => 0],
        ];

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getCachedFolders')->andReturn($rawFolders);
        $mockService->shouldReceive('refreshFolderCache')->andReturn(null);
        $this->app->instance(ImapMailboxService::class, $mockService);

        session(['openmail:imap_password' => Crypt::encrypt('secret')]);

        $test = Livewire::actingAs($this->user)
            ->test(FolderSidebar::class);

        // Initial render: exactly one Trash folder
        $folders = $test->get('folders');
        $trashEntries = array_filter($folders, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $trashEntries);

        // Call refreshFolders 3 times in a row
        $test->call('refreshFolders');
        $test->call('refreshFolders');
        $test->call('refreshFolders');

        $refreshed = $test->get('folders');
        $refreshedTrash = array_filter($refreshed, fn($f) => ($f['role'] ?? null) === 'trash');
        $this->assertCount(1, $refreshedTrash, 'Repeated refresh must never re-introduce duplicate trash folders');
        $this->assertEquals('Trash', array_values($refreshedTrash)[0]['name']);
    }

    public function test_folder_sidebar_renders_stable_unique_keys(): void
    {
        $rawFolders = [
            ['path' => 'INBOX', 'name' => 'Inbox', 'role' => 'inbox', 'total' => 10, 'unread' => 0],
            ['path' => 'Sent', 'name' => 'Sent', 'role' => 'sent', 'total' => 5, 'unread' => 0],
            ['path' => 'Trash', 'name' => 'Trash', 'role' => 'trash', 'total' => 2, 'unread' => 0],
            ['path' => 'Custom Projects', 'name' => 'Custom Projects', 'role' => null, 'total' => 1, 'unread' => 0],
        ];

        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getCachedFolders')->andReturn($rawFolders);
        $this->app->instance(ImapMailboxService::class, $mockService);

        session(['openmail:imap_password' => Crypt::encrypt('secret')]);

        $inboxKey = 'folder-core-' . md5('INBOX');
        $trashKey = 'folder-cleanup-' . md5('Trash');
        $customKey = 'folder-custom-' . md5('Custom Projects');

        Livewire::actingAs($this->user)
            ->test(FolderSidebar::class)
            ->assertSee($inboxKey)
            ->assertSee($trashKey)
            ->assertSee($customKey);
    }
}
