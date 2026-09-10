<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class InstallationVerifier
{
    public function verify(array $config = []): array
    {
        $checks = [];

        // 1. Config file exists and valid (.env readable)
        $checks[] = $this->checkConfigReadable();

        // 2. Database connection works
        $checks[] = $this->checkDatabaseConnection();

        // 3. IMAP connection works
        $checks[] = $this->checkImapConnection($config);

        // 4. SMTP connection works
        $checks[] = $this->checkSmtpConnection($config);

        // 5. Filesystem writable
        $checks[] = $this->checkFilesystemWritable();

        // 6. APP_KEY exists
        $checks[] = $this->checkAppKeyExists();

        // 7. PHP version adequate
        $checks[] = $this->checkPhpVersion();

        return $checks;
    }

    private function checkConfigReadable(): array
    {
        $envPath = base_path('.env');
        $readable = File::exists($envPath) && File::isReadable($envPath);

        return [
            'name' => 'config_readable',
            'label' => 'Configuration file readable',
            'passed' => $readable,
            'error' => $readable ? null : '.env file not found or not readable',
            'fixable' => false,
            'fixStep' => null,
        ];
    }

    private function checkDatabaseConnection(): array
    {
        try {
            DB::connection()->getPdo();

            return [
                'name' => 'db_connection',
                'label' => 'Database connection',
                'passed' => true,
                'error' => null,
                'fixable' => true,
                'fixStep' => 3,
            ];
        } catch (\Exception $e) {
            return [
                'name' => 'db_connection',
                'label' => 'Database connection',
                'passed' => false,
                'error' => 'Database connection failed. Please check your database configuration.',
                'technical' => [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ],
                'fixable' => true,
                'fixStep' => 3,
            ];
        }
    }

    private function checkImapConnection(array $config): array
    {
        $host = $config['imapHost'] ?? config('openmail.imap.host');
        $port = $config['imapPort'] ?? config('openmail.imap.port');
        $encryption = $config['imapEncryption'] ?? config('openmail.imap.encryption');
        $username = $config['imapUsername'] ?? '';
        $password = $config['imapPassword'] ?? '';

        if (! $username || ! $password) {
            return [
                'name' => 'imap_connection',
                'label' => 'IMAP connection',
                'passed' => false,
                'error' => 'IMAP credentials not configured',
                'fixable' => true,
                'fixStep' => 4,
            ];
        }

        $tester = app(ImapConnectionTester::class);
        $result = $tester->test($host, $port, $encryption, $username, $password);

        return [
            'name' => 'imap_connection',
            'label' => 'IMAP connection',
            'passed' => $result['success'],
            'error' => $result['success'] ? null : $result['error'],
            'technical' => $result['technical'] ?? null,
            'fixable' => true,
            'fixStep' => 4,
        ];
    }

    private function checkSmtpConnection(array $config): array
    {
        $host = $config['smtpHost'] ?? config('openmail.smtp.host');
        $port = $config['smtpPort'] ?? config('openmail.smtp.port');
        $encryption = $config['smtpEncryption'] ?? config('openmail.smtp.encryption');
        $username = $config['smtpUsername'] ?? '';
        $password = $config['smtpPassword'] ?? '';

        if (! $username || ! $password) {
            return [
                'name' => 'smtp_connection',
                'label' => 'SMTP connection',
                'passed' => false,
                'error' => 'SMTP credentials not configured',
                'fixable' => true,
                'fixStep' => 4,
            ];
        }

        $tester = app(SmtpConnectionTester::class);
        $result = $tester->test($host, $port, $encryption, $username, $password);

        return [
            'name' => 'smtp_connection',
            'label' => 'SMTP connection',
            'passed' => $result['success'],
            'error' => $result['success'] ? null : $result['error'],
            'technical' => $result['technical'] ?? null,
            'fixable' => true,
            'fixStep' => 4,
        ];
    }

    private function checkFilesystemWritable(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        $allWritable = true;
        foreach ($paths as $path) {
            if (! is_writable($path)) {
                $allWritable = false;
                break;
            }
        }

        return [
            'name' => 'filesystem_writable',
            'label' => 'Filesystem writable',
            'passed' => $allWritable,
            'error' => $allWritable ? null : 'Storage or bootstrap/cache directory is not writable',
            'fixable' => false,
            'fixStep' => null,
        ];
    }

    private function checkAppKeyExists(): array
    {
        $appKey = config('app.key');
        $hasKey = ! empty($appKey) && $appKey !== 'base64:';

        return [
            'name' => 'app_key_exists',
            'label' => 'Application key (APP_KEY)',
            'passed' => $hasKey,
            'error' => $hasKey ? null : 'APP_KEY is not set in .env',
            'fixable' => true,
            'fixStep' => 1,
        ];
    }

    private function checkPhpVersion(): array
    {
        $passed = version_compare(PHP_VERSION, '8.2.0', '>=');

        return [
            'name' => 'php_version',
            'label' => 'PHP Version (≥8.2)',
            'passed' => $passed,
            'error' => $passed ? null : 'PHP 8.2 or higher is required. Current: '.PHP_VERSION,
            'fixable' => false,
            'fixStep' => null,
        ];
    }
}
