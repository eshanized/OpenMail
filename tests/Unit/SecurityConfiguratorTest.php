<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Install\ConfigurationWriter;
use App\Install\SecurityConfigurator;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class SecurityConfiguratorTest extends TestCase
{
    public function test_existing_app_key_is_preserved_without_running_key_generation(): void
    {
        $writer = Mockery::mock(ConfigurationWriter::class);
        $writer->shouldReceive('read')->once()->with('APP_KEY')->andReturn('base64:existing-long-key');
        Artisan::shouldReceive('call')->never();

        $this->assertTrue((new SecurityConfigurator($writer))->ensureAppKey());
    }

    public function test_missing_app_key_runs_key_generation_and_reports_that_it_was_generated(): void
    {
        $writer = Mockery::mock(ConfigurationWriter::class);
        $writer->shouldReceive('read')->once()->with('APP_KEY')->andReturn('');
        Artisan::shouldReceive('call')->once()->with('key:generate', ['--force' => true])->andReturn(0);

        $this->assertFalse((new SecurityConfigurator($writer))->ensureAppKey());
    }

    public function test_apply_sets_hardened_session_and_production_defaults_for_http(): void
    {
        $writer = Mockery::mock(ConfigurationWriter::class);
        $writer->shouldReceive('write')->once()->with(Mockery::on(function (array $values): bool {
            return $values['SESSION_LIFETIME'] === '1440'
                && $values['SESSION_ENCRYPT'] === 'true'
                && $values['SESSION_SECURE_COOKIE'] === 'false'
                && $values['BCRYPT_ROUNDS'] === '12'
                && $values['APP_DEBUG'] === 'false'
                && $values['APP_ENV'] === 'production'
                && ! array_key_exists('APP_URL', $values);
        }))->andReturn(true);

        (new SecurityConfigurator($writer))->apply();
    }

    public function test_apply_sets_https_url_and_secure_cookie_when_https_is_enabled(): void
    {
        $writer = Mockery::mock(ConfigurationWriter::class);
        $writer->shouldReceive('write')->once()->with(Mockery::on(
            fn (array $values): bool => $values['SESSION_SECURE_COOKIE'] === 'true'
                && $values['APP_URL'] === 'https://mail.example.test'
        ))->andReturn(true);

        (new SecurityConfigurator($writer))->apply([
            'https' => true,
            'domain' => 'mail.example.test',
        ]);
    }

    public function test_apply_does_not_write_an_https_url_without_a_domain(): void
    {
        $writer = Mockery::mock(ConfigurationWriter::class);
        $writer->shouldReceive('write')->once()->with(Mockery::on(
            fn (array $values): bool => $values['SESSION_SECURE_COOKIE'] === 'true'
                && ! array_key_exists('APP_URL', $values)
        ))->andReturn(true);

        (new SecurityConfigurator($writer))->apply(['https' => true]);
    }
}
