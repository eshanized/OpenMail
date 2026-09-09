<?php

declare(strict_types=1);

namespace App\Install;

use Illuminate\Support\Facades\Artisan;

class SecurityConfigurator
{
    public function __construct(private readonly ConfigurationWriter $writer) {}

    // ──────────────────────────────────────────────────────────────────────────
    // APP_KEY management
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Ensure an APP_KEY exists.
     *
     * @return bool true  if a key already existed (no change made)
     *              false if a new key was generated and written
     */
    public function ensureAppKey(): bool
    {
        $existing = $this->writer->read('APP_KEY');

        if ($existing !== null && strlen($existing) > 10) {
            return true; // Already present, do NOT overwrite
        }

        // Generate and write a new key
        Artisan::call('key:generate', ['--force' => true]);
        return false;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Security defaults
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Apply secure defaults to .env.
     *
     * @param array{https?: bool, domain?: string} $options
     */
    public function apply(array $options = []): void
    {
        $isHttps = (bool) ($options['https'] ?? false);
        $domain  = $options['domain'] ?? '';

        $config = [
            'SESSION_LIFETIME'     => '1440',
            'SESSION_ENCRYPT'      => 'false',
            'SESSION_SECURE_COOKIE' => $isHttps ? 'true' : 'false',
            'BCRYPT_ROUNDS'        => '12',
            'APP_DEBUG'            => 'false',
            'APP_ENV'              => 'production',
        ];

        if ($isHttps && $domain !== '') {
            $config['APP_URL'] = 'https://' . $domain;
        }

        $this->writer->write($config);
    }
}
