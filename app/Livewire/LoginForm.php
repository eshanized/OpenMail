<?php

namespace App\Livewire;

use Livewire\Component;

class LoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public string $error = '';

    public function login(): void
    {
        $this->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // IMAP authentication will be implemented in Plan 03
        // For now, just show a placeholder message
        $this->error = 'IMAP authentication will be implemented in Phase 1, Plan 3.';
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}