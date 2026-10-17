<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\MailConfigDetector;
use Tests\TestCase;

class MailConfigDetectorTest extends TestCase
{
    private MailConfigDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new MailConfigDetector;
    }

    public function test_detects_gmail_and_googlemail_aliases(): void
    {
        $gmail = $this->detector->detect('person@gmail.com');
        $googlemail = $this->detector->detect('person@googlemail.com');

        $this->assertSame('Gmail', $gmail['name']);
        $this->assertSame('imap.gmail.com', $gmail['imap_host']);
        $this->assertSame(993, $gmail['imap_port']);
        $this->assertSame('ssl', $gmail['imap_encryption']);
        $this->assertSame('smtp.gmail.com', $gmail['smtp_host']);
        $this->assertSame(465, $gmail['smtp_port']);
        $this->assertSame($gmail, $googlemail);
    }

    public function test_detects_outlook_aliases_and_starttls_smtp_settings(): void
    {
        foreach (['outlook.com', 'hotmail.com', 'live.com'] as $domain) {
            $settings = $this->detector->detect('user@'.$domain);
            $this->assertSame('Microsoft Outlook', $settings['name']);
            $this->assertSame('outlook.office365.com', $settings['imap_host']);
            $this->assertSame('smtp.office365.com', $settings['smtp_host']);
            $this->assertSame(587, $settings['smtp_port']);
            $this->assertSame('tls', $settings['smtp_encryption']);
        }
    }

    public function test_detects_yahoo_aliases(): void
    {
        foreach (['yahoo.com', 'ymail.com'] as $domain) {
            $settings = $this->detector->detect('user@'.$domain);
            $this->assertSame('Yahoo Mail', $settings['name']);
            $this->assertSame('imap.mail.yahoo.com', $settings['imap_host']);
            $this->assertSame('smtp.mail.yahoo.com', $settings['smtp_host']);
            $this->assertSame(465, $settings['smtp_port']);
        }
    }

    public function test_unknown_domains_receive_custom_mail_server_defaults(): void
    {
        $settings = $this->detector->detect('person@department.example');

        $this->assertSame('Custom', $settings['name']);
        $this->assertSame('mail.department.example', $settings['imap_host']);
        $this->assertSame(993, $settings['imap_port']);
        $this->assertSame('ssl', $settings['imap_encryption']);
        $this->assertSame('smtp.department.example', $settings['smtp_host']);
        $this->assertSame(465, $settings['smtp_port']);
        $this->assertSame('ssl', $settings['smtp_encryption']);
    }

    public function test_provider_lookup_is_case_insensitive(): void
    {
        $this->assertSame(
            $this->detector->detect('person@gmail.com'),
            $this->detector->detect('person@GMAIL.COM')
        );
    }
}
