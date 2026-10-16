<?php

namespace App\Services;

use InvalidArgumentException;

class OutboundConnectionGuard
{
    /**
     * Validate an outbound connection target (host and port) against SSRF policies.
     *
     * @param  string  $host  The hostname or IP address to connect to
     * @param  int  $port  The target port (1 - 65535)
     * @param  array<int>  $allowedPorts  Optional list of permitted ports
     * @param  bool|null  $allowInternal  Whether to permit private/internal IPs
     *
     * @throws InvalidArgumentException If host or port violates security policy
     */
    public function validateTarget(
        string $host,
        int $port,
        array $allowedPorts = [],
        ?bool $allowInternal = null
    ): void {
        $trimmedHost = trim($host, "[] \t\n\r\0\x0B");

        if ($trimmedHost === '') {
            throw new InvalidArgumentException('Target host cannot be empty.');
        }

        if ($port < 1 || $port > 65535) {
            throw new InvalidArgumentException("Invalid port: {$port}. Must be between 1 and 65535.");
        }

        if (! empty($allowedPorts) && ! in_array($port, $allowedPorts, true)) {
            throw new InvalidArgumentException("Port {$port} is not in the allowed ports list.");
        }

        // Check if internal/private hosts are explicitly permitted
        $allowInternalHosts = $allowInternal ?? (bool) config('openmail.allow_internal_hosts', false);

        if ($allowInternalHosts) {
            return;
        }

        // Check for direct IP address or encoded forms
        if ($this->isInternalOrLoopbackHost($trimmedHost)) {
            throw new InvalidArgumentException("Connection to internal or reserved host '{$trimmedHost}' is prohibited.");
        }

        // If it's a hostname, resolve DNS to prevent DNS rebinding to internal addresses
        if (! filter_var($trimmedHost, FILTER_VALIDATE_IP)) {
            $ips = @gethostbynamel($trimmedHost);
            if ($ips === false || empty($ips)) {
                // If it fails to resolve, let the transport handle resolution failure,
                // but if it matches localhost patterns, reject immediately.
                if (strcasecmp($trimmedHost, 'localhost') === 0 || str_ends_with(strtolower($trimmedHost), '.localhost')) {
                    throw new InvalidArgumentException('Connection to localhost is prohibited.');
                }

                return;
            }

            foreach ($ips as $ip) {
                if ($this->isInternalIp($ip)) {
                    throw new InvalidArgumentException(
                        "Resolved IP '{$ip}' for host '{$trimmedHost}' is in a private or reserved address range."
                    );
                }
            }
        }
    }

    /**
     * Determine if a hostname or literal IP represents internal/loopback.
     */
    public function isInternalOrLoopbackHost(string $host): bool
    {
        $normalized = strtolower(trim($host, "[] \t\n\r\0\x0B"));

        if ($normalized === 'localhost' || $normalized === '0.0.0.0' || $normalized === '::1' || $normalized === '::') {
            return true;
        }

        // Decimal IP
        if (ctype_digit($normalized)) {
            $long = (int) $normalized;
            if ($long >= 0 && $long <= 4294967295) {
                return $this->isInternalIp(long2ip($long));
            }
        }

        // Hex IP
        if (str_starts_with($normalized, '0x')) {
            return true;
        }

        // Octal IP
        if (preg_match('/^0\d+(\.|$)/', $normalized)) {
            return true;
        }

        // Direct IP check
        if (filter_var($normalized, FILTER_VALIDATE_IP)) {
            return $this->isInternalIp($normalized);
        }

        return false;
    }

    /**
     * Check if an IP address is in private, reserved, loopback, or link-local ranges.
     */
    public function isInternalIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
