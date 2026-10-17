<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\OutboundConnectionGuard;
use InvalidArgumentException;
use Tests\TestCase;

class OutboundConnectionGuardTest extends TestCase
{
    private OutboundConnectionGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->guard = new OutboundConnectionGuard;
    }

    public function test_rejects_empty_hosts_and_invalid_ports_before_network_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Target host cannot be empty.');
        $this->guard->validateTarget(" \t ", 993);
    }

    public function test_rejects_ports_outside_the_tcp_range(): void
    {
        foreach ([0, 65536] as $port) {
            try {
                $this->guard->validateTarget('8.8.8.8', $port);
                $this->fail("Port {$port} should be rejected.");
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('Invalid port', $exception->getMessage());
            }
        }
    }

    public function test_rejects_ports_not_in_the_explicit_allowlist(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Port 80 is not in the allowed ports list.');
        $this->guard->validateTarget('8.8.8.8', 80, [143, 993]);
    }

    public function test_allows_a_public_literal_ip_on_an_allowed_port(): void
    {
        $this->guard->validateTarget('8.8.8.8', 993, [143, 993]);
        $this->assertTrue(true);
    }

    public function test_rejects_private_loopback_link_local_and_reserved_ip_addresses(): void
    {
        foreach (['127.0.0.1', '10.0.0.8', '172.16.1.3', '192.168.1.10', '169.254.169.254', '::1'] as $host) {
            try {
                $this->guard->validateTarget($host, 993);
                $this->fail("Internal or reserved host {$host} should be rejected.");
            } catch (InvalidArgumentException $exception) {
                $this->assertStringContainsString('prohibited', strtolower($exception->getMessage()));
            }
        }
    }

    public function test_internal_hosts_can_only_be_allowed_when_explicitly_configured(): void
    {
        $this->guard->validateTarget('192.168.1.10', 993, [993], true);
        $this->assertTrue(true);
    }

    public function test_internal_host_allowance_does_not_skip_port_validation(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Port 80 is not in the allowed ports list.');
        $this->guard->validateTarget('192.168.1.10', 80, [993], true);
    }

    public function test_identifies_legacy_numeric_hex_and_octal_ip_forms_as_internal(): void
    {
        foreach (['2130706433', '0x7f000001', '0177.0.0.1'] as $host) {
            $this->assertTrue($this->guard->isInternalOrLoopbackHost($host), $host);
        }
    }

    public function test_classifies_public_and_private_ip_addresses(): void
    {
        $this->assertFalse($this->guard->isInternalIp('8.8.8.8'));
        $this->assertTrue($this->guard->isInternalIp('127.0.0.1'));
        $this->assertTrue($this->guard->isInternalOrLoopbackHost('[::1]'));
        $this->assertTrue($this->guard->isInternalOrLoopbackHost('localhost'));
        $this->assertFalse($this->guard->isInternalOrLoopbackHost('example.com'));
    }
}
