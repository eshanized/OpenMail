<?php

declare(strict_types=1);

namespace App\Services;

class SystemRequirementsChecker
{
    // ──────────────────────────────────────────────────────────────────────────
    // Main entry point
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Run all requirement checks and return a structured result array.
     *
     * Each entry has:
     *   label   – human-readable name
     *   type    – 'required' | 'recommended'
     *   passed  – bool
     *   current – string representation of current value (or null)
     *   required – string representation of required value (or null)
     *   error   – null if passed, otherwise human-readable error message
     *   fix     – null or actionable remediation advice
     */
    public function check(): array
    {
        return [
            'php_version' => $this->checkPhpVersion(),
            'extensions' => $this->checkExtensions(),
            'writable' => $this->checkPermissions(),
            'database' => $this->checkDatabaseDriver(),
            'recommended' => $this->checkRecommended(),
        ];
    }

    /**
     * Returns true only if all *required* checks passed.
     */
    public function allRequiredPassed(array $result): bool
    {
        if (! $result['php_version']['passed']) {
            return false;
        }

        if (! $result['database']['passed']) {
            return false;
        }

        foreach ($result['extensions'] as $ext) {
            if (! $ext['passed']) {
                return false;
            }
        }

        foreach ($result['writable'] as $path) {
            if (! $path['passed']) {
                return false;
            }
        }

        return true;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Required checks
    // ──────────────────────────────────────────────────────────────────────────

    private function checkPhpVersion(): array
    {
        $required = '8.2.0';
        $current = PHP_VERSION;
        $passed = version_compare($current, $required, '>=');

        return [
            'label' => 'PHP Version',
            'type' => 'required',
            'passed' => $passed,
            'current' => $current,
            'required' => '≥ '.$required,
            'error' => $passed ? null : "PHP {$required} or higher is required (current: {$current}).",
            'fix' => $passed ? null : 'Select a newer PHP version in your cPanel PHP Selector, XAMPP configuration, or server PHP config.',
        ];
    }

    private function checkExtensions(): array
    {
        $required = [
            'mbstring' => 'Multibyte string handling (required for email encoding)',
            'openssl' => 'SSL/TLS support (required for IMAP/SMTP)',
            'pdo' => 'PHP Data Objects (required for database)',
            'pdo_mysql' => 'MySQL PDO driver (required for MySQL database)',
            'curl' => 'cURL HTTP client (required for HTTP operations)',
            'xml' => 'XML parsing (required for email parsing)',
            'tokenizer' => 'PHP tokenizer (required by Laravel)',
            'fileinfo' => 'File type detection (required for attachments)',
            'json' => 'JSON encoding/decoding (required by Laravel)',
            'bcmath' => 'Arbitrary precision math (required by Laravel)',
        ];

        return array_map(function (string $ext, string $description) {
            $passed = extension_loaded($ext);

            return [
                'label' => "ext-{$ext}",
                'description' => $description,
                'type' => 'required',
                'passed' => $passed,
                'current' => $passed ? 'Loaded' : 'Not loaded',
                'required' => 'Loaded',
                'error' => $passed ? null : "The PHP extension '{$ext}' is not installed.",
                'fix' => $passed ? null : $this->extensionFix($ext),
            ];
        }, array_keys($required), $required);
    }

    private function checkPermissions(): array
    {
        $paths = [
            'storage' => storage_path(),
            'storage/logs' => storage_path('logs'),
            'storage/framework/cache' => storage_path('framework/cache'),
            'storage/framework/sessions' => storage_path('framework/sessions'),
            'storage/framework/views' => storage_path('framework/views'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        return array_map(function (string $name, string $path) {
            // Create sub-directories if they don't exist yet
            if (! is_dir($path)) {
                @mkdir($path, 0755, true);
            }

            $passed = is_writable($path);

            return [
                'label' => $name,
                'type' => 'required',
                'passed' => $passed,
                'current' => $passed ? 'Writable' : 'Not writable',
                'required' => 'Writable',
                'error' => $passed ? null : "The directory '{$path}' is not writable.",
                'fix' => $passed ? null : "Run: chmod -R 775 {$path}\nOr in cPanel File Manager, right-click → Change Permissions → set to 775.",
            ];
        }, array_keys($paths), $paths);
    }

    private function checkDatabaseDriver(): array
    {
        $passed = extension_loaded('pdo_mysql');

        return [
            'label' => 'MySQL/MariaDB PDO Driver',
            'type' => 'required',
            'passed' => $passed,
            'current' => $passed ? 'Available' : 'Not available',
            'required' => 'Available',
            'error' => $passed ? null : 'The pdo_mysql PHP extension is not installed.',
            'fix' => $passed ? null : 'Enable pdo_mysql in your PHP configuration or contact your host.',
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Recommended checks
    // ──────────────────────────────────────────────────────────────────────────

    private function checkRecommended(): array
    {
        return [
            $this->checkMemoryLimit(),
            $this->checkUploadMaxFilesize(),
            $this->checkPostMaxSize(),
            $this->checkMaxExecutionTime(),
            $this->checkEnvWritable(),
        ];
    }

    private function checkMemoryLimit(): array
    {
        $raw = ini_get('memory_limit');
        $bytes = $this->parseIniBytes($raw);
        $requiredMb = 128;
        $passed = $bytes === -1 || $bytes >= $requiredMb * 1024 * 1024;

        return [
            'label' => 'memory_limit',
            'type' => 'recommended',
            'passed' => $passed,
            'current' => $raw,
            'required' => "≥ {$requiredMb}M",
            'error' => $passed ? null : "memory_limit is {$raw}, recommend ≥ {$requiredMb}M.",
            'fix' => $passed ? null : "Add 'memory_limit = 256M' to your php.ini or .htaccess.",
        ];
    }

    private function checkUploadMaxFilesize(): array
    {
        $raw = ini_get('upload_max_filesize');
        $bytes = $this->parseIniBytes($raw);
        $requiredMb = 8;
        $passed = $bytes >= $requiredMb * 1024 * 1024;

        return [
            'label' => 'upload_max_filesize',
            'type' => 'recommended',
            'passed' => $passed,
            'current' => $raw,
            'required' => "≥ {$requiredMb}M",
            'error' => $passed ? null : "upload_max_filesize is {$raw}, recommend ≥ {$requiredMb}M for email attachments.",
            'fix' => $passed ? null : "Add 'upload_max_filesize = 25M' to php.ini or .htaccess.",
        ];
    }

    private function checkPostMaxSize(): array
    {
        $raw = ini_get('post_max_size');
        $bytes = $this->parseIniBytes($raw);
        $requiredMb = 8;
        $passed = $bytes >= $requiredMb * 1024 * 1024;

        return [
            'label' => 'post_max_size',
            'type' => 'recommended',
            'passed' => $passed,
            'current' => $raw,
            'required' => "≥ {$requiredMb}M",
            'error' => $passed ? null : "post_max_size is {$raw}, recommend ≥ {$requiredMb}M.",
            'fix' => $passed ? null : "Add 'post_max_size = 25M' to php.ini or .htaccess.",
        ];
    }

    private function checkMaxExecutionTime(): array
    {
        $raw = (int) ini_get('max_execution_time');
        $passed = $raw === 0 || $raw >= 30;

        return [
            'label' => 'max_execution_time',
            'type' => 'recommended',
            'passed' => $passed,
            'current' => $raw === 0 ? 'unlimited' : "{$raw}s",
            'required' => '≥ 30s',
            'error' => $passed ? null : "max_execution_time is {$raw}s, recommend ≥ 30s.",
            'fix' => $passed ? null : "Add 'max_execution_time = 120' to php.ini or .htaccess.",
        ];
    }

    private function checkEnvWritable(): array
    {
        $envPath = base_path('.env');
        $passed = file_exists($envPath) ? is_writable($envPath) : is_writable(dirname($envPath));

        return [
            'label' => '.env file writable',
            'type' => 'recommended',
            'passed' => $passed,
            'current' => $passed ? 'Writable' : 'Not writable',
            'required' => 'Writable (for installer to save configuration)',
            'error' => $passed ? null : 'The .env file is not writable. You may need to configure settings manually.',
            'fix' => $passed ? null : 'Run: chmod 664 '.$envPath."\nOr create and edit .env manually.",
        ];
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Convert php.ini size string (e.g. "128M", "1G") to bytes.
     * Returns -1 for "unlimited" (-1).
     */
    private function parseIniBytes(string $value): int
    {
        $value = trim(strtolower($value));

        if ($value === '-1') {
            return -1;
        }

        $unit = substr($value, -1);
        $bytes = (int) $value;

        return match ($unit) {
            'g' => $bytes * 1024 * 1024 * 1024,
            'm' => $bytes * 1024 * 1024,
            'k' => $bytes * 1024,
            default => $bytes,
        };
    }

    private function extensionFix(string $ext): string
    {
        return match ($ext) {
            'pdo_mysql' => 'Enable pdo_mysql in php.ini: uncomment or add "extension=pdo_mysql". In cPanel, enable in PHP Extensions.',
            'mbstring' => 'Enable mbstring in php.ini. In cPanel, enable in PHP Extensions.',
            'openssl' => 'Enable openssl in php.ini. Usually pre-enabled on most hosts.',
            'curl' => 'Enable curl in php.ini. In cPanel, enable in PHP Extensions.',
            'xml' => 'Enable xml in php.ini. May require libxml2-dev on Linux.',
            'fileinfo' => 'Enable fileinfo in php.ini. In cPanel, enable in PHP Extensions.',
            default => "Enable the {$ext} extension in your PHP configuration.",
        };
    }
}
