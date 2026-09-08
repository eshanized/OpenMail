<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SSRF Protection Middleware (SEC-07)
 *
 * Blocks requests containing dangerous URL schemes and private IP addresses
 * in query parameters, form data, or request body. Prevents server-side
 * request forgery via message content or user input.
 */
class SsrfProtection
{
    /**
     * Dangerous URL schemes that should never be followed by the server.
     */
    private const DANGEROUS_SCHEMES = ['javascript', 'data', 'vbscript', 'file'];

    /**
     * Private/reserved IP ranges (RFC 1918, loopback, link-local, etc.).
     */
    private const PRIVATE_IP_PATTERNS = [
        '/^https?:\/\/(localhost)/i',
        '/^https?:\/\/127\./i',
        '/^https?:\/\/10\./i',
        '/^https?:\/\/192\.168\./i',
        '/^https?:\/\/169\.254\./i',
        '/^https?:\/\/\[::1\]/i',
        '/^https?:\/\/0\.0\.0\.0/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        // Check all input data for dangerous URLs
        $this->validateInput($request->query());
        $this->validateInput($request->post());
        $this->validateInput($request->except(['password', 'password_confirmation']));

        return $next($request);
    }

    /**
     * Validate an array of input data for dangerous URLs.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function validateInput(array $data): void
    {
        foreach ($data as $value) {
            if (is_string($value)) {
                $this->validateUrl($value);
            } elseif (is_array($value)) {
                $this->validateInput($value);
            }
        }
    }

    /**
     * Check if a string contains a dangerous URL and throw 422 if so.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    private function validateUrl(string $value): void
    {
        if ($this->isDangerousUrl($value)) {
            abort(422, 'Potentially malicious URL detected in input.');
        }
    }

    /**
     * Determine if a URL is dangerous (SSRF or XSS vector).
     */
    public function isDangerousUrl(string $url): bool
    {
        // Block dangerous URL schemes
        if (preg_match('/^(' . implode('|', self::DANGEROUS_SCHEMES) . '):/i', $url)) {
            return true;
        }

        // Block private/reserved IP ranges
        foreach (self::PRIVATE_IP_PATTERNS as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }
}
