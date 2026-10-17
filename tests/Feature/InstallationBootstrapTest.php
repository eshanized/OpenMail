<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\InstallationBootstrap;
use PHPUnit\Framework\TestCase;

class InstallationBootstrapTest extends TestCase
{
    /** @var list<string> */
    private array $tempDirectories = [];

    protected function tearDown(): void
    {
        foreach ($this->tempDirectories as $directory) {
            $this->removeDirectory($directory);
        }

        parent::tearDown();
    }

    public function test_creates_env_from_example_and_generates_missing_key(): void
    {
        $base = $this->makeDirectory();
        file_put_contents($base.'/.env.example', "APP_NAME=OpenMail\nAPP_KEY=\nAPP_ENV=production\n");

        InstallationBootstrap::ensureBootable($base);

        $content = file_get_contents($base.'/.env');
        $this->assertIsString($content);
        $this->assertStringContainsString('APP_NAME=OpenMail', $content);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.{40,}$/m', $content);
        $this->assertStringContainsString('APP_ENV=production', $content);
    }

    public function test_generates_minimal_environment_when_no_example_exists(): void
    {
        $base = $this->makeDirectory();

        InstallationBootstrap::ensureBootable($base);

        $content = file_get_contents($base.'/.env');
        $this->assertIsString($content);
        $this->assertStringContainsString('APP_NAME=OpenMail', $content);
        $this->assertStringContainsString('DB_CONNECTION=sqlite', $content);
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.{40,}$/m', $content);
    }

    public function test_preserves_an_existing_sufficiently_long_app_key(): void
    {
        $base = $this->makeDirectory();
        $key = 'base64:existing-test-key-that-is-long-enough';
        file_put_contents($base.'/.env', "APP_NAME=OpenMail\nAPP_KEY={$key}\n");

        InstallationBootstrap::ensureBootable($base);

        $content = file_get_contents($base.'/.env');
        $this->assertStringContainsString("APP_KEY={$key}", $content);
        $this->assertSame(1, preg_match_all('/^APP_KEY=/m', $content));
    }

    public function test_replaces_a_short_existing_key_instead_of_adding_a_duplicate(): void
    {
        $base = $this->makeDirectory();
        file_put_contents($base.'/.env', "APP_NAME=OpenMail\nAPP_KEY=short\n");

        InstallationBootstrap::ensureBootable($base);

        $content = file_get_contents($base.'/.env');
        $this->assertMatchesRegularExpression('/^APP_KEY=base64:.{40,}$/m', $content);
        $this->assertSame(1, preg_match_all('/^APP_KEY=/m', $content));
    }

    private function makeDirectory(): string
    {
        $directory = sys_get_temp_dir().'/openmail-bootstrap-'.bin2hex(random_bytes(8));
        mkdir($directory, 0700, true);
        $this->tempDirectories[] = $directory;

        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $entries = scandir($directory);
        foreach ($entries ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }
}
