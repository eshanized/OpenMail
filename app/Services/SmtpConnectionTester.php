<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SmtpConnectionTester
{
    /**
     * Test an SMTP connection without sending any email.
     *
     * Establishes a TCP connection to the SMTP server, performs the EHLO
     * handshake, and optionally authenticates \u2014 then closes the connection.
     * No message is sent.
     *
     * @param string $encryption 'ssl' (implicit TLS on port 465) | 'tls' (STARTTLS on 587) | 'none'
     * @return array{success: bool, error?: string, technical?: array}
     */
    public function test(
        string $host,
        int    $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        try {
            $tls = ($encryption === 'ssl');

            $transport = new EsmtpTransport(
                host:        $host,
                port:        $port,
                tls:         $tls,
                dispatcher:  null,
                logger:      null,
            );

            if ($username !== '') {
                $transport->setUsername($username);
            }
            if ($password !== '') {
                $transport->setPassword($password);
            }

            // start() performs the TCP connect + EHLO + AUTH (if credentials set)
            // without actually sending a message.
            $transport->start();
            $transport->stop();

            return ['success' => true];
        } catch (TransportExceptionInterface $e) {
            return [
                'success'   => false,
                'error'     => $this->friendlyTransportError($e->getMessage()),
                'technical' => [
                    'exception' => get_class($e),
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success'   => false,
                'error'     => $this->friendlyGenericError($e->getMessage()),
                'technical' => [
                    'exception' => get_class($e),
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage(),
                ],
            ];
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Error translation
    // ──────────────────────────────────────────────────────────────────────────

    private function friendlyTransportError(string $message): string
    {
        return match (true) {
            str_contains($message, 'Connection refused')
                => 'Could not connect to the SMTP server. Check that the host and port are correct and the server is running.',

            str_contains($message, 'Connection timed out') || str_contains($message, 'timed out')
                => 'SMTP connection timed out. The server may be unreachable or blocked by a firewall.',

            str_contains($message, 'Authentication') || str_contains($message, 'authentication')
                => 'SMTP authentication failed. Check your username and password.',

            str_contains($message, 'certificate') || str_contains($message, 'SSL') || str_contains($message, 'TLS')
                => 'SSL/TLS error connecting to SMTP server. Try a different encryption setting or check the server certificate.',

            str_contains($message, 'getaddrinfo') || str_contains($message, 'php_network_getaddresses')
                => 'Cannot resolve the SMTP server hostname. Check the host field.',

            default => 'SMTP connection failed. ' . $message,
        };
    }

    private function friendlyGenericError(string $message): string
    {
        if (str_contains($message, 'Connection refused') || str_contains($message, 'Connection timed out')) {
            return 'Could not reach the SMTP server. Check host, port, and firewall settings.';
        }
        if (str_contains($message, 'Authentication') || str_contains($message, 'authentication')) {
            return 'SMTP authentication failed. Check your username and password.';
        }
        return 'SMTP connection failed: ' . $message;
    }
}