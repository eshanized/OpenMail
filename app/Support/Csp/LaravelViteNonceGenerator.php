<?php

namespace App\Support\Csp;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

/**
 * Generates CSP nonces using Vite's built-in nonce generation.
 *
 * This integrates with Vite's build pipeline to automatically inject
 * the same nonce into both the CSP header and script/style tags.
 *
 * In development (HMR), Vite handles nonce injection directly.
 * In production, Vite::useCspNonce() returns a base64-encoded nonce
 * derived from cryptographically secure random data.
 */
class LaravelViteNonceGenerator implements NonceGenerator
{
    /**
     * Generate a CSP nonce string.
     *
     * Returns a base64-encoded nonce from Vite's CSP nonce generator.
     * The nonce is automatically added to script/style tags by Vite's
     * Blade directives (@vite) and the spatie/laravel-csp middleware.
     */
    public function generate(): string
    {
        return Vite::useCspNonce();
    }
}
