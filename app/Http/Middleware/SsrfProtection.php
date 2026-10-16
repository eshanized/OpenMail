<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
     * Includes IPv4, IPv6, decimal, octal, and hex-encoded bypass variants.
     */
    private const PRIVATE_IP_PATTERNS = [
        // IPv4 loopback
        '/^https?:\/\/(localhost|0\.0\.0\.0|127\.\d{1,3}\.\d{1,3}\.\d{1,3})/i',
        // IPv4 private RFC 1918 (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16)
        '/^https?:\/\/10\.\d{1,3}\.\d{1,3}\.\d{1,3}/i',
        '/^https?:\/\/172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}/i',
        '/^https?:\/\/192\.168\.\d{1,3}\.\d{1,3}/i',
        // IPv4 link-local
        '/^https?:\/\/169\.254\.\d{1,3}\.\d{1,3}/i',
        // IPv4 "0.x" shorthand (resolves to 0.0.0.0)
        '/^https?:\/\/0(\.0){0,2}(\.0)?(\:|\/|$)/i',
        // IPv4 decimal representation (e.g. http://2130706433 = 127.0.0.1)
        // Block 9+ digit numbers (covers 10.0.0.0/8 through 255.255.255.255)
        '/^https?:\/\/\d{9,}(\:|\/|$)/i',
        // IPv4 octal representation (e.g. http://0177.0.0.1 = 127.0.0.1)
        '/^https?:\/\/0\d{2,3}\.\d{1,3}\.\d{1,3}/i',
        // IPv4 hex representation (e.g. http://0x7f.0x0.0x0.0x1)
        '/^https?:\/\/0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}/i',
        // IPv6 loopback and private ranges
        '/^https?:\/\/\[?::1\]?\//i',
        '/^https?:\/\/\[?::ffff:127\.\d{1,3}\.\d{1,3}\.\d{1,3}\]?/i',
        '/^https?:\/\/\[?(fc00|fd[0-9a-f]{2}|fe80|::)/i',
        // IPv4-mapped IPv6
        '/^https?:\/\/\[?::ffff:\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\]?/i',
    ];

    private const EXCLUDED_INPUT_KEYS = [
        'body',
        'content',
        'body_html',
        'body_plain',
        'html',
        'mime_message',
        'subject',
        'message',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $this->validateInput($request->query());
        $this->validateInput($request->post());

        return $next($request);
    }

    /**
     * Validate an array of input data for dangerous URLs.
     *
     * @throws HttpException
     */
    private function validateInput(array $data): void
    {
        foreach ($data as $key => $value) {
            $keyStr = is_string($key) ? strtolower($key) : '';
            if (in_array($keyStr, self::EXCLUDED_INPUT_KEYS, true)) {
                continue;
            }

            if (is_string($value)) {
                if ($this->looksLikeUrl($value) || $this->isDangerousUrl($value)) {
                    $this->validateUrl($value);
                }
            } elseif (is_array($value)) {
                $this->validateInput($value);
            }
        }
    }

    /**
     * Check if a string looks like a URL or dangerous scheme.
     */
    public function looksLikeUrl(string $value): bool
    {
        $trimmed = trim($value);

        return preg_match('/^[a-z0-9+.-]+:\/\//i', $trimmed) === 1
            || preg_match('/^(javascript|data|vbscript|file):/i', $trimmed) === 1;
    }

    /**
     * Check if a string contains a dangerous URL and throw 422 if so.
     *
     * @throws HttpException
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
        if (preg_match('/^('.implode('|', self::DANGEROUS_SCHEMES).'):/i', $url)) {
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
