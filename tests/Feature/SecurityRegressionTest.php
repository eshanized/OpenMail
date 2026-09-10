<?php

namespace Tests\Feature;

use App\Http\Middleware\SsrfProtection;
use App\Install\SecurityConfigurator;
use App\Livewire\Mailbox\AttachmentList;
use App\Livewire\SetupWizard;
use App\Models\ContactAutocompleteCache;
use App\Models\Label;
use App\Models\MessageMetadata;
use App\Models\User;
use App\Services\ContactAutocompleteService;
use App\Services\ContactService;
use App\Services\LabelService;
use App\Services\MessageSanitizer;
use App\Services\SearchService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Regression tests for security fixes found during battle testing.
 *
 * Each test covers a specific vulnerability that was found and fixed.
 * These tests ensure the fixes remain effective against future regressions.
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    // ════════════════════════════════════════════════════════════════════════
    // SSRF Protection (CRITICAL-1: SsrfProtection middleware bypasses)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function ssrf_middleware_blocks_ipv4_loopback(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->isDangerousUrl('http://127.0.0.1/secret'));
        $this->assertTrue($middleware->isDangerousUrl('http://127.0.0.1:8080/admin'));
        $this->assertTrue($middleware->isDangerousUrl('http://127.1.2.3/path'));
    }

    /** @test */
    public function ssrf_middleware_blocks_private_ipv4_ranges(): void
    {
        $middleware = new SsrfProtection;

        // 10.0.0.0/8
        $this->assertTrue($middleware->isDangerousUrl('http://10.0.0.1/'));
        $this->assertTrue($middleware->isDangerousUrl('http://10.255.255.255/'));

        // 172.16.0.0/12
        $this->assertTrue($middleware->isDangerousUrl('http://172.16.0.1/'));
        $this->assertTrue($middleware->isDangerousUrl('http://172.31.255.255/'));

        // 192.168.0.0/16
        $this->assertTrue($middleware->isDangerousUrl('http://192.168.1.1/'));
        $this->assertTrue($middleware->isDangerousUrl('http://192.168.0.0/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_link_local(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->isDangerousUrl('http://169.254.169.254/latest/meta-data/'));
        $this->assertTrue($middleware->isDangerousUrl('http://169.254.1.1/metadata'));
    }

    /** @test */
    public function ssrf_middleware_blocks_ipv6_loopback_and_private(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->isDangerousUrl('http://[::1]/'));
        $this->assertTrue($middleware->isDangerousUrl('http://[::ffff:127.0.0.1]/'));
        $this->assertTrue($middleware->isDangerousUrl('http://[fc00::1]/'));
        $this->assertTrue($middleware->isDangerousUrl('http://[fd00::1]/'));
        $this->assertTrue($middleware->isDangerousUrl('http://[fe80::1]/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_decimal_ip(): void
    {
        $middleware = new SsrfProtection;
        // 2130706433 = 127.0.0.1 in decimal
        $this->assertTrue($middleware->isDangerousUrl('http://2130706433/'));
        // 167772161 = 10.0.0.1 in decimal
        $this->assertTrue($middleware->isDangerousUrl('http://167772161/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_octal_ip(): void
    {
        $middleware = new SsrfProtection;
        // 0177.0.0.1 = 127.0.0.1 in octal
        $this->assertTrue($middleware->isDangerousUrl('http://0177.0.0.1/'));
        $this->assertTrue($middleware->isDangerousUrl('http://010.0.0.1/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_hex_ip(): void
    {
        $middleware = new SsrfProtection;
        // 0x7f.0x0.0x0.0x1 = 127.0.0.1 in hex
        $this->assertTrue($middleware->isDangerousUrl('http://0x7f.0x0.0x0.0x1/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_0x0_0_0_0(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->isDangerousUrl('http://0.0.0.0/'));
        $this->assertTrue($middleware->isDangerousUrl('http://0/'));
    }

    /** @test */
    public function ssrf_middleware_blocks_dangerous_schemes(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->isDangerousUrl('javascript:alert(1)'));
        $this->assertTrue($middleware->isDangerousUrl('data:text/html,<script>alert(1)</script>'));
        $this->assertTrue($middleware->isDangerousUrl('vbscript:MsgBox(1)'));
        $this->assertTrue($middleware->isDangerousUrl('file:///etc/passwd'));
    }

    /** @test */
    public function ssrf_middleware_allows_safe_urls(): void
    {
        $middleware = new SsrfProtection;
        $this->assertFalse($middleware->isDangerousUrl('https://example.com'));
        $this->assertFalse($middleware->isDangerousUrl('https://google.com/search?q=test'));
        $this->assertFalse($middleware->isDangerousUrl('mailto:user@example.com'));
        $this->assertFalse($middleware->isDangerousUrl('https://8.8.8.8/dns-query'));
        $this->assertFalse($middleware->isDangerousUrl('https://203.0.113.1/'));
    }

    // ════════════════════════════════════════════════════════════════════════
    // MessageSanitizer URL blocking (CRITICAL-2: isDangerousUrl bypasses)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function sanitizer_blocks_private_ips_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="http://172.16.0.1/admin">Click</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
        $this->assertStringContainsString('link-blocked', $result);

        $html = '<a href="http://192.168.1.1/">Router</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_blocks_ipv6_private_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="http://[::1]/admin">Localhost</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);

        $html = '<a href="http://[fc00::1]/">Private IPv6</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_blocks_decimal_ip_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        // 2130706433 = 127.0.0.1
        $html = '<a href="http://2130706433/">Loopback</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_blocks_octal_ip_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="http://0177.0.0.1/">Octal loopback</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_blocks_hex_ip_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="http://0x7f.0x0.0x0.0x1/">Hex loopback</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_allows_safe_urls_in_email_links(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="https://example.com">Safe</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringNotContainsString('link-blocked', $result);

        $html = '<a href="https://google.com/search?q=test">Search</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="https://google.com/search?q=test"', $result);
    }

    /** @test */
    public function sanitizer_blocks_javascript_schemes(): void
    {
        $sanitizer = new MessageSanitizer;

        $html = '<a href="javascript:alert(1)">XSS</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    /** @test */
    public function sanitizer_handles_url_encoded_bypasses(): void
    {
        $sanitizer = new MessageSanitizer;

        // URL-encoded dots should be decoded before checking
        $html = '<a href="http://127%2e0%2e0%2e1/">Encoded</a>';
        $result = $sanitizer->sanitizeUrls($html);
        $this->assertStringContainsString('href="#"', $result);
    }

    // ════════════════════════════════════════════════════════════════════════
    // LIKE Injection (MEDIUM: ContactService search wildcard injection)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function contact_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();
        $service = new ContactService;

        $service->create($user->id, ['name' => 'Test User', 'email' => 'test@example.com']);
        $service->create($user->id, ['name' => 'Another User', 'email' => 'another@example.com']);

        // % and _ should be treated literally, not as wildcards
        $results = $service->search($user->id, '%');
        $this->assertCount(0, $results, 'Percent sign should not match all records');

        $results = $service->search($user->id, '_');
        $this->assertCount(0, $results, 'Underscore should not match all single-char records');

        // Normal search should still work
        $results = $service->search($user->id, 'test');
        $this->assertCount(1, $results);
    }

    /** @test */
    public function contact_autocomplete_search_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();

        // Create autocomplete cache entries
        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'test@example.com',
            'name' => 'Test Contact',
            'frequency' => 5,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $service = app(ContactAutocompleteService::class);

        // % should be treated literally
        $results = $service->search($user->id, '%');
        $this->assertCount(0, $results);

        // _ should be treated literally
        $results = $service->search($user->id, '_');
        $this->assertCount(0, $results);

        // Normal search works
        $results = $service->search($user->id, 'test');
        $this->assertCount(1, $results);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Attachment MIME Whitelist (HIGH: only blocklist was enforced)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function attachment_download_enforces_whitelist_not_blocklist(): void
    {
        // The fix changed from a blocklist to a whitelist.
        // Verify that the whitelist approach is more restrictive.
        // This is a code-structure test: the allowedMimeTypes array must be checked.

        $reflection = new \ReflectionClass(AttachmentList::class);
        $method = $reflection->getMethod('download');
        $source = file_get_contents($method->getFileName());

        // Verify the whitelist is checked (not just the blocklist)
        $this->assertStringContainsString('in_array($mimeType, $allowedMimeTypes)', $source);
        // Verify the old blocklist-only pattern is gone
        $this->assertStringNotContainsString('in_array($mimeType, $blockedMimeTypes)', $source);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Session Encryption (HIGH: SESSION_ENCRYPT defaults to true)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function security_configurator_enables_session_encryption(): void
    {
        // The SecurityConfigurator must set SESSION_ENCRYPT=true
        $reflection = new \ReflectionClass(SecurityConfigurator::class);
        $method = $reflection->getMethod('apply');
        $source = file_get_contents($method->getFileName());

        $this->assertStringContainsString("'SESSION_ENCRYPT'      => 'true'", $source);
        $this->assertStringNotContainsString("'SESSION_ENCRYPT'      => 'false'", $source);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Setup Wizard Credential Encryption (HIGH: plaintext in session)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function setup_wizard_encrypts_credentials_in_session(): void
    {
        $reflection = new \ReflectionClass(SetupWizard::class);
        $method = $reflection->getMethod('sessionPayload');
        $source = file_get_contents($method->getFileName());

        // Verify that sensitive fields are encrypted before storage
        $this->assertStringContainsString('Crypt::encryptString($this->dbPassword)', $source);
        $this->assertStringContainsString('Crypt::encryptString($this->imapPassword)', $source);
        $this->assertStringContainsString('Crypt::encryptString($this->smtpPassword)', $source);
        $this->assertStringContainsString('Crypt::encryptString($this->adminPassword)', $source);
    }

    /** @test */
    public function setup_wizard_decrypts_credentials_on_restore(): void
    {
        $reflection = new \ReflectionClass(SetupWizard::class);
        $method = $reflection->getMethod('restoreFromSession');
        $source = file_get_contents($method->getFileName());

        // Verify that sensitive fields are decrypted on restore
        $this->assertStringContainsString('Crypt::decryptString($value)', $source);
        // Verify error handling for failed decryption
        $this->assertStringContainsString('catch (\Throwable)', $source);
    }

    // ════════════════════════════════════════════════════════════════════════
    // IDOR Prevention (HIGH: LabelService missing user_id check)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function label_service_requires_user_id_on_apply_to_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $labelService = app(LabelService::class);

        // User1 creates a label
        $label1 = $labelService->create($user1->id, 'Private', '#2563EB');

        // User2 creates a label
        $label2 = $labelService->create($user2->id, 'Work', '#16A34A');

        // User1 creates messages
        $msg1 = MessageMetadata::factory()->create(['user_id' => $user1->id]);

        // User1 should be able to apply their own label
        $labelService->applyToMessages($label1->id, [$msg1->id], $user1->id);
        $this->assertCount(1, $msg1->fresh()->labels);

        // User1 should NOT be able to apply user2's label (IDOR)
        $this->expectException(ModelNotFoundException::class);
        $labelService->applyToMessages($label2->id, [$msg1->id], $user1->id);
    }

    /** @test */
    public function label_service_requires_user_id_on_remove_from_messages(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $labelService = app(LabelService::class);

        // User1 creates a label and applies it
        $label1 = $labelService->create($user1->id, 'Work', '#2563EB');
        $msg1 = MessageMetadata::factory()->create(['user_id' => $user1->id]);
        $labelService->applyToMessages($label1->id, [$msg1->id], $user1->id);

        // User2 tries to remove user1's label (IDOR) — should fail
        $this->expectException(ModelNotFoundException::class);
        $labelService->removeFromMessages($label1->id, [$msg1->id], $user2->id);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Health Endpoint (LOW: database connectivity check)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function health_endpoint_returns_200_when_database_accessible(): void
    {
        $response = $this->get('/up');
        $response->assertStatus(200);
        $response->assertContent('OK');
    }

    /** @test */
    public function health_endpoint_checks_database_connectivity(): void
    {
        // Verify the /up endpoint actually queries the database by checking source
        $routePath = base_path('routes/web.php');
        $source = file_get_contents($routePath);
        $this->assertStringContainsString("DB::select('SELECT 1')", $source);
    }

    // ════════════════════════════════════════════════════════════════════════
    // Search LIKE Injection (MEDIUM: SearchService LIKE wildcards not escaped)
    // ════════════════════════════════════════════════════════════════════════

    /** @test */
    public function search_service_escapes_like_wildcards(): void
    {
        $user = User::factory()->create();

        // Create messages with specific content
        MessageMetadata::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Test message',
            'snippet' => 'Hello world',
        ]);
        MessageMetadata::factory()->create([
            'user_id' => $user->id,
            'subject' => 'Another message',
            'snippet' => 'Goodbye world',
        ]);

        $searchService = app(SearchService::class);

        // % should be treated literally, not as a wildcard
        $results = $searchService->search($user->id, '%');
        $this->assertCount(0, $results);

        // _ should be treated literally, not as a single-char wildcard
        $results = $searchService->search($user->id, '_');
        $this->assertCount(0, $results);

        // Normal search should work
        $results = $searchService->search($user->id, 'test');
        $this->assertGreaterThanOrEqual(1, $results->total());
    }

    /** @test */
    public function search_service_query_sanitizer_removes_boolean_operators(): void
    {
        $searchService = app(SearchService::class);

        // FULLTEXT boolean operators should be stripped
        $this->assertEquals('test query', $searchService->sanitizeQuery('test +query'));
        $this->assertEquals('test query', $searchService->sanitizeQuery('test -query'));
        $this->assertEquals('test query', $searchService->sanitizeQuery('test >query'));
        $this->assertEquals('test query', $searchService->sanitizeQuery('test <query'));
        $this->assertEquals('test query', $searchService->sanitizeQuery('test *query'));
        $this->assertEquals('test query', $searchService->sanitizeQuery('test "query"'));
    }
}
