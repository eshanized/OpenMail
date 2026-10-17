<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\CspReportController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Mockery;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CspReportControllerTest extends TestCase
{
    public function test_standard_csp_report_is_logged_and_returns_no_content(): void
    {
        $channel = Mockery::mock();
        $channel->shouldReceive('warning')->once()->with('CSP Violation', Mockery::on(
            fn (array $context): bool => $context['blocked_uri'] === 'https://tracker.example.test'
                && $context['violated_directive'] === 'script-src'
                && $context['line_number'] === 12
                && array_key_exists('user_id', $context)
                && array_key_exists('ip', $context)
        ));
        Log::shouldReceive('channel')->once()->with('security')->andReturn($channel);

        $request = Request::create('/csp-report', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_REFERER' => 'https://mail.example.test/settings',
            'REMOTE_ADDR' => '203.0.113.10',
        ], json_encode(['csp-report' => [
            'blocked-uri' => 'https://tracker.example.test',
            'violated-directive' => 'script-src',
            'line-number' => 12,
        ]], JSON_THROW_ON_ERROR));

        $response = app(CspReportController::class)->store($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('', $response->getContent());
    }

    public function test_flat_report_payload_is_accepted_and_optional_fields_default_to_null(): void
    {
        $channel = Mockery::mock();
        $channel->shouldReceive('warning')->once()->with('CSP Violation', Mockery::on(
            fn (array $context): bool => $context['blocked_uri'] === 'inline'
                && $context['violated_directive'] === null
                && $context['effective_directive'] === null
                && $context['url'] === 'unknown'
        ));
        Log::shouldReceive('channel')->once()->with('security')->andReturn($channel);

        $request = Request::create('/csp-report', 'POST', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], json_encode(['blocked-uri' => 'inline'], JSON_THROW_ON_ERROR));

        $response = app(CspReportController::class)->store($request);

        $this->assertSame(204, $response->getStatusCode());
    }
}
