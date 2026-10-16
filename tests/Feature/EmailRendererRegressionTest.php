<?php

namespace Tests\Feature;

use App\Livewire\Mailbox\MessageViewer;
use App\Models\User;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\View;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class EmailRendererRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected MessageSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->sanitizer = app(MessageSanitizer::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_sanitizer_preserves_nested_tables_buttons_and_layout_styles(): void
    {
        $emailHtml = <<<HTML
        <table width="100%" border="0" cellpadding="0" cellspacing="0" bgcolor="#f4f4f4">
            <tr>
                <td align="center" valign="top" style="padding: 24px 0;">
                    <table width="600" border="0" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px;">
                        <tr>
                            <td style="padding: 32px; font-family: Helvetica, Arial, sans-serif; font-size: 16px; color: #333333; line-height: 24px;">
                                <h1 style="margin: 0 0 16px; font-size: 24px; color: #111111;">Special Promotion</h1>
                                <p style="margin: 0 0 24px;">Click the button below to claim your offer:</p>
                                <table border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center" style="border-radius: 6px; background-color: #e11d48;">
                                            <a href="https://example.com/claim" target="_blank"
                                               style="display: inline-block; padding: 14px 28px; font-family: Arial, sans-serif; font-size: 16px; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold;">
                                                Claim Offer
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <script>alert("malicious script execution");</script>
HTML;

        $sanitized = $this->sanitizer->sanitizeHtml($emailHtml);

        // Verify layout elements and attributes preserved (HTMLPurifier removes spaces after colon)
        $this->assertStringContainsString('background-color:#ffffff', $sanitized);
        $this->assertStringContainsString('border-radius:8px', $sanitized);
        $this->assertStringContainsString('padding:32px', $sanitized);
        $this->assertStringContainsString('color:#ffffff', $sanitized);
        $this->assertStringContainsString('display:inline-block', $sanitized);
        $this->assertStringContainsString('border-radius:6px', $sanitized);
        $this->assertStringContainsString('Claim Offer', $sanitized);

        // Verify malicious script is stripped
        $this->assertStringNotContainsString('<script', $sanitized);
        $this->assertStringNotContainsString('malicious script', $sanitized);
    }

    public function test_remote_images_are_safely_blocked_without_breaking_dimensions(): void
    {
        $emailHtml = <<<HTML
        <div>
            <img src="https://example.com/promotions/hero-banner.jpg" width="600" height="200" alt="Special Offer">
            <img src="https://tracking.example.com/pixel.gif" width="1" height="1" alt="">
        </div>
HTML;

        $blocked = $this->sanitizer->blockRemoteImages($emailHtml);

        // Remote image converted to data-src and assigned blocked-image class
        $this->assertStringContainsString('data-src="https://example.com/promotions/hero-banner.jpg"', $blocked);
        $this->assertStringContainsString('class="blocked-image"', $blocked);
        $this->assertStringContainsString('src="data:image/gif;base64,', $blocked);

        // 1x1 tracking pixel hidden
        $this->assertStringContainsString('display:none !important;', $blocked);
    }

    public function test_inline_cid_images_are_resolved_to_base64_data_uris(): void
    {
        $emailHtml = '<p>Check out our logo: <img src="cid:brand-logo@example.com" alt="Company Logo"></p>';

        $attachment = (object) [
            'content_id' => 'brand-logo@example.com',
            'mime_type' => 'image/png',
            'content' => 'fake-png-binary-data',
        ];

        $resolvedHtml = $this->sanitizer->resolveCidImages($emailHtml, [$attachment]);

        $this->assertStringContainsString('src="data:image/png;base64,', $resolvedHtml);
        $this->assertStringContainsString(base64_encode('fake-png-binary-data'), $resolvedHtml);
        $this->assertStringNotContainsString('cid:brand-logo@example.com', $resolvedHtml);

        // Ensure sanitizer permits the resolved data:image scheme
        $sanitized = $this->sanitizer->sanitizeHtml($resolvedHtml);
        $this->assertStringContainsString('data:image/png;base64,', $sanitized);
    }

    public function test_email_renderer_component_uses_sandboxed_iframe(): void
    {
        $view = View::make('components.email-renderer', [
            'html' => '<p>Hello <strong>World</strong></p>',
            'showImages' => false,
        ])->render();

        $this->assertStringContainsString('<iframe', $view);
        $this->assertStringContainsString('sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"', $view);
        $this->assertStringNotContainsString('allow-scripts', $view);
        $this->assertStringContainsString('Remote images blocked for privacy.', $view);
    }

    public function test_message_viewer_displays_plain_text_fallback_when_html_is_empty(): void
    {
        $mockService = Mockery::mock(ImapMailboxService::class);
        $mockService->shouldReceive('getMessageWithBody')
            ->once()
            ->with('INBOX', 123)
            ->andReturn((object) [
                'uid' => 123,
                'subject' => 'Plain Text Notification',
                'from_address' => 'sender@example.com',
                'from_name' => 'Sender',
                'to_address' => 'user@example.com',
                'date' => now()->toDateTimeString(),
                'html_body' => '',
                'text_body' => "This is a plain-text notification.\r\nPlease review your account.",
                'is_seen' => true,
                'is_flagged' => false,
                'attachments' => [],
            ]);

        $mockService->shouldReceive('markAsRead')->andReturn(true);
        $mockService->shouldReceive('getCachedFolders')->andReturn([]);
        $this->app->instance(ImapMailboxService::class, $mockService);

        session(['openmail:imap_password' => Crypt::encrypt('secret')]);

        Livewire::actingAs($this->user)
            ->test(MessageViewer::class, ['folderPath' => 'INBOX', 'uid' => 123])
            ->assertSee('Plain Text Notification')
            ->assertSee('This is a plain-text notification.')
            ->assertSee('Please review your account.');
    }
}
