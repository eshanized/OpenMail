<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Handles CSP violation reports from the browser.
 *
 * When Content-Security-Policy-Report-Only (or enforce) header includes
 * a report-uri directive, the browser POSTs JSON violation reports to
 * this endpoint. Reports are logged to the 'security' channel for
 * monitoring during the report-only phase (D-01, D-03).
 *
 * The endpoint is rate-limited (throttle:60,1) to prevent report flooding (T-05-01).
 * No state is modified — this is a logging-only endpoint.
 */
class CspReportController extends Controller
{
    /**
     * Store a CSP violation report.
     *
     * Accepts POST requests with JSON body containing csp-report object.
     * Logs the violation to the security channel and returns 204 No Content.
     *
     * @see https://www.w3.org/TR/CSP3/#directive-report-uri
     */
    public function store(Request $request): Response
    {
        $report = $request->json()->all();

        // Extract the csp-report object (standard CSP report format)
        $cspReport = $report['csp-report'] ?? $report;

        Log::channel('security')->warning('CSP Violation', [
            'blocked_uri' => $cspReport['blocked-uri'] ?? null,
            'violated_directive' => $cspReport['violated-directive'] ?? null,
            'effective_directive' => $cspReport['effective-directive'] ?? null,
            'original_policy' => $cspReport['original-policy'] ?? null,
            'source_file' => $cspReport['source-file'] ?? null,
            'line_number' => $cspReport['line-number'] ?? null,
            'column_number' => $cspReport['column-number'] ?? null,
            'status_code' => $cspReport['status-code'] ?? null,
            'script_sample' => $cspReport['script-sample'] ?? null,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'url' => $request->headers->get('referer', 'unknown'),
        ]);

        return response()->noContent(204);
    }
}
