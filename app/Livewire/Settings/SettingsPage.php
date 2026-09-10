<?php

namespace App\Livewire\Settings;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsPage extends Component
{
    #[Url(as: 'tab')]
    public string $activeTab = 'profile';

    public array $tabs = [
        'profile' => 'Profile',
        'mail' => 'Mail',
        'appearance' => 'Appearance',
        'security' => 'Security',
        'signatures' => 'Signatures',
    ];

    public function mount(): void
    {
        $tab = request()->query('tab');
        if ($tab && array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.settings.settings-page', [
            'activeTab' => $this->activeTab,
        ]);
    }
}
