<?php

namespace Tests\Feature\Mailbox;

use App\Models\User;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MessageListLoadingTest extends TestCase
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

        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        $mockService->shouldReceive('getThreadHeaders')
            ->andReturn([]);

        $this->app->instance(ImapMailboxService::class, $mockService);
        $this->app->instance(FolderMapper::class, new FolderMapper);
        $this->app->instance(MessageSanitizer::class, new MessageSanitizer);
    }

    #[Test]
    public function test_message_list_has_skeleton_loading_guard(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX']);

        $html = $component->html();

        $this->assertStringContainsString('wire:target="onFolderChanged,setSort,toggleThreadMode"', $html);
        $this->assertStringContainsString('wire:loading.remove', $html);
    }

    #[Test]
    public function test_message_list_has_no_full_screen_spinner_overlay(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX']);

        $html = $component->html();

        $this->assertStringNotContainsString('fixed inset-0 bg-white/80 z-50 flex items-center justify-center', $html);
        $this->assertStringNotContainsString('animate-spin', $html);
    }
}
