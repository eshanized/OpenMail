<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\InstallLock;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SsrfProtection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class SecurityMiddlewareTest extends TestCase
{
    public function test_security_headers_are_attached_to_http_responses_without_hsts(): void
    {
        config(['app.force_https' => false]);
        $request = Request::create('http://mail.example.test/');
        $response = (new SecurityHeaders)->handle($request, fn () => new Response('ok'));

        $this->assertSame('DENY', $response->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertSame('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertSame('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertNull($response->headers->get('Strict-Transport-Security'));
    }

    public function test_security_headers_add_hsts_for_https_or_forced_https(): void
    {
        $httpsRequest = Request::create('https://mail.example.test/');
        $httpsResponse = (new SecurityHeaders)->handle($httpsRequest, fn () => new Response('ok'));
        $this->assertSame('max-age=31536000; includeSubDomains', $httpsResponse->headers->get('Strict-Transport-Security'));

        config(['app.force_https' => true]);
        $httpRequest = Request::create('http://mail.example.test/');
        $forcedResponse = (new SecurityHeaders)->handle($httpRequest, fn () => new Response('ok'));
        $this->assertSame('max-age=31536000; includeSubDomains', $forcedResponse->headers->get('Strict-Transport-Security'));
    }

    public function test_install_lock_allows_normal_routes_when_installation_is_complete(): void
    {
        File::put(storage_path('installed'), '{}');
        $request = Request::create('/mailbox');
        $response = (new InstallLock)->handle($request, fn () => new Response('passed'));

        $this->assertSame('passed', $response->getContent());
    }

    public function test_install_routes_are_hidden_after_installation(): void
    {
        File::put(storage_path('installed'), '{}');
        $this->expectException(HttpException::class);
        (new InstallLock)->handle(Request::create('/install'), fn () => new Response('should not run'));
    }

    public function test_uninstalled_application_redirects_normal_routes_to_installer(): void
    {
        File::delete(storage_path('installed'));
        $response = (new InstallLock)->handle(Request::create('/mailbox'), fn () => new Response('should not run'));

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/install', parse_url($response->headers->get('Location'), PHP_URL_PATH));
    }

    public function test_uninstalled_application_allows_livewire_and_health_check_routes(): void
    {
        File::delete(storage_path('installed'));

        foreach (['/livewire/update', '/up'] as $path) {
            $response = (new InstallLock)->handle(Request::create($path), fn () => new Response($path));
            $this->assertSame($path, $response->getContent());
        }
    }

    public function test_ssrf_detector_recognizes_url_shapes_and_dangerous_schemes(): void
    {
        $middleware = new SsrfProtection;
        $this->assertTrue($middleware->looksLikeUrl(' https://example.test/path '));
        $this->assertTrue($middleware->looksLikeUrl('javascript:alert(1)'));
        $this->assertFalse($middleware->looksLikeUrl('not a url'));
        $this->assertTrue($middleware->isDangerousUrl('javascript:alert(1)'));
        $this->assertTrue($middleware->isDangerousUrl('data:text/html,hello'));
        $this->assertTrue($middleware->isDangerousUrl('file:///etc/passwd'));
        $this->assertTrue($middleware->isDangerousUrl('https://127.0.0.1/admin'));
        $this->assertTrue($middleware->isDangerousUrl('https://192.168.1.5/private'));
        $this->assertTrue($middleware->isDangerousUrl('https://2130706433/admin'));
        $this->assertFalse($middleware->isDangerousUrl('https://8.8.8.8/'));
        $this->assertFalse($middleware->isDangerousUrl('https://mail.example.test/'));
    }

    public function test_ssrf_middleware_blocks_dangerous_urls_in_nested_input(): void
    {
        $request = Request::create('/mailbox', 'POST', [
            'settings' => ['redirect_url' => 'https://127.0.0.1/internal'],
        ]);

        $this->expectException(HttpException::class);
        try {
            (new SsrfProtection)->handle($request, fn () => new Response('should not run'));
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
            throw $exception;
        }
    }

    public function test_ssrf_middleware_does_not_scan_excluded_mail_content_fields(): void
    {
        $request = Request::create('/mailbox', 'POST', [
            'subject' => 'Link: https://127.0.0.1/not-a-request',
            'body_html' => '<a href="https://127.0.0.1/not-a-request">message content</a>',
            'title' => 'normal title',
        ]);

        $response = (new SsrfProtection)->handle($request, fn () => new Response('accepted'));
        $this->assertSame('accepted', $response->getContent());
    }

    public function test_ssrf_middleware_passes_safe_input_through(): void
    {
        $request = Request::create('/settings', 'POST', ['redirect_url' => 'https://docs.example.test/']);

        $response = (new SsrfProtection)->handle($request, fn () => new Response('ok'));
        $this->assertSame('ok', $response->getContent());
    }
}
