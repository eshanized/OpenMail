<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use Livewire\Component;

class AppearanceTab extends Component
{
    public string $theme = 'system';

    public string $density = 'regular';

    public function mount(): void
    {
        $user = auth()->user();
        $this->theme = $user->setting('theme', 'system');
        $this->density = $user->setting('density', 'regular');
    }

    public function updatedTheme(): void
    {
        $this->validateTheme();
        $this->persist();
        $this->dispatch('theme-changed', theme: $this->theme);
    }

    public function updatedDensity(): void
    {
        $this->validateDensity();
        $this->persist();
        $this->dispatch('density-changed', density: $this->density);
    }

    private function persist(): void
    {
        $userId = auth()->id();
        Setting::setForUser($userId, 'theme', $this->theme, 'appearance');
        Setting::setForUser($userId, 'density', $this->density, 'appearance');

        // Dispatch browser events for localStorage sync and cross-component updates
        $this->dispatch('browser-theme-changed', theme: $this->theme);
        $this->dispatch('browser-density-changed', density: $this->density);
    }

    private function validateTheme(): void
    {
        if (! in_array($this->theme, ['light', 'dark', 'system'])) {
            $this->theme = 'system';
        }
    }

    private function validateDensity(): void
    {
        if (! in_array($this->density, ['compact', 'regular', 'comfortable'])) {
            $this->density = 'regular';
        }
    }

    public function render()
    {
        return view('livewire.settings.appearance-tab');
    }
}
