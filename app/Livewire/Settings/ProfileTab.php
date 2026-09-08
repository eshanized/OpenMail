<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class ProfileTab extends Component
{
    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name ?? '';
        $this->email = auth()->user()->email ?? '';
    }

    public function save(): void
    {
        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $this->dispatch('toast', message: 'Profile updated', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.profile-tab');
    }
}
