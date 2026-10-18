<?php

declare(strict_types=1);

namespace App\Install;

use RuntimeException;

class InstallationLock
{
    private string $lockFile;

    private string $progressFile;

    public function __construct()
    {
        $this->lockFile = storage_path('installed');
        $this->progressFile = storage_path('framework/install_progress.json');
    }

    // ──────────────────────────────────────────────────────────────────────────
    // State
    // ──────────────────────────────────────────────────────────────────────────

    public function state(): InstallationState
    {
        if ($this->isInstalled()) {
            return InstallationState::Installed;
        }

        $progress = $this->readProgress();
        if (isset($progress['state'])) {
            $state = InstallationState::tryFrom($progress['state']);
            if ($state !== null) {
                return $state;
            }
        }

        return InstallationState::NotInstalled;
    }

    public function isInstalled(): bool
    {
        return is_file($this->lockFile);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Transitions
    // ──────────────────────────────────────────────────────────────────────────

    public function markInstalling(): void
    {
        $this->writeProgress([
            'state' => InstallationState::Installing->value,
            'started_at' => now()->toIso8601String(),
            'pid' => getmypid(),
        ]);
    }

    /**
     * Persist the installation lock and fail loudly if the filesystem refuses it.
     *
     * A successful return from this method is the installer's durable signal that
     * setup is complete. Never clear progress or report success unless the lock
     * can be read back from the exact path checked by the install middleware.
     */
    public function markInstalled(array $metadata = []): void
    {
        $data = array_merge([
            'installed_at' => now()->toIso8601String(),
            'version' => config('app.version', '2.0.0'),
        ], $metadata);

        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if (! is_string($payload)) {
            throw new RuntimeException('Unable to encode the installation lock metadata.');
        }

        $lockExistedBefore = is_file($this->lockFile);
        $directory = dirname($this->lockFile);
        if (! is_dir($directory) && ! @mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('Unable to create the installation state directory. Check storage permissions.');
        }

        // Strategy 1: write to a temporary file, then atomically rename it.
        $tmpFile = $this->lockFile.'.tmp.'.getmypid().'.'.bin2hex(random_bytes(6));
        $written = @file_put_contents($tmpFile, $payload, LOCK_EX);

        if ($written === strlen($payload) && @rename($tmpFile, $this->lockFile)) {
            if ($this->lockContains($payload)) {
                $this->removeProgress();

                return;
            }
        }

        // Strategy 2: fall back to an exclusive write on filesystems where rename fails.
        $fp = @fopen($this->lockFile, 'c+');
        if ($fp !== false) {
            $persisted = false;

            if (flock($fp, LOCK_EX)) {
                $ready = ftruncate($fp, 0) && rewind($fp);
                $bytesWritten = $ready ? fwrite($fp, $payload) : false;
                $persisted = $bytesWritten === strlen($payload) && fflush($fp);
                flock($fp, LOCK_UN);
            }

            fclose($fp);

            if ($persisted && $this->lockContains($payload)) {
                if (file_exists($tmpFile)) {
                    @unlink($tmpFile);
                }

                $this->removeProgress();

                return;
            }
        }

        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }

        // Do not leave a partial lock behind; isInstalled() must remain false.
        if (! $lockExistedBefore && is_file($this->lockFile)) {
            @unlink($this->lockFile);
        }

        // Preserve progress state on failure; the UI/CLI must not report success.
        throw new RuntimeException(
            'Unable to persist the installation lock file. Ensure the storage directory is writable by PHP, then retry setup.'
        );
    }

    private function lockContains(string $expectedPayload): bool
    {
        if (! is_file($this->lockFile) || ! is_readable($this->lockFile)) {
            return false;
        }

        $contents = @file_get_contents($this->lockFile);

        return is_string($contents) && $contents === $expectedPayload;
    }

    public function markFailed(string $reason = ''): void
    {
        $progress = $this->readProgress();
        $progress['state'] = InstallationState::Failed->value;
        $progress['failed_at'] = now()->toIso8601String();
        $progress['reason'] = $reason;
        $this->writeProgress($progress);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Metadata
    // ──────────────────────────────────────────────────────────────────────────

    public function getMetadata(): array
    {
        if (! $this->isInstalled()) {
            return [];
        }

        $raw = @file_get_contents($this->lockFile);
        if ($raw === false || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        // Handle legacy plain-text lock files (just a timestamp string)
        if (! is_array($decoded)) {
            return ['installed_at' => trim($raw)];
        }

        return $decoded;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Progress file helpers
    // ──────────────────────────────────────────────────────────────────────────

    public function readProgress(): array
    {
        if (! file_exists($this->progressFile)) {
            return [];
        }

        $raw = @file_get_contents($this->progressFile);
        if ($raw === false) {
            return [];
        }

        return json_decode($raw, true) ?? [];
    }

    private function writeProgress(array $data): void
    {
        $dir = dirname($this->progressFile);
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(
            $this->progressFile,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function removeProgress(): void
    {
        if (file_exists($this->progressFile)) {
            @unlink($this->progressFile);
        }
    }
}
