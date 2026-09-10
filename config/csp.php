<?php

use App\Support\Csp\LaravelViteNonceGenerator;
use App\Support\Csp\OpenMailPreset;

/**
 * Content Security Policy Configuration
 *
 * CSP protects against XSS by controlling which resources the browser is allowed to load.
 * This application uses spatie/laravel-csp with Vite nonce integration for automatic
 * nonce injection on script/style tags.
 *
 * Mode Toggle:
 *   - CSP_ENABLED=false: No CSP headers sent (default for development)
 *   - CSP_ENABLED=true + CSP_REPORT_ONLY=true: Report-only mode — violations logged but not blocked
 *   - CSP_ENABLED=true + CSP_REPORT_ONLY=false: Enforce mode — violations blocked by browser
 *
 * Recommended rollout:
 *   1. Deploy with CSP_ENABLED=true, CSP_REPORT_ONLY=true
 *   2. Monitor storage/logs/security.log for CSP violation reports (2-4 weeks)
 *   3. Review violations, adjust presets as needed
 *   4. Switch to enforce mode: CSP_REPORT_ONLY=false
 *
 * Violation Monitoring:
 *   - POST /csp-report receives browser violation reports
 *   - Reports are logged to the 'security' channel in storage/logs/security.log
 *   - Review logs regularly during report-only phase to identify false positives
 */

return [

    /*
    |--------------------------------------------------------------------------
    | CSP Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for CSP headers. When false, no CSP headers are sent.
    | Set to true to enable CSP in report-only or enforce mode.
    |
    */
    'enabled' => env('CSP_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Report Only
    |--------------------------------------------------------------------------
    |
    | When true, sends Content-Security-Policy-Report-Only header.
    | Violations are reported but not blocked by the browser.
    | When false, sends Content-Security-Policy header (enforce mode).
    |
    */
    'report_only' => env('CSP_REPORT_ONLY', true),

    /*
    |--------------------------------------------------------------------------
    | Nonce Generator
    |--------------------------------------------------------------------------
    |
    | Class that generates cryptographic nonces for script/style tags.
    | LaravelViteNonceGenerator uses Vite::useCspNonce() for automatic
    | nonce injection that integrates with Vite's build pipeline.
    |
    */
    'nonce_generator' => LaravelViteNonceGenerator::class,

    /*
    |--------------------------------------------------------------------------
    | Nonce Enabled
    |--------------------------------------------------------------------------
    |
    | Whether to include nonces in CSP directives. Disable if nonces conflict
    | with Vite HMR in development.
    |
    */
    'nonce_enabled' => env('CSP_NONCE_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Enabled While Hot Reloading
    |--------------------------------------------------------------------------
    |
    | When true, CSP headers are sent even during Vite HMR. Set to false
    | to disable CSP during development (default behavior).
    |
    */
    'enabled_while_hot_reloading' => env('CSP_ENABLED_WHILE_HOT_RELOADING', false),

    /*
    |--------------------------------------------------------------------------
    | Presets
    |--------------------------------------------------------------------------
    |
    | Array of Preset class names applied to the enforced CSP policy.
    | Each preset configures specific CSP directives. The OpenMailPreset
    | defines directives for Livewire 3, Alpine.js, and Vite integration.
    |
    */
    'presets' => [
        OpenMailPreset::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Directives
    |--------------------------------------------------------------------------
    |
    | Additional CSP directives added after presets. Use this for one-off
    | directive overrides without creating a new preset class.
    |
    */
    'directives' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Report URI
    |--------------------------------------------------------------------------
    |
    | Endpoint for CSP violation reports in enforce mode.
    | Browser POSTs JSON violation reports to this URL.
    |
    */
    'report_uri' => env('CSP_REPORT_URI', '/csp-report'),

    /*
    |--------------------------------------------------------------------------
    | Report To
    |--------------------------------------------------------------------------
    |
    | Report-To header value for the Reporting API v1.
    | Use if your browser supports Reporting API instead of report-uri.
    |
    */
    'report_to' => env('CSP_REPORT_TO'),

    /*
    |--------------------------------------------------------------------------
    | Reporting Endpoints
    |--------------------------------------------------------------------------
    |
    | Reporting-Endpoints header values (Reporting API v2).
    | Format: ['endpoint-name' => 'https://example.com/report']
    |
    */
    'reporting_endpoints' => [],

    /*
    |--------------------------------------------------------------------------
    | Report Only Presets
    |--------------------------------------------------------------------------
    |
    | Presets applied to the Content-Security-Policy-Report-Only header.
    | Use this to test a stricter policy alongside the enforced policy.
    |
    */
    'report_only_presets' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Only Directives
    |--------------------------------------------------------------------------
    |
    | Additional directives for the report-only policy.
    |
    */
    'report_only_directives' => [
        //
    ],

    /*
    |--------------------------------------------------------------------------
    | Report Only URI
    |--------------------------------------------------------------------------
    |
    | Override report-uri for report-only mode. Falls back to report_uri.
    |
    */
    'report_only_uri' => env('CSP_REPORT_ONLY_URI', '/csp-report'),

    /*
    |--------------------------------------------------------------------------
    | Report Only To
    |--------------------------------------------------------------------------
    |
    | Override report-to for report-only mode. Falls back to report_to.
    |
    */
    'report_only_to' => env('CSP_REPORT_ONLY_TO'),

];

/*
|--------------------------------------------------------------------------
| CSP Enforcement Procedure
|--------------------------------------------------------------------------
|
| Follow this procedure to transition from report-only to enforcement:
|
| STEP 1: Deploy with CSP_ENABLED=true, CSP_REPORT_ONLY=true
|   - CSP headers are sent as Content-Security-Policy-Report-Only
|   - Violations are reported to /csp-report but NOT blocked by browser
|   - Monitor storage/logs/security.log for violation reports
|
| STEP 2: Monitor for 2-4 weeks
|   - Review all violation reports in the security log channel
|   - Categorize: legitimate violations (need policy adjustment) vs noise (browser extensions, etc.)
|   - Adjust OpenMailPreset if legitimate violations require new directives
|
| STEP 3: Switch to enforcement mode
|   - Set CSP_ENABLED=true, CSP_REPORT_ONLY=false in .env
|   - Violations are now BLOCKED by the browser
|   - Continue monitoring for new violations (some may shift from warn to block)
|
| STEP 4: Optional strictening
|   - After 2+ weeks in enforcement with no issues, consider:
|     - Removing 'unsafe-inline' from script-src (requires refactoring inline scripts to nonces)
|     - Adding report-only stricter policy via report_only_presets for testing
|
| ROLLBACK: If enforcement breaks functionality:
|   - Set CSP_REPORT_ONLY=true to revert to report-only mode
|   - Or set CSP_ENABLED=false to disable CSP entirely
|   - Investigate and fix the violation, then re-enable enforcement
|
| FILES INVOLVED:
|   - config/csp.php — this file (policy configuration)
|   - app/Support/Csp/OpenMailPreset.php — CSP directives for Livewire/Alpine/Vite
|   - app/Support/Csp/LaravelViteNonceGenerator.php — Vite nonce integration
|   - app/Http/Controllers/CspReportController.php — violation report endpoint
|   - storage/logs/security.log — violation reports (daily rotation, 90-day retention)
|
*/
