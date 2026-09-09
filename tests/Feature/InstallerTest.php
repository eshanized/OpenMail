<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\InstallationLock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class InstallerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure not installed for tests
        $lockFile = storage_path('installed');
        if (File::exists($lockFile)) {
            File::delete($lockFile);
        }
    }

    protected function tearDown(): void
    {
        $lockFile = storage_path('installed');
        if (File::exists($lockFile)) {
            File::delete($lockFile);
        }
        parent::tearDown();
    }

    public function test_installer_is_accessible_when_not_installed(): void
    {
        $response = $this->get('/install');
        
        $response->assertStatus(200);
        $response->assertViewIs('setup.page');
    }

    public function test_installer_redirects_to_404_when_installed(): void
    {
        $lock = app(InstallationLock::class);
        $lock->markInstalled();
        
        $response = $this->get('/install');
        
        $response->assertStatus(404);
    }
}
