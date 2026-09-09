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
    public function test_compose_button_has_brand_color_classes(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('mailbox.message-list', ['folderPath' => 'INBOX']);

        $html = $component->html();

        // Compose button uses primary brand color
        $this->assertStringContainsString('bg-primary', $html);
    }

    #[Test]
    public function test_send_button_has_brand_color_classes(): void
    {
        $composerPath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $this->assertFileExists($composerPath);
        $composerContent = file_get_contents($composerPath);

        // Send button uses primary brand color
        $this->assertStringContainsString('bg-primary', $composerContent);
    }

    #[Test]
    public function test_x_cloak_css_rule_exists(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $this->assertFileExists($cssPath);
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('[x-cloak]', $cssContent);
        $this->assertStringContainsString('display: none', $cssContent);
    }

    #[Test]
    public function test_design_system_tokens_present(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $this->assertFileExists($cssPath);
        $cssContent = file_get_contents($cssPath);

        // Semantic color tokens
        $this->assertStringContainsString('--color-primary', $cssContent);
        $this->assertStringContainsString('--color-surface', $cssContent);
        $this->assertStringContainsString('--color-ink', $cssContent);
        $this->assertStringContainsString('--color-border', $cssContent);

        // Shadow tokens
        $this->assertStringContainsString('--shadow-sm', $cssContent);
        $this->assertStringContainsString('--shadow-md', $cssContent);

        // Dark mode tokens
        $this->assertStringContainsString(':where(.dark)', $cssContent);
    }

    #[Test]
    public function test_dark_mode_tokens_defined(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $cssContent = file_get_contents($cssPath);

        // Dark mode surface and ink colors
        $this->assertStringContainsString('--color-surface: #111318', $cssContent);
        $this->assertStringContainsString('--color-surface-raised: #1a1d24', $cssContent);
        $this->assertStringContainsString('--color-ink: #e8eaef', $cssContent);
    }

    #[Test]
    public function test_skeleton_loading_class_defined(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('.skeleton', $cssContent);
        $this->assertStringContainsString('shimmer', $cssContent);
    }

    #[Test]
    public function test_message_viewer_has_no_chrome_classes(): void
    {
        $viewerPath = base_path('resources/views/livewire/mailbox/message-viewer.blade.php');
        $this->assertFileExists($viewerPath);
        $viewerContent = file_get_contents($viewerPath);

        // No glass morphism in message viewer
        $this->assertStringNotContainsString('glass-', $viewerContent);
        // No gradient backgrounds
        $this->assertStringNotContainsString('bg-linear', $viewerContent);
    }

    #[Test]
    public function test_layout_emits_csp_nonce_meta(): void
    {
        $layoutPath = base_path('resources/views/layouts/app.blade.php');
        $this->assertFileExists($layoutPath);
        $layoutContent = file_get_contents($layoutPath);

        $this->assertStringContainsString('@cspNonceMetaTag', $layoutContent);
    }

    #[Test]
    public function test_folder_sidebar_uses_semantic_tokens(): void
    {
        $sidebarPath = base_path('resources/views/livewire/mailbox/folder-sidebar.blade.php');
        $this->assertFileExists($sidebarPath);
        $sidebarContent = file_get_contents($sidebarPath);

        // Uses semantic token classes
        $this->assertStringContainsString('bg-primary-subtle', $sidebarContent);
        $this->assertStringContainsString('text-primary', $sidebarContent);
        $this->assertStringContainsString('bg-surface-raised', $sidebarContent);
        $this->assertStringContainsString('border-border', $sidebarContent);
    }

    #[Test]
    public function test_login_uses_design_system(): void
    {
        $loginPath = base_path('resources/views/auth/login.blade.php');
        $this->assertFileExists($loginPath);
        $loginContent = file_get_contents($loginPath);

        $this->assertStringContainsString('bg-surface', $loginContent);
        $this->assertStringContainsString('bg-surface-raised', $loginContent);
        $this->assertStringContainsString('border-border', $loginContent);
        $this->assertStringContainsString('Tonmoy Infrastructure', $loginContent);
    }

    #[Test]
    public function test_setup_wizard_has_progress_bar(): void
    {
        $wizardPath = base_path('resources/views/livewire/setup-wizard.blade.php');
        $this->assertFileExists($wizardPath);
        $wizardContent = file_get_contents($wizardPath);

        // Mobile progress bar
        $this->assertStringContainsString('rounded-full', $wizardContent);
        // Desktop step bubbles
        $this->assertStringContainsString('rounded-full', $wizardContent);
        // Success state
        $this->assertStringContainsString('bg-success', $wizardContent);
    }

    #[Test]
    public function test_reduced_motion_support(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('prefers-reduced-motion', $cssContent);
    }

    #[Test]
    public function test_scrollbar_styling_defined(): void
    {
        $cssPath = base_path('resources/css/app.css');
        $cssContent = file_get_contents($cssPath);

        $this->assertStringContainsString('scrollbar-thin', $cssContent);
    }

    #[Test]
    public function test_user_profile_dropdown_renders_with_enhanced_ui(): void
    {
        $response = $this->actingAs($this->user)
            ->withSession(['openmail:imap_password' => \Illuminate\Support\Facades\Crypt::encrypt('test-password')])
            ->get('/mailbox/INBOX');

        $response->assertStatus(200);
        $response->assertSee('user-menu-button');
        $response->assertSee('Settings & Preferences', false);
        $response->assertSee('Theme & Display', false);
        $response->assertSee('Sign out');
        $response->assertSee($this->user->email);
    }
}
