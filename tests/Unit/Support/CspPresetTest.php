<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\Csp\LaravelViteNonceGenerator;
use App\Support\Csp\OpenMailPreset;
use App\Support\Csp\OpenMailReportOnlyPreset;
use Illuminate\Support\Facades\Vite;
use Mockery;
use Spatie\Csp\Policy;
use Tests\TestCase;

class CspPresetTest extends TestCase
{
    public function test_enforcing_preset_adds_expected_directives_and_nonces(): void
    {
        $policy = Mockery::mock(Policy::class);
        $policy->shouldReceive('add')->times(16)->andReturnSelf();
        $policy->shouldReceive('addNonce')->times(2)->andReturnSelf();

        (new OpenMailPreset)->configure($policy);
    }

    public function test_report_only_preset_uses_nonces_without_unsafe_inline_directives(): void
    {
        $policy = Mockery::mock(Policy::class);
        $policy->shouldReceive('add')->times(14)->andReturnSelf();
        $policy->shouldReceive('addNonce')->times(2)->andReturnSelf();

        (new OpenMailReportOnlyPreset)->configure($policy);
    }

    public function test_vite_nonce_generator_returns_the_nonce_from_vite(): void
    {
        Vite::shouldReceive('useCspNonce')->once()->andReturn('test-csp-nonce');

        $this->assertSame('test-csp-nonce', (new LaravelViteNonceGenerator)->generate());
    }
}
