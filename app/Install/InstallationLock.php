<?php

declare(strict_types=1);

namespace App\Install;

class InstallationLock
{
    private string $lockFile;
    private string $progressFile;

    public function __construct()
    {
        $this->lockFile     = storage_path('installed');
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
        return file_exists($this->lockFile);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Transitions
    // ──────────────────────────────────────────────────────────────────────────

    public function markInstalling(): void
    {
        $this->writeProgress([
            'state'      => InstallationState::Installing->value,
            'started_at' => now()->toIso8601String(),
            'pid'        => getmypid(),
        ]);
    }

    /**
     * Atomically write the installation lock file.
     *
     * Uses rename() for atomicity where available (most POSIX filesystems).
     * Falls back to exclusive flock on the same file as a safety net.
     */
    public function markInstalled(array $metadata = []): void
    {
        $data = array_merge([
            'installed_at' => now()->toIso8601String(),
            'version'      => config('app.version', '1.0.0'),
        ], $metadata);

        $payload = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Strategy 1: write to tmp then rename (atomic on POSIX)
        $tmpFile = $this->lockFile . '.tmp.' . getmypid() . '.' . microtime(true);

        if (@file_put_contents($tmpFile, $payload) !== false && @rename($tmpFile, $this->lockFile)) {
            // Success – clean up progress file
            $this->removeProgress();
            return;
        }

        // Strategy 2: exclusive flock
        $fp = @fopen($this->lockFile, 'c+');
        if ($fp !== false) {
            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, $payload);
                fflush($fp);
                flock($fp, LOCK_UN);
            }
            fclose($fp);
        }

        // Clean up
        if (file_exists($tmpFile)) {
            @unlink($tmpFile);
        }
        $this->removeProgress();
    }

    public function markFailed(string $reason = ''): void
    {
        $progress           = $this->readProgress();
        $progress['state']  = InstallationState::Failed->value;
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
