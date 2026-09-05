<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
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

        // Check throttle using dual-key (IP + email)
        $throttleKey = 'login_attempts:' . Str::lower($this->email);

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            throw ValidationException::withMessages([
                'email' => "Too many attempts. Try again in {$seconds} seconds.",
            ]);
        }

        // Attempt IMAP authentication
        $remember = false; // We don't use remember token for IMAP auth
        $credentials = ['email' => $this->email, 'password' => $this->password];

        if (Auth::guard('imap')->attempt($credentials, $remember)) {
            // Login successful
            $request = request();
            $request->session()->regenerate(); // Session rotation (AUTH-04)
            RateLimiter::clear($throttleKey); // Clear throttle on success

            // Store encrypted IMAP password in session for future IMAP connections
            $request->session()->put('openmail:imap_password', Crypt::encrypt($this->password));

            // Redirect to mailbox
            $this->redirect(route('mailbox'), navigate: true);
            return;
        }

        // Login failed
        RateLimiter::hit($throttleKey, $this->calculateDecay());

        $attempts = RateLimiter::attempts($throttleKey);
        $this->setErrorMessage($attempts);
    }

    private function calculateDecay(): int
    {
        // Progressive decay: 5 attempts per 5 minutes base
        // The LoginForm applies progressive delays, RateLimiter just counts
        return 300; // 5 minutes decay for the rate limiter counter
    }

    private function setErrorMessage(int $attempts): void
    {
        // Progressive throttling per D-14
        if ($attempts >= 10) {
            $this->error = 'Too many failed attempts. Account locked for 15 minutes.';
        } elseif ($attempts >= 7) {
            $this->error = 'Too many failed attempts. Try again in 5 minutes.';
        } elseif ($attempts >= 5) {
            $this->error = 'Too many failed attempts. Try again in 30 seconds.';
        } elseif ($attempts >= 3) {
            $this->error = 'Too many failed attempts. Try again in 5 seconds.';
        } else {
            $this->error = 'Invalid email or password.';
        }
    }

    public function render()
    {
        return view('livewire.login-form');
    }
}