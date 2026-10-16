<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\ConfigurationWriter;
use App\Models\Signature;
use App\Models\User;
use App\Services\MailTransportService;
use App\Services\OutboundConnectionGuard;
use App\Services\SearchService;
use App\Services\VCardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryAndSecurityRemediationTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_transport_service_builds_unicode_and_prevents_bcc_leakage(): void
    {
        $transport = new MailTransportService;
        $user = User::factory()->create(['email' => 'sender@example.com', 'name' => 'René Démon']);

        $data = [
            'to' => 'alice@example.com',
            'cc' => 'bob@example.com',
            'bcc' => 'secret-bcc@example.com',
            'subject' => 'Meeting 🚀 Conférence',
            'body' => '<p>Bonjour! ✉️</p>',
            'bodyText' => 'Bonjour! ✉️',
            'in_reply_to' => '<msg-original@example.com>',
            'references' => '<msg-root@example.com> <msg-original@example.com>',
            'attachments' => [],
        ];

        $email = $transport->createEmail($data, (string) $user->id, '<stable-msg-id@example.com>');

        // Check stable Message-ID
        $this->assertEquals('<stable-msg-id@example.com>', (string) $email->getHeaders()->get('Message-ID')?->getBodyAsString());

        // Check Unicode subject
        $this->assertEquals('Meeting 🚀 Conférence', $email->getSubject());

        // Check reply headers
        $this->assertEquals('<msg-original@example.com>', (string) $email->getHeaders()->get('In-Reply-To')?->getBodyAsString());
        $this->assertEquals('<msg-root@example.com> <msg-original@example.com>', (string) $email->getHeaders()->get('References')?->getBodyAsString());

        // Check envelope vs visible headers:
        // Visible headers should have To, Cc
        $this->assertNotEmpty($email->getTo());
        $this->assertNotEmpty($email->getCc());

        // Raw serialized string must NOT contain visible Bcc header (RFC privacy)
        $rawMime = $email->toString();
        $this->assertStringNotContainsString("\nBcc: secret-bcc@example.com", $rawMime);
        $this->assertStringNotContainsString("\r\nBcc: secret-bcc@example.com", $rawMime);

        // But envelope recipients must include Bcc
        $envelopeRecipients = $transport->getEnvelopeRecipients($email, ['secret-bcc@example.com']);
        $recipientEmails = array_map(fn ($r) => $r->getAddress(), $envelopeRecipients);
        $this->assertContains('alice@example.com', $recipientEmails);
        $this->assertContains('bob@example.com', $recipientEmails);
        $this->assertContains('secret-bcc@example.com', $recipientEmails);
    }

    public function test_outbound_connection_guard_blocks_ssrf_and_private_targets(): void
    {
        $guard = new OutboundConnectionGuard;

        // Loopback
        $this->expectException(\InvalidArgumentException::class);
        $guard->validateTarget('127.0.0.1', 587, [587]);
    }

    public function test_outbound_connection_guard_blocks_metadata_ip(): void
    {
        $guard = new OutboundConnectionGuard;

        $this->expectException(\InvalidArgumentException::class);
        $guard->validateTarget('169.254.169.254', 587, [587]);
    }

    public function test_outbound_connection_guard_blocks_unauthorized_ports(): void
    {
        $guard = new OutboundConnectionGuard;

        // Port 22 SSH not in allowed SMTP/IMAP list
        $this->expectException(\InvalidArgumentException::class);
        $guard->validateTarget('mail.example.com', 22, [25, 465, 587, 2525]);
    }

    public function test_search_highlight_matches_escapes_xss_in_snippets(): void
    {
        $searchService = new SearchService;

        $maliciousSnippet = '&lt;script&gt;alert("XSS")&lt;/script&gt; Important invoice notification';
        $query = 'invoice';

        $highlighted = $searchService->highlightMatches($maliciousSnippet, $query);

        // Must not contain raw executable script tags
        $this->assertStringNotContainsString('<script>', $highlighted);
        $this->assertStringContainsString('&lt;script&gt;', $highlighted);

        // Highlight tag must be wrapped around the query term safely
        $this->assertStringContainsString('<mark class="bg-yellow-100 text-yellow-900 px-0.5 rounded">invoice</mark>', $highlighted);
    }

    public function test_signature_pure_php_renderer_and_sanitizer(): void
    {
        $user = User::factory()->create();

        $tiptapJson = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Best regards,',
                        ],
                    ],
                ],
                [
                    'type' => 'paragraph',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Alice Smith',
                            'marks' => [
                                ['type' => 'bold'],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $sig = Signature::create([
            'user_id' => $user->id,
            'name' => 'Professional',
            'content_json' => $tiptapJson,
            'is_default' => true,
        ]);

        $this->assertNotNull($sig->content_html);
        $this->assertStringContainsString('<p>Best regards,</p>', $sig->content_html);
        $this->assertStringContainsString('<strong>Alice Smith</strong>', $sig->content_html);
        // Ensure no raw JSON was stored
        $this->assertStringNotContainsString('"type":', $sig->content_html);
    }

    public function test_vcard_service_bounds_large_inputs(): void
    {
        $service = new VCardService;
        $user = User::factory()->create();

        $hugePayload = str_repeat("BEGIN:VCARD\r\nVERSION:3.0\r\nFN:A\r\nEND:VCARD\r\n", 200000);

        $this->expectException(\InvalidArgumentException::class);
        $service->import($hugePayload, $user->id);
    }

    public function test_configuration_writer_creates_secure_permissions(): void
    {
        $writer = new ConfigurationWriter;
        $envPath = base_path('.env');

        $success = $writer->write(['TEST_KEY' => 'test_value_123']);
        $this->assertTrue($success);

        $this->assertEquals('test_value_123', $writer->read('TEST_KEY'));

        if (file_exists($envPath)) {
            $perms = fileperms($envPath) & 0777;
            // Permissions should be 0600 on POSIX
            $this->assertEquals(0600, $perms);
        }
    }
}
