<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\InstallationVerifier;
use Tests\TestCase;

class InstallationVerifierTest extends TestCase
{
    public function test_verifier_returns_all_seven_diagnostics_when_mail_credentials_are_missing(): void
    {
        config([
            'openmail.imap.host' => 'imap.example.test',
            'openmail.imap.port' => 993,
            'openmail.imap.encryption' => 'ssl',
            'openmail.smtp.host' => 'smtp.example.test',
            'openmail.smtp.port' => 465,
            'openmail.smtp.encryption' => 'ssl',
            'app.key' => 'base64:valid-test-key',
        ]);

        $checks = app(InstallationVerifier::class)->verify([]);

        $this->assertCount(7, $checks);
        $byName = collect($checks)->keyBy('name');
        $this->assertSame(
            ['config_readable', 'db_connection', 'imap_connection', 'smtp_connection', 'filesystem_writable', 'app_key_exists', 'php_version'],
            array_keys($byName->all())
        );
        $this->assertTrue($byName['db_connection']['passed']);
        $this->assertFalse($byName['imap_connection']['passed']);
        $this->assertSame('IMAP credentials not configured', $byName['imap_connection']['error']);
        $this->assertFalse($byName['smtp_connection']['passed']);
        $this->assertSame('SMTP credentials not configured', $byName['smtp_connection']['error']);
        $this->assertTrue($byName['app_key_exists']['passed']);
    }

    public function test_verifier_reports_a_missing_application_key(): void
    {
        config(['app.key' => 'base64:']);
        $checks = collect(app(InstallationVerifier::class)->verify([]))->keyBy('name');

        $this->assertFalse($checks['app_key_exists']['passed']);
        $this->assertSame('APP_KEY is not set in .env', $checks['app_key_exists']['error']);
    }
}
