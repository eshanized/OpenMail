<?php

$dir = '/home/snigdha/Desktop/OpenMail';

// Directories to create
@mkdir($dir . '/app/Install', 0777, true);
@mkdir($dir . '/app/Console/Commands', 0777, true);
@mkdir($dir . '/resources/views/livewire', 0777, true);
@mkdir($dir . '/resources/views/setup/steps', 0777, true);

// 1. InstallationState.php
file_put_contents($dir . '/app/Install/InstallationState.php', '<?php

declare(strict_types=1);

namespace App\Install;

enum InstallationState: string
{
    case NotInstalled = \'not_installed\';
    case Installing = \'installing\';
    case Installed = \'installed\';
    case Failed = \'failed\';

    public function isInstalled(): bool
    {
        return $this === self::Installed;
    }

    public function canInstall(): bool
    {
        return in_array($this, [self::NotInstalled, self::Failed]);
    }
}
');

// 2. InstallationLock.php
file_put_contents($dir . '/app/Install/InstallationLock.php', '<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Carbon;

class InstallationLock
{
    private string $lockFile;
    private string $progressFile;

    public function __construct()
    {
        $this->lockFile = storage_path(\'installed\');
        $this->progressFile = storage_path(\'framework/install_progress.json\');
    }

    public function state(): InstallationState
    {
        if ($this->isInstalled()) {
            return InstallationState::Installed;
        }
        
        $progress = $this->readProgress();
        if (isset($progress[\'state\'])) {
            return InstallationState::from($progress[\'state\']);
        }
        
        return InstallationState::NotInstalled;
    }

    public function isInstalled(): bool
    {
        return file_exists($this->lockFile);
    }

    public function markInstalling(): void
    {
        $this->writeProgress([\'state\' => InstallationState::Installing->value, \'started_at\' => now()->toIso8601String()]);
    }

    public function markInstalled(array $metadata = []): void
    {
        $data = array_merge([\'installed_at\' => now()->toIso8601String()], $metadata);
        
        // Atomic write using temp file + rename
        $tmpFile = $this->lockFile . \'.tmp.\' . getmypid();
        file_put_contents($tmpFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        if (rename($tmpFile, $this->lockFile) === false) {
            // Fallback: direct write with exclusive lock
            $fp = fopen($this->lockFile, \'c+\');
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }
        
        // Clean up progress file
        if (file_exists($this->progressFile)) {
            unlink($this->progressFile);
        }
    }

    public function markFailed(string $reason = \'\'): void
    {
        $progress = $this->readProgress();
        $progress[\'state\'] = InstallationState::Failed->value;
        $progress[\'failed_at\'] = now()->toIso8601String();
        $progress[\'reason\'] = $reason;
        $this->writeProgress($progress);
    }

    public function getMetadata(): array
    {
        if (!$this->isInstalled()) {
            return [];
        }
        $content = file_get_contents($this->lockFile);
        return json_decode($content, true) ?? [\'installed_at\' => $content];
    }

    public function readProgress(): array
    {
        if (!file_exists($this->progressFile)) {
            return [];
        }
        $content = file_get_contents($this->progressFile);
        return json_decode($content, true) ?? [];
    }

    private function writeProgress(array $data): void
    {
        $dir = dirname($this->progressFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->progressFile, json_encode($data, JSON_PRETTY_PRINT));
    }
}
');

// 3. ConfigurationWriter.php
file_put_contents($dir . '/app/Install/ConfigurationWriter.php', '<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Facades\Artisan;

class ConfigurationWriter
{
    private string $envPath;

    public function __construct()
    {
        $this->envPath = base_path(\'.env\');
    }

    /**
     * Write or update key-value pairs in .env file.
     * Values are properly quoted if they contain special characters.
     */
    public function write(array $values): bool
    {
        $content = file_exists($this->envPath) ? file_get_contents($this->envPath) : \'\';
        
        $lines = $content !== \'\' ? explode("\n", rtrim($content, "\n")) : [];
        $updated = [];

        foreach ($lines as $i => $line) {
            $updated[$i] = $line;
            foreach ($values as $key => $value) {
                $pattern = \'/^\' . preg_quote($key, \'/\') . \'\s*=/\';
                if (preg_match($pattern, ltrim($line))) {
                    $updated[$i] = $key . \'=\' . $this->formatValue((string) $value);
                    unset($values[$key]);
                    break;
                }
            }
        }

        // Append any keys that weren\'t found
        foreach ($values as $key => $value) {
            $updated[] = $key . \'=\' . $this->formatValue((string) $value);
        }

        $newContent = implode("\n", $updated) . "\n";
        
        $result = file_put_contents($this->envPath, $newContent, LOCK_EX);
        return $result !== false;
    }

    /**
     * Read a value from .env.
     */
    public function read(string $key): ?string
    {
        if (!file_exists($this->envPath)) {
            return null;
        }
        $content = file_get_contents($this->envPath);
        if (preg_match(\'/^\' . preg_quote($key, \'/\') . \'\s*=(.*)$/m\', $content, $matches)) {
            $value = trim($matches[1]);
            // Strip surrounding quotes
            if (strlen($value) >= 2 && $value[0] === \'"\' && $value[-1] === \'"\') {
                $value = stripslashes(substr($value, 1, -1));
            }
            return $value;
        }
        return null;
    }

    public function isWritable(): bool
    {
        if (file_exists($this->envPath)) {
            return is_writable($this->envPath);
        }
        return is_writable(dirname($this->envPath));
    }

    /**
     * Format a value for .env, quoting if necessary.
     */
    private function formatValue(string $value): string
    {
        // If value contains spaces, special chars, or is empty, quote it
        if ($value === \'\' || preg_match(\'/[\s"\\\\#]/\', $value) || str_contains($value, "\'")) {
            // Use double quotes, escape backslashes and double quotes
            $escaped = str_replace([\'\\\\\', \'"\'], [\'\\\\\\\\\', \'\\\\"\'], $value);
            return \'"\' . $escaped . \'"\';
        }
        return $value;
    }
}
');

// 4. DatabaseInstaller.php
file_put_contents($dir . '/app/Install/DatabaseInstaller.php', '<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseInstaller
{
    /**
     * Test a database connection with the given credentials.
     * Does NOT run migrations.
     */
    public function testConnection(
        string $host,
        int $port,
        string $database,
        string $username,
        string $password
    ): array {
        try {
            Config::set(\'database.connections.installer_test\', [
                \'driver\' => \'mysql\',
                \'host\' => $host,
                \'port\' => $port,
                \'database\' => $database,
                \'username\' => $username,
                \'password\' => $password,
                \'charset\' => \'utf8mb4\',
                \'collation\' => \'utf8mb4_unicode_ci\',
                \'prefix\' => \'\',
                \'strict\' => true,
                \'engine\' => null,
                \'options\' => [
                    \PDO::ATTR_TIMEOUT => 5,
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                ],
            ]);

            DB::purge(\'installer_test\');
            $pdo = DB::connection(\'installer_test\')->getPdo();
            
            // Check if we can actually use the database
            $serverVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            return [
                \'success\' => true,
                \'server_version\' => $serverVersion,
            ];
        } catch (\PDOException $e) {
            return [
                \'success\' => false,
                \'error\' => $this->friendlyPdoError($e),
                \'technical\' => [
                    \'exception\' => get_class($e),
                    \'code\' => $e->getCode(),
                    \'message\' => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                \'success\' => false,
                \'error\' => \'Database connection failed. Check your credentials and try again.\',
                \'technical\' => [
                    \'exception\' => get_class($e),
                    \'code\' => $e->getCode(),
                    \'message\' => $e->getMessage(),
                ],
            ];
        } finally {
            try { DB::purge(\'installer_test\'); } catch (\Exception $e) {}
        }
    }

    /**
     * Run Laravel migrations on the configured default connection.
     * Safe: skips already-run migrations, does not drop tables.
     */
    public function runMigrations(): array
    {
        try {
            $output = new \Symfony\Component\Console\Output\BufferedOutput();
            $result = Artisan::call(\'migrate\', [
                \'--force\' => true,
                \'--no-interaction\' => true,
            ]);
            
            return [
                \'success\' => $result === 0,
                \'output\' => Artisan::output(),
            ];
        } catch (\Exception $e) {
            return [
                \'success\' => false,
                \'error\' => \'Migration failed: \' . $e->getMessage(),
                \'technical\' => [
                    \'exception\' => get_class($e),
                    \'message\' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Check migration status.
     */
    public function migrationStatus(): array
    {
        try {
            $pendingCount = 0;
            $ranCount = 0;
            
            // Use artisan migrate:status
            Artisan::call(\'migrate:status\', [\'--no-interaction\' => true]);
            $output = Artisan::output();
            
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (str_contains($line, \'Ran\') || str_contains($line, \'Yes\')) {
                    $ranCount++;
                } elseif (str_contains($line, \'Pending\') || str_contains($line, \'No\')) {
                    $pendingCount++;
                }
            }
            
            return [
                \'ran\' => $ranCount,
                \'pending\' => $pendingCount,
                \'output\' => $output,
            ];
        } catch (\Exception $e) {
            return [\'ran\' => 0, \'pending\' => 0, \'output\' => \'\', \'error\' => $e->getMessage()];
        }
    }

    private function friendlyPdoError(\PDOException $e): string
    {
        $msg = $e->getMessage();
        $code = (int) $e->getCode();
        
        return match(true) {
            str_contains($msg, \'Connection refused\') => \'Database connection refused. Check that MySQL/MariaDB is running and the host/port are correct.\',
            str_contains($msg, \'Connection timed out\') || str_contains($msg, \'timed out\') => \'Database connection timed out. Check that the host is reachable and the port is correct.\',
            str_contains($msg, \'Unknown database\') || $code === 1049 => \'Database does not exist. Create it in phpMyAdmin/cPanel or ask your host.\',
            str_contains($msg, \'Access denied\') || $code === 1045 => \'Access denied. Check your database username and password.\',
            str_contains($msg, \'Unknown MySQL server host\') => \'Cannot resolve the database host. Check the hostname.\',
            str_contains($msg, \'php_network_getaddresses\') => \'Cannot resolve database host. Check the hostname and DNS.\',
            default => \'Database connection failed. \' . $msg,
        };
    }
}
');

// 5. SecurityConfigurator.php
file_put_contents($dir . '/app/Install/SecurityConfigurator.php', '<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Facades\Artisan;

class SecurityConfigurator
{
    private ConfigurationWriter $writer;

    public function __construct(ConfigurationWriter $writer)
    {
        $this->writer = $writer;
    }

    /**
     * Apply security configuration to .env.
     */
    public function apply(array $options = []): void
    {
        $isHttps = $options[\'https\'] ?? false;
        
        $config = [
            \'SESSION_LIFETIME\' => \'1440\',
            \'SESSION_ENCRYPT\' => \'false\',
            \'SESSION_SECURE_COOKIE\' => $isHttps ? \'true\' : \'false\',
            \'BCRYPT_ROUNDS\' => \'12\',
        ];
        
        if ($isHttps) {
            $config[\'APP_URL\'] = \'https://\' . ($options[\'domain\'] ?? \'localhost\');
        }
        
        $this->writer->write($config);
    }

    /**
     * Ensure APP_KEY exists. Generate if missing.
     * Returns true if key already existed, false if generated.
     */
    public function ensureAppKey(): bool
    {
        $existingKey = $this->writer->read(\'APP_KEY\');
        
        if ($existingKey && strlen($existingKey) > 10) {
            return true; // Already set
        }
        
        Artisan::call(\'key:generate\', [\'--force\' => true]);
        return false;
    }
}
');

// 6. InstallationBootstrap.php
file_put_contents($dir . '/app/Install/InstallationBootstrap.php', '<?php

declare(strict_types=1);

namespace App\Install;

class InstallationBootstrap
{
    /**
     * Ensure minimum bootstrap requirements are met for the installer to function.
     * This runs early in the request cycle.
     */
    public static function ensureInstallable(): void
    {
        $envPath = __DIR__ . \'/../../.env\';
        
        // If .env doesn\'t exist, create it from .env.example
        if (!file_exists($envPath)) {
            $examplePath = __DIR__ . \'/../../.env.example\';
            if (file_exists($examplePath)) {
                copy($examplePath, $envPath);
            } else {
                // Create minimal .env
                file_put_contents($envPath, self::minimalEnv());
            }
        }
        
        // If APP_KEY is empty, generate one and write to .env
        $content = file_get_contents($envPath);
        if (preg_match(\'/^APP_KEY=$/m\', $content) || preg_match(\'/^APP_KEY=\s*$/m\', $content) || !str_contains($content, \'APP_KEY=\')) {
            $key = \'base64:\' . base64_encode(random_bytes(32));
            if (str_contains($content, \'APP_KEY=\')) {
                $content = preg_replace(\'/^APP_KEY=.*$/m\', \'APP_KEY=\' . $key, $content);
            } else {
                $content .= "\nAPP_KEY=" . $key . "\n";
            }
            file_put_contents($envPath, $content);
        }
    }
    
    private static function minimalEnv(): string
    {
        $key = \'base64:\' . base64_encode(random_bytes(32));
        return <<<ENV
APP_NAME=OpenMail
APP_ENV=production
APP_KEY={$key}
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=1440

CACHE_STORE=file

QUEUE_CONNECTION=database
ENV;
    }
}
');

echo "Scripts created\n";
