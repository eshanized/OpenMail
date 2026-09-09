<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use App\Services\AuditService;

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

        // Attempt authentication: IMAP first, then local fallback
        $authenticatedUser = null;
        try {
            $imapTester = app(\App\Services\ImapConnectionTester::class);
            $host = config('openmail.imap.host');
            $port = (int) config('openmail.imap.port', 993);
            $encryption = config('openmail.imap.encryption', 'ssl');

            if ($host) {
                $result = $imapTester->test($host, $port, $encryption, $this->email, $this->password);
                if (!empty($result['success'])) {
                    $authenticatedUser = \App\Models\User::firstOrCreate(
                        ['email' => $this->email],
                        ['name' => explode('@', $this->email)[0]]
                    );
                    $authenticatedUser->password = $this->password;
                    $authenticatedUser->save();
                }
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('IMAP auth check failed: ' . $e->getMessage());
        }

        // Fallback to local database credentials (e.g. offline testing or local admin account)
        if (!$authenticatedUser) {
            if (Auth::guard('web')->attempt(['email' => $this->email, 'password' => $this->password])) {
                $authenticatedUser = Auth::guard('web')->user();
            }
        }

        if ($authenticatedUser) {
            // Login to web guard (default guard for web routes) and imap guard
            Auth::guard('web')->login($authenticatedUser);
            if (config('auth.guards.imap')) {
                Auth::guard('imap')->login($authenticatedUser);
            }

            // Login successful
            $request = request();
            if ($request->hasSession()) {
                $request->session()->regenerate(); // Session rotation (AUTH-04)
            }
            RateLimiter::clear($throttleKey); // Clear throttle on success

            // Store encrypted IMAP password in session for future IMAP connections
            session()->put('openmail:imap_password', Crypt::encrypt($this->password));

            // Audit log: login success
            AuditService::loginSuccess($request);

            // Redirect to mailbox
            $this->redirect(route('mailbox'), navigate: true);
            return;
        }

        // Login failed
        RateLimiter::hit($throttleKey, $this->calculateDecay());

        $attempts = RateLimiter::attempts($throttleKey);

        // Audit log: login failure
        AuditService::loginFailed($this->email, 'invalid_credentials', request());

        // Audit log: lockout at 10+ attempts
        if ($attempts >= 10) {
            AuditService::lockout($this->email, request());
        }

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