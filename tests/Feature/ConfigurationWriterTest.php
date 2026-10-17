<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\ConfigurationWriter;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ConfigurationWriterTest extends TestCase
{
    private string $envPath;

    private bool $envOriginallyExisted;

    private ?string $originalEnvContents;

    protected function setUp(): void
    {
        parent::setUp();

        $this->envPath = base_path('.env');
        $this->envOriginallyExisted = File::exists($this->envPath);
        $this->originalEnvContents = $this->envOriginallyExisted ? File::get($this->envPath) : null;
        File::put($this->envPath, "# preserved comment\nAPP_NAME=OldName\nEMPTY=old\n");
    }

    protected function tearDown(): void
    {
        if ($this->envOriginallyExisted) {
            File::put($this->envPath, $this->originalEnvContents ?? '');
        } else {
            File::delete($this->envPath);
        }

        parent::tearDown();
    }

    public function test_write_updates_existing_keys_and_appends_missing_keys_atomically(): void
    {
        $writer = new ConfigurationWriter;

        $this->assertTrue($writer->write([
            'APP_NAME' => 'Open Mail',
            'EMPTY' => '',
            'NEW_VALUE' => 'value#with comment marker',
            'FEATURE_ENABLED' => true,
            'FEATURE_DISABLED' => false,
        ]));

        $contents = File::get($this->envPath);
        $this->assertStringContainsString("# preserved comment\n", $contents);
        $this->assertStringContainsString('APP_NAME="Open Mail"', $contents);
        $this->assertStringContainsString('EMPTY=""', $contents);
        $this->assertStringContainsString('NEW_VALUE="value#with comment marker"', $contents);
        $this->assertStringContainsString('FEATURE_ENABLED=true', $contents);
        $this->assertStringContainsString('FEATURE_DISABLED=false', $contents);
        $this->assertSame('Open Mail', $writer->read('APP_NAME'));
        $this->assertSame('value#with comment marker', $writer->read('NEW_VALUE'));
    }

    public function test_read_returns_null_for_missing_keys_and_unwraps_single_quoted_values(): void
    {
        File::put($this->envPath, "QUOTED='single quoted value'\nPLAIN=value\n");

        $writer = new ConfigurationWriter;
        $this->assertSame('single quoted value', $writer->read('QUOTED'));
        $this->assertSame('value', $writer->read('PLAIN'));
        $this->assertNull($writer->read('ABSENT_KEY'));
    }

    public function test_writable_check_falls_back_to_parent_directory_when_env_does_not_exist(): void
    {
        File::delete($this->envPath);

        $writer = new ConfigurationWriter;
        $this->assertNull($writer->read('APP_NAME'));
        $this->assertTrue($writer->isWritable());
    }
}
