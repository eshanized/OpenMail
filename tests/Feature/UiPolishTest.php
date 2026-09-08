<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Illuminate\Pagination\LengthAwarePaginator;

use PHPUnit\Framework\Attributes\Test;

class UiPolishTest extends TestCase
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

        $mockService->shouldReceive('getMessages')
            ->andReturn(new LengthAwarePaginator([], 0, 25));

        $mockService->shouldReceive('getThreadHeaders')
            ->andReturn([]);

        $this->app->instance(\App\Services\ImapMailboxService::class, $mockService);
        $this->app->instance(\App\Services\FolderMapper::class, new \App\Services\FolderMapper());
        $this->app->instance(\App\Services\MessageSanitizer::class, new \App\Services\MessageSanitizer());
    }

    #[Test]
    public function test_compose_button_has_gradient_classes(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX']);

        $html = $component->html();

        // RED today — gradient classes land in plan 06-02
        $this->assertStringContainsString('from-blue-600', $html);
        $this->assertStringContainsString('to-purple-600', $html);
    }

    #[Test]
    public function test_send_and_archive_buttons_have_gradient_classes(): void
    {
        // Source-level: read composer and message-toolbar blade files
        $composerPath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $toolbarPath = base_path('resources/views/livewire/mailbox/message-toolbar.blade.php');

        $this->assertFileExists($composerPath);
        $this->assertFileExists($toolbarPath);

        $composerContent = file_get_contents($composerPath);
        $toolbarContent = file_get_contents($toolbarPath);

        // RED today — gradient classes land in plan 06-03
        $this->assertStringContainsString('from-blue-600', $composerContent);
        $this->assertStringContainsString('to-purple-600', $composerContent);
        $this->assertStringContainsString('from-blue-600', $toolbarContent);
        $this->assertStringContainsString('to-purple-600', $toolbarContent);
    }

    #[Test]
    public function test_x_cloak_css_rule_exists(): void
    {
        // Source-level: read app.css
        $cssPath = base_path('resources/css/app.css');
        $this->assertFileExists($cssPath);

        $cssContent = file_get_contents($cssPath);

        // RED today — [x-cloak] rule added in plan 06-02
        $this->assertStringContainsString('[x-cloak]', $cssContent);
        $this->assertStringContainsString('display: none', $cssContent);
    }

    #[Test]
    public function test_glass_surface_classes_present(): void
    {
        // Source-level: assert glass classes in blade markup
        $sidebarPath = base_path('resources/views/livewire/mailbox/folder-sidebar.blade.php');
        $searchPath = base_path('resources/views/livewire/mailbox/search-results-dropdown.blade.php');
        $layoutPath = base_path('resources/views/layouts/mailbox.blade.php');

        $this->assertFileExists($sidebarPath);
        $this->assertFileExists($searchPath);
        $this->assertFileExists($layoutPath);

        $sidebarContent = file_get_contents($sidebarPath);
        $searchContent = file_get_contents($searchPath);
        $layoutContent = file_get_contents($layoutPath);

        // RED today — glass classes land in plans 06-02/06-03
        $this->assertStringContainsString('backdrop-blur-sm', $sidebarContent);
        $this->assertStringContainsString('backdrop-blur-sm', $searchContent);
        $this->assertStringContainsString('glass-card', $layoutContent);
    }

    #[Test]
    public function test_status_badges_use_soft_tag_style(): void
    {
        // Source-level: folder-sidebar unread-count pill keeps tinted-bg + colored-text soft-tag pattern
        $sidebarPath = base_path('resources/views/livewire/mailbox/folder-sidebar.blade.php');
        $this->assertFileExists($sidebarPath);

        $sidebarContent = file_get_contents($sidebarPath);

        // GREEN today — pill already matches soft-tag pattern
        // Assert bg-*-100 tinted pill fill and text-*-600 colored text pair
        $this->assertStringContainsString('bg-', $sidebarContent);
        $this->assertStringContainsString('text-', $sidebarContent);
        // More specific: red-100/red-600 or similar tinted pattern
        $this->assertTrue(
            str_contains($sidebarContent, 'red-100') || str_contains($sidebarContent, 'bg-red-100') ||
            str_contains($sidebarContent, 'bg-blue-100') || str_contains($sidebarContent, 'bg-green-100') ||
            str_contains($sidebarContent, 'bg-yellow-100') || str_contains($sidebarContent, 'bg-purple-100')
        );
        $this->assertTrue(
            str_contains($sidebarContent, 'text-red-600') || str_contains($sidebarContent, 'text-blue-600') ||
            str_contains($sidebarContent, 'text-green-600') || str_contains($sidebarContent, 'text-yellow-600') ||
            str_contains($sidebarContent, 'text-purple-600')
        );
    }

    #[Test]
    public function test_message_viewer_has_no_chrome_classes(): void
    {
        // Source-level: message-viewer must NOT contain glass- prefix or gradient utility prefix
        $viewerPath = base_path('resources/views/livewire/mailbox/message-viewer.blade.php');
        $this->assertFileExists($viewerPath);

        $viewerContent = file_get_contents($viewerPath);

        // GREEN today — cross-phase invariant
        $this->assertStringNotContainsString('glass-', $viewerContent);
        $this->assertStringNotContainsString('bg-linear', $viewerContent);
    }

    #[Test]
    public function test_layout_emits_csp_nonce_meta(): void
    {
        // Source-level: check app.blade.php for @cspNonceMetaTag directive
        $layoutPath = base_path('resources/views/layouts/app.blade.php');
        $this->assertFileExists($layoutPath);

        $layoutContent = file_get_contents($layoutPath);

        // GREEN today — CSP nonce meta tag emitted by spatie/laravel-csp
        $this->assertStringContainsString('@cspNonceMetaTag', $layoutContent);
    }
}