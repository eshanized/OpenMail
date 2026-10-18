<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\InstallationLock;
use App\Install\InstallationState;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

class InstallationLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        File::delete(storage_path('installed'));
        File::delete(storage_path('framework/install_progress.json'));
    }

    protected function tearDown(): void
    {
        File::delete(storage_path('installed'));
        File::delete(storage_path('framework/install_progress.json'));
        parent::tearDown();
    }

    public function test_missing_lock_and_progress_means_not_installed(): void
    {
        $lock = new InstallationLock;

        $this->assertFalse($lock->isInstalled());
        $this->assertSame(InstallationState::NotInstalled, $lock->state());
        $this->assertSame([], $lock->getMetadata());
        $this->assertSame([], $lock->readProgress());
    }

    public function test_installing_and_failed_states_are_persisted_as_progress(): void
    {
        $lock = new InstallationLock;
        $lock->markInstalling();

        $this->assertSame(InstallationState::Installing, $lock->state());
        $this->assertArrayHasKey('started_at', $lock->readProgress());
        $this->assertArrayHasKey('pid', $lock->readProgress());

        $lock->markFailed('database rejected credentials');

        $this->assertSame(InstallationState::Failed, $lock->state());
        $this->assertSame('database rejected credentials', $lock->readProgress()['reason']);
        $this->assertArrayHasKey('failed_at', $lock->readProgress());
    }

    public function test_installed_lock_contains_metadata_and_cleans_up_progress(): void
    {
        $lock = new InstallationLock;
        $lock->markInstalling();
        $lock->markInstalled(['version' => '2.0.0', 'org' => 'Example Org']);

        $this->assertTrue($lock->isInstalled());
        $this->assertSame(InstallationState::Installed, $lock->state());
        $this->assertSame('2.0.0', $lock->getMetadata()['version']);
        $this->assertSame('Example Org', $lock->getMetadata()['org']);
        $this->assertArrayHasKey('installed_at', $lock->getMetadata());
        $this->assertSame([], $lock->readProgress());
        $this->assertFileDoesNotExist(storage_path('framework/install_progress.json'));
    }

    public function test_mark_installed_throws_when_the_lock_file_cannot_be_persisted(): void
    {
        $temporaryRoot = sys_get_temp_dir().'/openmail-lock-test-'.bin2hex(random_bytes(8));
        mkdir($temporaryRoot, 0700, true);

        // A regular file cannot serve as the storage directory, so both the
        // atomic write and the fallback write must fail without changing app storage.
        $invalidStoragePath = $temporaryRoot.'/storage-is-a-file';
        file_put_contents($invalidStoragePath, 'not a directory');
        $originalStoragePath = $this->app->storagePath();

        try {
            $this->app->useStoragePath($invalidStoragePath);
            $lock = new InstallationLock;

            try {
                $lock->markInstalled();
                $this->fail('Installation must not report success when the lock cannot be written.');
            } catch (RuntimeException $exception) {
                $this->assertStringContainsString('directory', strtolower($exception->getMessage()));
            }

            $this->assertFalse($lock->isInstalled());
        } finally {
            $this->app->useStoragePath($originalStoragePath);
            @unlink($invalidStoragePath);
            @rmdir($temporaryRoot);
        }
    }

    public function test_legacy_text_lock_file_is_supported(): void
    {
        File::put(storage_path('installed'), '2026-01-02T03:04:05Z');

        $this->assertSame(
            ['installed_at' => '2026-01-02T03:04:05Z'],
            (new InstallationLock)->getMetadata()
        );
    }

    public function test_invalid_progress_json_falls_back_to_not_installed(): void
    {
        File::ensureDirectoryExists(storage_path('framework'));
        File::put(storage_path('framework/install_progress.json'), '{invalid-json');

        $this->assertSame([], (new InstallationLock)->readProgress());
        $this->assertSame(InstallationState::NotInstalled, (new InstallationLock)->state());
    }

    public function test_unknown_progress_state_falls_back_to_not_installed(): void
    {
        File::ensureDirectoryExists(storage_path('framework'));
        File::put(storage_path('framework/install_progress.json'), json_encode(['state' => 'unknown-state']));

        $this->assertSame(InstallationState::NotInstalled, (new InstallationLock)->state());
    }
}
