<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Adds security headers to all responses (SEC-10).
 *
 * Headers applied:
 * - X-Frame-Options: DENY — prevents clickjacking
 * - X-Content-Type-Options: nosniff — prevents MIME type sniffing
 * - Referrer-Policy: strict-origin-when-cross-origin — limits referrer leakage
 * - Permissions-Policy: disables unused browser features
 * - Strict-Transport-Security: HSTS (only on HTTPS)
 *
 * Registered globally in bootstrap/app.php to ensure all responses
 * include these headers (T-05-03 mitigation).
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // SEC-10: Clickjacking prevention
        $response->headers->set('X-Frame-Options', 'DENY');

        // SEC-10: MIME type sniffing prevention
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // SEC-10: Referrer leakage prevention
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // SEC-10: Disable unused browser features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        // HSTS: Only on HTTPS connections
        if ($request->isSecure() || config('app.force_https', false)) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        return $response;
    }
}
