<?php

namespace Tests\Feature\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    #[Test]
    public function test_theme_radios_render_and_persist(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('settings.appearance-tab');

        // Theme radios render with wire:model.live="theme"
        $html = $component->html();
        $this->assertStringContainsString('wire:model.live="theme"', $html);
        $this->assertStringContainsString('value="light"', $html);
        $this->assertStringContainsString('value="dark"', $html);
        $this->assertStringContainsString('value="system"', $html);

        // Updating theme to 'dark' persists via Setting row and dispatches theme-changed
        $component
            ->set('theme', 'dark')
            ->assertDispatched('theme-changed', theme: 'dark')
            ->assertDispatched('browser-theme-changed', theme: 'dark');

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'theme',
            'value' => '"dark"',
            'group' => 'appearance',
        ]);
    }

    #[Test]
    public function test_density_radios_render_and_persist(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('settings.appearance-tab');

        // Density radios render with wire:model.live="density"
        $html = $component->html();
        $this->assertStringContainsString('wire:model.live="density"', $html);
        $this->assertStringContainsString('value="compact"', $html);
        $this->assertStringContainsString('value="regular"', $html);
        $this->assertStringContainsString('value="comfortable"', $html);

        // Updating density to 'compact' persists via Setting row and dispatches density-changed
        $component
            ->set('density', 'compact')
            ->assertDispatched('density-changed', density: 'compact')
            ->assertDispatched('browser-density-changed', density: 'compact');

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'density',
            'value' => '"compact"',
            'group' => 'appearance',
        ]);
    }

    #[Test]
    public function test_invalid_theme_snaps_back_to_system(): void
    {
        $component = Livewire::actingAs($this->user)
            ->test('settings.appearance-tab');

        // Invalid theme value snaps back to 'system' via validateTheme guard
        $component
            ->set('theme', 'pink')
            ->assertSet('theme', 'system');

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'theme',
            'value' => '"system"',
            'group' => 'appearance',
        ]);
    }
}
