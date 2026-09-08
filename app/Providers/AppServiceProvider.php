<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register IMAP auth guard
        Auth::viaRequest('imap', function ($request) {
            $email = $request->input('email');
            $password = $request->input('password');

            if (!$email || !$password) {
                return null;
            }

            // Find or create user by email
            $user = \App\Models\User::firstOrCreate(
                ['email' => $email],
                ['name' => explode('@', $email)[0]]
            );

            // Verify credentials against IMAP
            try {
                $config = array(
                    'host' => config('openmail.imap.host'),
                    'port' => config('openmail.imap.port'),
                    'encryption' => config('openmail.imap.encryption'),
                    'username' => $email,
                    'password' => $password,
                    'protocol' => 'imap',
                    'timeout' => 10,
                    'validate_cert' => true,
                );

                $client = new \Webklex\PHPIMAP\Client($config);
                $client->connect();

                // Connection successful - log security event (without password)
                Log::channel('security')->info('IMAP authentication successful', array(
                    'email' => $email,
                    'ip' => $request->ip(),
                ));

                $client->disconnect();

                return $user;
            } catch (\Exception $e) {
                // Log failed attempt (without password)
                Log::channel('security')->warning('IMAP authentication failed', array(
                    'email' => $email,
                    'ip' => $request->ip(),
                    'error' => $e->getMessage(),
                ));

                return null;
            }
        });

        // Configure rate limiter for login
        RateLimiter::for('login', function ($request) {
            $email = Str::lower($request->input('email', ''));

            return array(
                // Per-IP: 10 attempts per minute
                Limit::perMinute(10)->by($request->ip()),
                // Per-email: 5 attempts per 5 minutes
                Limit::perMinutes(5, 5)->by($email),
            );
        });

        // API rate limiters per SEC-04

        // Compose endpoint: 30 requests per minute per user/IP
        RateLimiter::for('api.compose', function ($request) {
            return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
        });

        // Search endpoint: 60 requests per minute per user/IP
        RateLimiter::for('api.search', function ($request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        // Settings endpoint: 120 requests per minute per user/IP
        RateLimiter::for('api.settings', function ($request) {
            return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
        });

        // Authenticated API: stricter per-user limits (100/min + 1000/hr)
        RateLimiter::for('api.authenticated', function ($request) {
            return array(
                Limit::perMinute(100)->by('minute:' . $request->user()->id),
                Limit::perHour(1000)->by('hour:' . $request->user()->id),
            );
        });
    }
}