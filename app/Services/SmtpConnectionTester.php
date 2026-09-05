<?php

namespace App\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

class SmtpConnectionTester
{
    public function test(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        try {
            $scheme = $encryption === 'ssl' ? 'smtps' : ($encryption === 'tls' ? 'smtp' : 'smtp');
            $dsn = "{$scheme}://{$username}:{$password}@{$host}:{$port}";

            $transport = new EsmtpTransport($dsn);

            // Attempt connection + EHLO
            $transport->checkConnection();

            return ['success' => true];
        } catch (TransportExceptionInterface $e) {
            return [
                'success' => false,
                'error' => 'Could not connect to SMTP server. Please check host, port, and encryption settings.',
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'SMTP authentication failed. Please check your email and password.',
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }
}