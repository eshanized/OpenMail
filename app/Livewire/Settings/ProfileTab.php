<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Validate;

class ProfileTab extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255|unique:users,email,' . '::auth()->id()')]
    public string $email = '';

    public function mount(): void
    {
        $this->name = auth()->user()->name ?? '';
        $this->email = auth()->user()->email ?? '';
    }

    public function save(): void
    {
        $this->validate();

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
