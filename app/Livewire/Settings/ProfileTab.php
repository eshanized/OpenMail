<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class ProfileTab extends Component
{
    public string $name = '';

    public string $email = '';

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.auth()->id(),
        ];
    }

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

        $this->dispatch('toast', message: 'Profile updated successfully.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.profile-tab');
    }
}
