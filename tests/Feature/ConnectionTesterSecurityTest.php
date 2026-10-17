<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\ImapConnectionTester;
use App\Services\OutboundConnectionGuard;
use App\Services\SmtpConnectionTester;
use InvalidArgumentException;
use Mockery;
use Tests\TestCase;

class ConnectionTesterSecurityTest extends TestCase
{
    public function test_imap_connection_tester_rejects_private_host_before_network_access(): void
    {
        $result = app(ImapConnectionTester::class)->test('127.0.0.1', 993, 'ssl', 'user', 'secret');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Security validation failed', $result['error']);
    }

    public function test_imap_connection_tester_rejects_a_non_imap_port(): void
    {
        $result = app(ImapConnectionTester::class)->test('8.8.8.8', 443, 'ssl', 'user', 'secret');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not in the allowed ports list', $result['error']);
    }

    public function test_smtp_connection_tester_rejects_private_host_before_network_access(): void
    {
        $result = app(SmtpConnectionTester::class)->test('127.0.0.1', 587, 'tls', 'user', 'secret');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Connection to internal or reserved host', $result['error']);
        $this->assertSame(InvalidArgumentException::class, $result['technical']['exception']);
    }

    public function test_smtp_connection_tester_rejects_a_non_smtp_port(): void
    {
        $result = app(SmtpConnectionTester::class)->test('8.8.8.8', 993, 'ssl', 'user', 'secret');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not in the allowed ports list', $result['error']);
    }
}
