<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.mailbox')]
class SettingsPage extends Component
{
    public string $activeTab = 'profile';

    public array $tabs = [
        'profile' => 'Profile',
        'mail' => 'Mail',
        'appearance' => 'Appearance',
        'security' => 'Security',
        'signatures' => 'Signatures',
    ];

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
