<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DatabaseInstaller
{
    // ──────────────────────────────────────────────────────────────────────────
    // Connection testing
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Test a database connection with the given credentials.
     * Does NOT run migrations or modify any data.
     *
     * @return array{success: bool, server_version?: string, error?: string, technical?: array}
     */
    public function testConnection(
        string $host,
        int    $port,
        string $database,
        string $username,
        string $password
    ): array {
        $connectionName = 'installer_probe_' . getmypid();

        try {
            Config::set("database.connections.{$connectionName}", [
                'driver'      => 'mysql',
                'host'        => $host,
                'port'        => $port,
                'database'    => $database,
                'username'    => $username,
                'password'    => $password,
                'charset'     => 'utf8mb4',
                'collation'   => 'utf8mb4_unicode_ci',
                'prefix'      => '',
                'strict'      => true,
                'engine'      => null,
                'options'     => [
                    \PDO::ATTR_TIMEOUT      => 5,
                    \PDO::ATTR_ERRMODE      => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_CONNECT_TIMEOUT => 5,
                ],
            ]);

            $pdo           = DB::connection($connectionName)->getPdo();
            $serverVersion = $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);

            return [
                'success'        => true,
                'server_version' => $serverVersion,
            ];
        } catch (\PDOException $e) {
            return [
                'success'   => false,
                'error'     => $this->friendlyPdoError($e),
                'technical' => [
                    'exception' => get_class($e),
                    'code'      => (int) $e->getCode(),
                    'message'   => $e->getMessage(),
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success'   => false,
                'error'     => 'Database connection failed. Please check your credentials and try again.',
                'technical' => [
                    'exception' => get_class($e),
                    'code'      => $e->getCode(),
                    'message'   => $e->getMessage(),
                ],
            ];
        } finally {
            try {
                DB::purge($connectionName);
            } catch (\Exception) {
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Migrations
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Run Laravel migrations against the currently configured default connection.
     *
     * Safe: idempotent (already-run migrations are skipped).
     * Does NOT drop or truncate any tables.
     *
     * @return array{success: bool, output: string, error?: string}
     */
    public function runMigrations(): array
    {
        try {
            $exitCode = Artisan::call('migrate', [
                '--force'          => true,
                '--no-interaction' => true,
            ]);

            $output = Artisan::output();

            return [
                'success' => $exitCode === 0,
                'output'  => $output,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output'  => '',
                'error'   => 'Migration failed: ' . $e->getMessage(),
                'technical' => [
                    'exception' => get_class($e),
                    'message'   => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Check whether migrations have been run by attempting to read the
     * migrations table (non-destructive).
     */
    public function areMigrationsRan(): bool
    {
        try {
            // If migrate:status returns without error and mentions "Ran", they've run
            Artisan::call('migrate:status', ['--no-interaction' => true]);
            $output = Artisan::output();
            return str_contains($output, 'Ran') || str_contains($output, 'Yes');
        } catch (\Exception) {
            return false;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Error translation
    // ──────────────────────────────────────────────────────────────────────────

    private function friendlyPdoError(\PDOException $e): string
    {
        $msg  = $e->getMessage();
        $code = (int) $e->getCode();

        return match (true) {
            str_contains($msg, 'Connection refused')
                => 'Database connection refused. Check that MySQL/MariaDB is running and the host/port are correct.',

            str_contains($msg, 'Connection timed out') || str_contains($msg, 'timed out')
                => 'Database connection timed out. Check that the host is reachable and the port is open.',

            ($code === 1049 || str_contains($msg, 'Unknown database'))
                => 'Database "' . htmlspecialchars($msg, ENT_QUOTES) . '" does not exist. Create it in phpMyAdmin/cPanel first.',

            ($code === 1045 || str_contains($msg, 'Access denied'))
                => 'Access denied. Check your database username and password.',

            str_contains($msg, 'Unknown MySQL server host') || str_contains($msg, 'php_network_getaddresses')
                => 'Cannot resolve the database hostname. Check the host field.',

            str_contains($msg, 'No such file or directory') && str_contains($msg, 'mysql')
                => 'MySQL socket not found. Try using "127.0.0.1" as the host instead of "localhost".',

            default => 'Database connection failed. ' . $msg,
        };
    }
}
