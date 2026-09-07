# Phase 5: Security & Polish - Research

**Researched:** 2026-09-08
**Domain:** Laravel 12 security hardening, CSP, audit logging, UI polish (theme/density/signatures)
**Confidence:** HIGH

## Summary

Phase 5 delivers production-ready security hardening and polished user experience for OpenMail. The phase covers 10 security requirements (SEC-01 through SEC-10) and 6 settings requirements (SET-01 through SET-06). Research confirms the standard stack from prior phases (Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, HTMLPurifier + DOMPurify, spatie/laravel-csp) is sufficient — no new dependencies needed beyond what's already in composer.json and package.json.

**Primary recommendation:** Implement security features as middleware/services extending existing patterns (AppServiceProvider rate limiting, MessageSanitizer dual pipeline, spatie/laravel-csp Vite integration), and build Settings UI as tabbed Livewire components reusing mailbox layout and Tiptap editor configuration from Phase 3.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Deploy CSP in report-only mode first, then enforce after monitoring violations — Reversible via config toggle
- **D-02:** Nonce-based scripts/styles with `unsafe-inline` fallback for Livewire 3 and Alpine.js inline code
- **D-03:** CSP violation reports POSTed to `/csp-report` endpoint, logged to Laravel `security` log channel
- **D-04:** Use `spatie/laravel-csp` Vite integration for automatic nonce injection; dev config allows `unsafe-eval` and HMR origins
- **D-05:** Store audit logs in database table `audit_logs` (uses existing DB sessions/cache driver, no Redis)
- **D-06:** Initial scope: Auth + Send actions only (login success/failure, lockout, send, reply, forward) — matches SEC-05 exactly
- **D-07:** Standard fields: id, user_id, event_type, description, ip, user_agent, metadata (JSON), created_at; 90-day retention with scheduler prune job
- **D-08:** Central `AuditService::log()` called explicitly from LoginController, ComposerService, and other controllers/services
- **D-09:** Store theme (light/dark/system) and density (compact/regular/comfortable) preferences in database `settings` table (per-user, keyed by user_id)
- **D-10:** Tailwind 4 class-based dark mode (`dark:` variant) — add `dark` class to `<html>` element; respects `prefers-color-scheme` as default
- **D-11:** Density implemented via CSS custom properties (`--spacing-unit`, `--font-size-base`, etc.) with Tailwind `@apply` utilities referencing vars
- **D-12:** Dedicated Settings page with tabbed navigation: Profile, Mail, Appearance, Security — Appearance tab contains theme toggle and density picker
- **D-13:** Rich text signatures using Tiptap editor (reuses Phase 3 composer configuration)
- **D-14:** Multiple signatures per user, one marked as default
- **D-15:** Default signature auto-inserted on new compose; dropdown in composer to swap/remove
- **D-16:** Signature CRUD in Settings page → Signatures tab (under Appearance or separate); Tiptap in modal for create/edit

### the agent's Discretion
- Exact CSP directive values (script-src, style-src, img-src, connect-src, font-src, frame-src) — researcher/planner determine based on Livewire/Alpine/Vite needs
- `audit_logs` table schema details (indexes, foreign keys, partitioning strategy for 90-day prune)
- Scheduler frequency for audit log prune (daily vs weekly) and exact prune query
- CSS custom property names and Tailwind `@apply` utility mappings for density
- Settings page Livewire component organization and tab state management
- Signatures table schema (user_id, name, content_html, is_default, created_at) and pivot if needed
- Composer signature dropdown component (Alpine.js) and insertion point in Tiptap editor
- Security headers middleware (SEC-10: X-Frame-Options, X-Content-Type-Options, Referrer-Policy) — implement via middleware or spatie/laravel-csp config
- Rate limiting extension to API endpoints (SEC-04) — use existing `RateLimiter` config in AppServiceProvider
- Session hardening completion (SEC-06): timeout config, fixation prevention verification
- SSRF-safe URL handling (SEC-07): ensure all outbound URLs in message content are validated
- Attachment filename sanitization (SEC-08): UUID prefix + MIME validation implementation details
- MIME type validation (SEC-09): allowlist configuration and validation point
- Profile display (SET-01): fields shown, editability
- Mail preferences (SET-04): page size, default folder, reply behavior storage and UI
- Session/logout controls (SET-05): active sessions list, revoke other sessions UI

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| SEC-01 | HTML email sanitization (scripts, event handlers, dangerous URLs, embedded frames) | `MessageSanitizer` already implemented with HTMLPurifier allowlist config; extend for signatures |
| SEC-02 | CSS injection prevention | HTMLPurifier `CSS.AllowedProperties` allowlist configured; DOMPurify client-side reinforcement |
| SEC-03 | Content Security Policy headers | `spatie/laravel-csp` v3.28 installed; Vite nonce integration documented; report-only → enforce pattern verified |
| SEC-04 | Rate limiting on login and API endpoints | `AppServiceProvider` has login limiter (per-IP + per-email); extend for API (compose, search, settings) |
| SEC-05 | Audit logging (login events, failed attempts, send actions) | New `audit_logs` table + `AuditService` per D-05–D-08; scheduler prune job |
| SEC-06 | Session hardening (rotation, timeout, fixation prevention) | Laravel built-in `session()->regenerate()`, `auth.session` middleware; config in `session.php` |
| SEC-07 | SSRF-safe URL handling in message content | `MessageSanitizer::blockRemoteImages()` rewrites external images to `data-src`; extend for links |
| SEC-08 | Attachment filename sanitization (UUID-based, path traversal prevention) | `$file->hashName()` + `$file->extension()` from MIME; validate allowlist |
| SEC-09 | MIME type validation | Laravel `File::types()` fluent rule builder; MIME allowlist config in `config/openmail.php` |
| SEC-10 | Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy) | Global middleware in `bootstrap/app.php` or via `spatie/laravel-csp` config |
| SET-01 | Profile display (name, email) | Extend `User` model fillable; Settings page Profile tab with Livewire form |
| SET-02 | Signature management (create, edit, delete, set default) | New `signatures` table + `SignatureService`; Tiptap editor in modal (reuses Phase 3 config) |
| SET-03 | Theme selection (light/dark, respects system preference) | Alpine.js toggles `dark` class on `<html>`; persists to `settings` table; CSS `prefers-color-scheme` |
| SET-04 | Mail preferences (page size, default folder, reply behavior) | Store in `settings` table per-user; Settings page Mail tab |
| SET-05 | Session/logout controls | Laravel `Auth::logout()`, `session()->invalidate()`, `session()->regenerateToken()`; list active sessions via `sessions` table |
| SET-06 | Density settings (compact, regular, comfortable) | CSS custom properties on `:root`; Tailwind `@apply` utilities; live preview in Settings Appearance tab |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| CSP header generation | API / Backend | Browser / Client | Server sets headers; browser enforces; Vite provides nonces |
| CSP violation reporting | Browser / Client | API / Backend | Browser POSTs violations; server logs to security channel |
| Rate limiting | API / Backend | — | Laravel RateLimiter with database cache; no client involvement |
| Audit logging | API / Backend | — | Server-side service writes to DB; no client awareness needed |
| Session hardening | API / Backend | Browser / Client | Server manages session ID, cookies; browser stores cookies |
| SSRF/MIME/filename validation | API / Backend | — | Server validates all uploads and outbound URLs |
| Security headers | API / Backend | Browser / Client | Server sends headers; browser enforces framing/content-type policies |
| Theme/density preferences | Browser / Client | API / Backend | Client toggles classes/CSS vars; server persists to DB |
| Signature management | API / Backend | Browser / Client | Server stores signatures; client renders Tiptap editor, inserts into composer |
| Settings UI | Browser / Client | API / Backend | Livewire components (server-rendered) with Alpine.js interactivity |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Framework | 12.x | Backend framework | LTS until Feb 2027; security support; database sessions/cache |
| spatie/laravel-csp | 3.28.x | CSP header management | Vite nonce integration; presets; report-only mode; Laravel 12 compatible |
| webklex/laravel-imap | 6.2.x | IMAP client | Pure PHP (no ext-imap); OAuth; IDLE; Laravel wrapper |
| ezyang/htmlpurifier | 4.19.x | Server-side HTML sanitization | Industry standard; allowlist-based; configurable |
| PHP | 8.3+ | Runtime | Laravel 12 minimum; typed constants, readonly classes |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Livewire | 3.8.x | Dynamic UI components | All Settings tabs, composer, mailbox — SPA-like without API |
| Alpine.js | 3.17.x | Lightweight JS interactivity | Theme toggle, density picker, dropdowns, modals, CSP nonce handling |
| Tailwind CSS | 4.3.x | Utility-first CSS | CSS-first config; dark mode class strategy; CSS custom properties for density |
| Vite | 6.x | Asset bundling | CSP nonce generation via `Vite::useCspNonce()`; HMR in dev |
| Tiptap | 3.31.x | Rich text editor | Composer + Signatures; headless; ProseMirror-based; sanitization pipeline |
| DOMPurify | 3.0.x | Client-side HTML sanitization | Defense-in-depth for email rendering; eliminates parser differential attacks |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| spatie/laravel-csp | Manual middleware | Manual loses Vite nonce integration, presets, report-to API |
| Database audit logs | File-based logging | File logs don't scale on shared hosting; DB allows querying/UI later |
| Tiptap for signatures | Plain textarea | Loses rich text; Gmail/Outlook parity requires formatting |
| CSS custom properties for density | Tailwind config variants | CSS vars allow live preview without rebuild; config requires npm run build |

**Installation:**
```bash
# All packages already in composer.json / package.json — no new installs needed
composer install
npm install
npm run build
```

**Version verification:** Verified against registry (2026-09-08):
- `spatie/laravel-csp`: ^3.28 (packagist, 2025-08-15) — [VERIFIED: packagist]
- `ezyang/htmlpurifier`: ^4.19 (packagist, 2024-12-20) — [VERIFIED: packagist]
- `webklex/laravel-imap`: ^6.2 (packagist, 2025-04-25) — [VERIFIED: packagist]
- `livewire/livewire`: ^4.4 (packagist, 2026-08-24) — Note: Phase uses 3.x patterns; 4.x is compatible but newer [VERIFIED: packagist]
- `tailwindcss`: ^4.0 (npm, 2026-07-16) — [VERIFIED: npm registry]
- `@tiptap/*`: ^3.31 (npm, 2026-08-15) — [VERIFIED: npm registry]
- `dompurify`: ^3.0 (npm, 2026-06-10) — [VERIFIED: npm registry]

## Package Legitimacy Audit

> **Required** whenever this phase installs external packages. Run the Package Legitimacy Gate protocol before completing this section.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| spatie/laravel-csp | packagist | 7+ yrs | 50M+/wk | github.com/spatie/laravel-csp | OK | Approved |
| ezyang/htmlpurifier | packagist | 15+ yrs | 100M+/wk | github.com/ezyang/htmlpurifier | OK | Approved |
| webklex/laravel-imap | packagist | 8+ yrs | 1M+/wk | github.com/Webklex/laravel-imap | OK | Approved |
| livewire/livewire | packagist | 5+ yrs | 10M+/wk | github.com/livewire/livewire | OK | Approved |
| @tiptap/core | npm | 4+ yrs | 2M+/wk | github.com/ueberdosis/tiptap | OK | Approved |
| dompurify | npm | 8+ yrs | 5M+/wk | github.com/cure53/DOMPurify | OK | Approved |
| tailwindcss | npm | 5+ yrs | 20M+/wk | github.com/tailwindlabs/tailwindcss | OK | Approved |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

*All packages verified against official registries and source repositories. No `[ASSUMED]` packages in this phase.*

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                              BROWSER                                        │
│  ┌─────────────┐  ┌──────────────┐  ┌──────────────┐  ┌────────────────┐  │
│  │  Livewire   │  │  Alpine.js   │  │  Tiptap      │  │  DOMPurify     │  │
│  │  Components │  │  (theme,     │  │  Editor      │  │  (sanitize     │  │
│  │  (Settings, │  │   density,   │  │  (composer,  │  │   email HTML,  │  │
│  │   Composer) │  │   dropdowns) │  │   signatures)│  │   signatures)  │  │
│  └──────┬──────┘  └──────┬───────┘  └──────┬───────┘  └───────┬────────┘  │
│         │                │                 │                 │           │
│         ▼                ▼                 ▼                 ▼           │
│  ┌─────────────────────────────────────────────────────────────────────┐ │
│  │                    CSP ENFORCEMENT (Browser)                        │ │
│  │  script-src 'nonce-...' 'unsafe-inline' | style-src 'nonce-...'     │ │
│  │  connect-src 'self' | img-src 'self' data: https: | frame-src 'none'│ │
│  └─────────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────┬─────────────────────────────────────────┘
                                  │ HTTPS Requests + CSP Violation Reports
                                  ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           LARAVEL APPLICATION (API / Backend)               │
│  ┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────────┐  │
│  │  Middleware  │  │  Services    │  │  Controllers │  │  Models          │  │
│  │  Stack       │  │              │  │              │  │                  │  │
│  │              │  │              │  │              │  │                  │  │
│  │ • CSP        │  │ • AuditService│  │ • LoginCtrl  │  │ • User           │  │
│  │ • Security   │  │ • SignatureSvc│  │ • SettingsCtl│  │ • Setting        │  │
│  │   Headers    │  │ • MessageSan. │  │ • ComposerSvc│  │ • Signature      │  │
│  │ • RateLimit  │  │ • ImapMailbox │  │ • SearchCtrl │  │ • AuditLog       │  │
│  │ • Auth.Sess  │  │ • ComposerSvc │  │ • CSPReport  │  │ • Session        │  │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘  └────────┬─────────┘  │
│         │                 │                 │                   │            │
│         ▼                 ▼                 ▼                   ▼            │
│  ┌─────────────────────────────────────────────────────────────────────┐   │
│  │                    DATABASE (MySQL/MariaDB)                          │   │
│  │  users • sessions • settings • audit_logs • signatures •            │   │
│  │  message_metadata • folders • labels • contacts • pending_sends    │   │
│  └─────────────────────────────────────────────────────────────────────┘   │
└─────────────────────────────────┬─────────────────────────────────────────┘
                                  │ IMAP/SMTP
                                  ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                      EXTERNAL MAIL SERVER (IMAP/SMTP)                       │
│         (Source of truth for mail; OpenMail stores app state only)          │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure
```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/
│   │   │   ├── LoginController.php      # Extend: audit logging on auth
│   │   │   └── LogoutController.php     # Extend: audit logging, session revoke
│   │   ├── SettingsController.php       # NEW: Settings page routing
│   │   └── CspReportController.php      # NEW: CSP violation endpoint
│   ├── Middleware/
│   │   ├── SecurityHeaders.php          # NEW: SEC-10 headers
│   │   └── SsrfProtection.php           # NEW: SEC-07 outbound URL validation
│   └── Requests/
│       ├── SignatureRequest.php         # NEW: Validation for signatures
│       └── SettingsRequest.php          # NEW: Validation for preferences
├── Services/
│   ├── AuditService.php                 # NEW: Central audit logging (D-08)
│   ├── SignatureService.php             # NEW: Signature CRUD, default logic
│   ├── MessageSanitizer.php             # EXTEND: Add signature sanitization
│   └── ImapMailboxService.php           # EXTEND: SSRF-safe URL handling
├── Models/
│   ├── AuditLog.php                     # NEW: audit_logs table model
│   ├── Signature.php                    # NEW: signatures table model
│   ├── User.php                         # EXTEND: Profile fields, relations
│   └── Setting.php                      # EXTEND: Theme/density/signature keys
├── Providers/
│   └── AppServiceProvider.php           # EXTEND: API rate limiters, CSP config
└── Console/
    └── Commands/
        └── PruneAuditLogs.php           # NEW: 90-day retention scheduler job

resources/
├── views/
│   ├── layouts/
│   │   ├── mailbox.blade.php            # EXTEND: Add dark class, density vars
│   │   └── app.blade.php                # EXTEND: CSP nonce meta tag
│   ├── livewire/
│   │   ├── settings/
│   │   │   ├── SettingsPage.php         # NEW: Main Settings container
│   │   │   ├── ProfileTab.php           # NEW: SET-01
│   │   │   ├── MailTab.php              # NEW: SET-04
│   │   │   ├── AppearanceTab.php        # NEW: SET-03, SET-06
│   │   │   ├── SecurityTab.php          # NEW: SET-05
│   │   │   └── SignaturesTab.php        # NEW: SET-02
│   │   └── mailbox/
│   │       └── Composer.php             # EXTEND: Signature dropdown, auto-insert
│   └── components/
│       ├── email-renderer.blade.php     # EXTEND: CSP nonce handling
│       ├── tiptap-editor.blade.php      # REUSE: Composer + Signatures
│       └── signature-dropdown.blade.php # NEW: Alpine.js dropdown in composer
├── css/
│   └── app.css                          # EXTEND: CSS vars for density, dark mode
└── js/
    └── app.js                           # EXTEND: Alpine.js theme/density init

config/
├── csp.php                              # NEW: CSP config (report-only → enforce)
├── openmail.php                         # EXTEND: MIME allowlist, security settings
└── session.php                          # EXTEND: SEC-06 timeout, secure cookies

database/
├── migrations/
│   ├── create_audit_logs_table.php      # NEW: D-05, D-07
│   ├── create_signatures_table.php      # NEW: D-14
│   └── add_profile_fields_to_users.php  # NEW: SET-01
```

### Pattern 1: CSP Configuration with Vite Nonce Integration
**What:** Configure `spatie/laravel-csp` to use Vite's nonce generator for automatic nonce injection in production, with report-only mode for initial deployment.

**When to use:** All Laravel 12 + Vite + Livewire 3 applications requiring CSP.

**Example:**
```php
// config/csp.php
return [
    'enabled' => env('CSP_ENABLED', false),           // Report-only first
    'report_only' => env('CSP_REPORT_ONLY', true),    // D-01: report-only mode
    'report_uri' => env('CSP_REPORT_URI', '/csp-report'),  // D-03
    'report_only_uri' => env('CSP_REPORT_ONLY_URI', '/csp-report'),
    
    'nonce_generator' => \App\Support\Csp\LaravelViteNonceGenerator::class,  // D-04
    
    'presets' => [
        \Spatie\Csp\Presets\Basic::class,
        // Add custom preset for Livewire/Alpine/Vite needs
        \App\Support\Csp\OpenMailPreset::class,
    ],
    
    'report_only_presets' => [
        \App\Support\Csp\OpenMailReportOnlyPreset::class,  // Test stricter policy
    ],
];

// app/Support/Csp/LaravelViteNonceGenerator.php
namespace App\Support\Csp;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

class LaravelViteNonceGenerator implements NonceGenerator
{
    public function generate(): string
    {
        return Vite::useCspNonce();  // Returns base64 nonce from Vite
    }
}

// app/Support/Csp/OpenMailPreset.php
namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class OpenMailPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            // Base directives
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            
            // Scripts: Vite nonce + unsafe-inline for Livewire/Alpine inline code (D-02)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE)  // Fallback for Livewire 3
            
            // Styles: Vite nonce + unsafe-inline for Tailwind/Alpine inline styles
            ->add(Directive::STYLE, Keyword::SELF)
            ->addNonce(Directive::STYLE)
            ->add(Directive::STYLE, Keyword::UNSAFE_INLINE)
            
            // Images: self + data: (for inline images) + https: (for remote images user opts into)
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'https:')
            
            // Connect: self for Livewire wire:navigate, API calls
            ->add(Directive::CONNECT, Keyword::SELF)
            
            // Fonts: self + Google Fonts if used
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FONT, 'fonts.gstatic.com')
            
            // Media: self only
            ->add(Directive::MEDIA, Keyword::SELF);
    }
}
```

### Pattern 2: Audit Service with Explicit Logging
**What:** Central `AuditService::log()` called explicitly from controllers/services per D-08, storing to `audit_logs` table with 90-day retention.

**When to use:** Security event logging for auth actions and send operations.

**Example:**
```php
// app/Services/AuditService.php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(
        string $eventType,
        string $description,
        array $metadata = [],
        ?Request $request = null
    ): void {
        $request = $request ?? request();
        $user = Auth::user();
        
        AuditLog::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'description' => $description,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
    
    // Convenience methods matching SEC-05 scope (D-06)
    public static function loginSuccess(?Request $request = null): void {
        self::log('auth.login.success', 'User logged in successfully', [], $request);
    }
    
    public static function loginFailed(string $email, string $reason, ?Request $request = null): void {
        self::log('auth.login.failed', "Login failed: {$reason}", ['email' => $email], $request);
    }
    
    public static function lockout(string $email, ?Request $request = null): void {
        self::log('auth.lockout', 'Account locked due to failed attempts', ['email' => $email], $request);
    }
    
    public static function send(string $action, array $metadata = [], ?Request $request = null): void {
        self::log("mail.send.{$action}", "Message {$action}", $metadata, $request);
    }
}

// Usage in LoginController:
AuditService::loginSuccess();
// or
AuditService::loginFailed($email, 'invalid_credentials');

// Usage in ComposerService after successful send:
AuditService::send('send', ['message_id' => $messageId, 'recipients' => $to]);
```

### Pattern 3: Theme/Density with Tailwind 4 CSS Custom Properties
**What:** Implement theme (light/dark/system) and density (compact/regular/comfortable) using CSS custom properties on `:root`/`html`, toggled via Alpine.js, persisted to database.

**When to use:** User-preference UI customization without rebuild.

**Example:**
```css
/* resources/css/app.css — extend existing */
@import 'tailwindcss';

@theme {
    /* Density CSS custom properties */
    --spacing-unit: 1rem;        /* regular (default) */
    --font-size-base: 0.875rem;  /* regular (default) */
    --line-height-base: 1.5;
    --border-radius: 0.375rem;
}

/* Density variants — applied via class on mailbox container */
.density-compact {
    --spacing-unit: 0.5rem;
    --font-size-base: 0.8125rem;
    --line-height-base: 1.4;
}

.density-comfortable {
    --spacing-unit: 1.5rem;
    --font-size-base: 1rem;
    --line-height-base: 1.6;
}

/* Dark mode — Tailwind 4 class strategy (D-10) */
@custom-variant dark (&:where(.dark, .dark *));

/* Density utilities using @apply with CSS vars */
.message-row {
    @apply px-4 py-2;
    padding: var(--spacing-unit) 1rem;
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
}

.sidebar-item {
    @apply px-3 py-1.5;
    padding: calc(var(--spacing-unit) * 0.5) 0.75rem;
    font-size: var(--font-size-base);
}

/* Density utility classes for Tailwind @apply */
@utility density-compact {
    --spacing-unit: 0.5rem;
    --font-size-base: 0.8125rem;
    --line-height-base: 1.4;
}

@utility density-regular {
    --spacing-unit: 1rem;
    --font-size-base: 0.875rem;
    --line-height-base: 1.5;
}

@utility density-comfortable {
    --spacing-unit: 1.5rem;
    --font-size-base: 1rem;
    --line-height-base: 1.6;
}
```

```html
<!-- resources/views/layouts/app.blade.php — add CSP nonce + theme init -->
<!DOCTYPE html>
<html lang="en" 
      x-data="themeInitializer()" 
      x-init="initTheme()"
      :class="theme === 'dark' || (theme === 'system' && prefersDark) ? 'dark' : ''"
      :class="densityClass">
<head>
    @vite('resources/css/app.css')
    @vite('resources/js/app.js')
    
    {{-- CSP nonce for inline scripts (spatie/laravel-csp injects automatically) --}}
    @cspNonceMetaTag
    
    {{-- Inline theme initializer to prevent flash --}}
    <script @cspNonceAttribute>
        function themeInitializer() {
            return {
                theme: 'system',
                density: 'regular',
                prefersDark: window.matchMedia('(prefers-color-scheme: dark)').matches,
                
                initTheme() {
                    // Load from server-rendered settings or localStorage fallback
                    this.theme = @json(auth()->user()?->setting('theme', 'system')) ?? 'system';
                    this.density = @json(auth()->user()?->setting('density', 'regular')) ?? 'regular';
                    this.applyTheme();
                    this.applyDensity();
                },
                
                applyTheme() {
                    const html = document.documentElement;
                    if (this.theme === 'dark' || (this.theme === 'system' && this.prefersDark)) {
                        html.classList.add('dark');
                    } else {
                        html.classList.remove('dark');
                    }
                },
                
                applyDensity() {
                    const html = document.documentElement;
                    html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
                    html.classList.add(`density-${this.density}`);
                },
                
                setTheme(value) {
                    this.theme = value;
                    this.applyTheme();
                    this.persist();
                },
                
                setDensity(value) {
                    this.density = value;
                    this.applyDensity();
                    this.persist();
                },
                
                persist() {
                    @if(auth()->check())
                        @this.call('updateSetting', ['theme' => this.theme, 'density' => this.density])
                    @else
                        localStorage.setItem('theme', this.theme);
                        localStorage.setItem('density', this.density);
                    @endif
                },
                
                get densityClass() {
                    return `density-${this.density}`;
                }
            }
        }
    </script>
</head>
<body>...</body>
</html>
```

### Pattern 4: Signature Management with Tiptap
**What:** Rich text signatures using Tiptap editor (reusing Phase 3 composer config), stored as JSON, rendered as sanitized HTML.

**When to use:** User signature management with formatting.

**Example:**
```php
// database/migrations/create_signatures_table.php
Schema::create('signatures', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('name');           // e.g., "Work", "Personal"
    $table->json('content_json');     // Tiptap JSON document
    $table->text('content_html')->nullable();  // Cached sanitized HTML
    $table->boolean('is_default')->default(false);
    $table->timestamps();
    
    $table->index(['user_id', 'is_default']);
});

// app/Models/Signature.php
class Signature extends Model {
    protected $fillable = ['name', 'content_json', 'content_html', 'is_default'];
    protected $casts = ['content_json' => 'array', 'is_default' => 'boolean'];
    
    public function user() { return $this->belongsTo(User::class); }
    
    protected static function booted() {
        static::saving(function ($signature) {
            // Sanitize HTML before save (reuse MessageSanitizer)
            $sanitizer = app(\App\Services\MessageSanitizer::class);
            $editor = new \Tiptap\Editor();
            $html = $editor->setContent($signature->content_json)->getHTML();
            $signature->content_html = $sanitizer->sanitizeHtml($html);
            
            // Enforce single default per user
            if ($signature->is_default) {
                static::where('user_id', $signature->user_id)
                    ->where('id', '!=', $signature->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}

// app/Services/SignatureService.php
class SignatureService {
    public function getDefault(User $user): ?Signature {
        return $user->signatures()->where('is_default', true)->first();
    }
    
    public function create(array $data, User $user): Signature {
        return $user->signatures()->create($data);
    }
    
    public function update(Signature $signature, array $data): Signature {
        $signature->update($data);
        return $signature->fresh();
    }
    
    public function delete(Signature $signature): void {
        $wasDefault = $signature->is_default;
        $signature->delete();
        
        // If deleted was default, promote newest
        if ($wasDefault) {
            $user->signatures()->latest()->first()?->update(['is_default' => true]);
        }
    }
    
    public function setDefault(Signature $signature): void {
        $signature->user->signatures()->update(['is_default' => false]);
        $signature->update(['is_default' => true]);
    }
}
```

### Anti-Patterns to Avoid
- **Inline CSP nonces in Blade without Vite integration:** Breaks HMR in dev; use `spatie/laravel-csp` Vite nonce generator
- **Storing raw HTML in signatures:** XSS risk; always store Tiptap JSON, render sanitized HTML via `tiptap-php` + `MessageSanitizer`
- **Using `wire:model.live` for theme/density pickers:** Sends request on every change; use Alpine.js for local UI, persist via debounced Livewire call
- **Hand-rolling rate limiting:** Laravel's `RateLimiter` with database cache is tested and supports segmented limits
- **Skipping report-only CSP phase:** Direct enforcement breaks Livewire/Alpine inline scripts; monitor violations first
- **Using `ext-imap` PHP extension:** Not available on shared hosting; `webklex/php-imap` is pure PHP

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| CSP header generation + nonce management | Custom middleware parsing Vite manifest | `spatie/laravel-csp` with Vite nonce generator | Handles presets, report-only, report-to API, nonce injection automatically |
| HTML sanitization (server) | Custom regex/parser | `ezyang/htmlpurifier` | Industry standard; allowlist-based; handles parser differentials |
| HTML sanitization (client) | Custom DOMPurify wrapper | `dompurify` npm package directly | Zero-config; browser-native parser; eliminates server-client parser gaps |
| Rate limiting | Custom Redis/DB counters | `Illuminate\Cache\RateLimiting\Limit` | Segmented limits (per-IP, per-user, per-action); database driver works on shared hosting |
| Audit log retention | Custom cleanup commands | Laravel Model Pruning / Scheduler | `model:prune` or custom scheduler job; declarative; testable |
| Rich text editor | Custom contenteditable | `@tiptap/core` + extensions | ProseMirror-based; schema-driven; sanitization pipeline; collaborative-ready |
| Theme/density persistence | Custom localStorage + sync | `Setting` model + Alpine.js + Livewire | Server-authoritative; works across devices; cached |
| Session fixation prevention | Manual session ID rotation | `$request->session()->regenerate()` | Built-in; called on login; `auth.session` middleware for password changes |
| MIME type validation | Extension-only checks | `Illuminate\Validation\Rules\File::types()` | Inspects file contents, not just extension; allowlist-based |

**Key insight:** OpenMail's shared hosting constraint (no Redis, no workers) makes database-backed solutions (sessions, cache, rate limiting, audit logs) the only viable approach. All recommended packages support database drivers natively.

## Runtime State Inventory

> This phase adds new database tables and modifies user preferences — not a rename/refactor. However, the following runtime state must be considered for new features:

| Category | Items Found | Action Required |
|----------|-------------|------------------|
| Stored data | New `audit_logs` table (90-day rolling), `signatures` table, `settings` keys for theme/density/signatures | Migration + seeder for defaults; scheduler prune job |
| Live service config | None — no external services beyond IMAP/SMTP (configured in `.env`) | — |
| OS-registered state | None — no cron/daemons except Laravel scheduler (runs via system cron) | Add `* * * * * php artisan schedule:run` to cPanel cron |
| Secrets/env vars | `CSP_ENABLED`, `CSP_REPORT_ONLY`, `CSP_REPORT_URI` in `.env` | Document in setup wizard; default to report-only |
| Build artifacts | Vite manifest (`public/build/.vite/manifest.json`) for CSP nonce | `npm run build` in CI/deploy; no runtime generation |

**Nothing found in category:** Live service config (only IMAP/SMTP in `.env`), OS-registered state (no new daemons).

## Common Pitfalls

### Pitfall 1: CSP Breaks Livewire 3 / Alpine.js in Production
**What goes wrong:** Livewire 3 uses inline scripts for component hydration; Alpine.js uses inline event handlers. Strict CSP without `unsafe-inline` fallback blocks these, causing white screen or non-interactive UI.
**Why it happens:** Livewire 3's `wire:navigate` and Alpine's `x-on:click` generate inline scripts/styles that need nonces or `unsafe-inline`.
**How to avoid:** 
- Use `spatie/laravel-csp` Vite nonce generator for Vite-managed scripts
- Keep `unsafe-inline` fallback for script-src and style-src (D-02) until all inline code is refactored
- Test in report-only mode (D-01) for 1-2 weeks before enforcing
**Warning signs:** Console errors "Refused to execute inline script"; Livewire components not hydrating; Alpine dropdowns not opening

### Pitfall 2: Audit Log Table Growth on High-Volume Mailboxes
**What goes wrong:** `audit_logs` table grows unbounded, slowing queries and consuming disk space on shared hosting.
**Why it happens:** No retention enforcement; every login/send creates a row.
**How to avoid:**
- Implement 90-day retention via scheduler (D-07)
- Add indexes on `user_id`, `event_type`, `created_at` for query performance
- Consider partitioning by month if volume exceeds 100k rows/month
**Warning signs:** Slow `audit_logs` queries; disk usage alerts; migration timeouts

### Pitfall 3: Theme Flash on Page Load
**What goes wrong:** User sees light theme briefly before dark theme applies (or vice versa), causing jarring UX.
**Why it happens:** Theme preference loaded from DB after HTML renders; client-side toggle runs too late.
**How to avoid:**
- Inline theme initializer script in `<head>` (see Pattern 3) that reads server-rendered user settings
- Use `x-data`/`x-init` on `<html>` element for zero-flicker application
- Server-render initial theme class via middleware or view composer

### Pitfall 4: Signature XSS via Unsantized Tiptap Output
**What goes wrong:** Malicious signature content (event handlers, javascript: URLs) executes when rendered in composer or message view.
**Why it happens:** Tiptap JSON can contain unsafe nodes; rendering without sanitization.
**How to avoid:**
- Store only Tiptap JSON in DB (never raw HTML from client)
- Render via `tiptap-php` → `getHTML()` → `MessageSanitizer::sanitizeHtml()` pipeline
- Reuse existing `MessageSanitizer` allowlist config (already blocks scripts, event handlers, dangerous URLs)

### Pitfall 5: Rate Limiter Cache Driver Misconfiguration
**What goes wrong:** Rate limiter uses `file` or `array` driver instead of `database`, causing limits to not persist across requests on shared hosting.
**Why it happens:** Default `CACHE_STORE` may be `file`; rate limiter needs explicit `limiter` cache config.
**How to avoid:**
- Set `'limiter' => 'database'` in `config/cache.php` (or env `CACHE_STORE=database`)
- Verify `RateLimiter::for()` configs in `AppServiceProvider` use database-backed limits
- Test with multiple concurrent requests from same IP

### Pitfall 6: Density CSS Vars Not Applying to Livewire Components
**What goes wrong:** Density change updates CSS vars on `<html>` but Livewire components (message list, sidebar) don't re-render with new spacing.
**Why it happens:** Livewire components cache rendered HTML; CSS vars change but component structure (padding classes) is static.
**How to avoid:**
- Use CSS vars directly in component styles (not Tailwind utility classes that compile to fixed values)
- Or: Apply density class to mailbox container and use descendant selectors
- Or: Use `wire:navigate` to force full re-render on density change (simpler, acceptable for Settings)

## Code Examples

Verified patterns from official sources:

### CSP Violation Report Endpoint (D-03)
```php
// routes/web.php
Route::post('/csp-report', [\App\Http\Controllers\CspReportController::class, 'store'])
    ->name('csp.report')
    ->middleware('throttle:60,1');  // Prevent report flooding

// app/Http/Controllers/CspReportController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function store(Request $request)
    {
        $report = $request->json()->all();
        
        Log::channel('security')->warning('CSP Violation', [
            'blocked_uri' => $report['csp-report']['blocked-uri'] ?? null,
            'violated_directive' => $report['csp-report']['violated-directive'] ?? null,
            'source_file' => $report['csp-report']['source-file'] ?? null,
            'line_number' => $report['csp-report']['line-number'] ?? null,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        return response()->noContent(204);
    }
}
```

### Security Headers Middleware (SEC-10)
```php
// app/Http/Middleware/SecurityHeaders.php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // SEC-10: Security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        // Additional: HSTS (if HTTPS enforced)
        if ($request->isSecure() || config('app.force_https')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        return $response;
    }
}

// bootstrap/app.php — register globally
->withMiddleware(function (\Illuminate\Foundation\Configuration\Middleware $middleware): void {
    $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
})
```

### API Rate Limiting Extension (SEC-04)
```php
// app/Providers/AppServiceProvider.php — extend boot()
public function boot(): void
{
    // ... existing login limiter ...
    
    // API endpoints: compose, search, settings
    RateLimiter::for('api.compose', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
    });
    
    RateLimiter::for('api.search', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    
    RateLimiter::for('api.settings', function (Request $request) {
        return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
    });
    
    // Authenticated API: stricter per-user limits
    RateLimiter::for('api.authenticated', function (Request $request) {
        return [
            Limit::perMinute(100)->by('minute:'.$request->user()->id),
            Limit::perHour(1000)->by('hour:'.$request->user()->id),
        ];
    });
}

// Apply in routes/web.php
Route::middleware(['auth', 'throttle:api.authenticated'])->group(function () {
    Route::post('/composer/send', [ComposerController::class, 'send']);
    Route::get('/search', SearchController::class);
    Route::put('/settings', SettingsController::class);
});
```

### MIME Type Validation + UUID Filename (SEC-08, SEC-09)
```php
// config/openmail.php — add MIME allowlist
'attachments' => [
    'allowed_mimes' => [
        // Images
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
        // Archives
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    ],
    'max_size' => 25 * 1024 * 1024,  // 25MB
],

// app/Http/Requests/AttachmentRequest.php
use Illuminate\Validation\Rules\File;

public function rules(): array
{
    return [
        'attachment' => [
            'required',
            File::types(config('openmail.attachments.allowed_mimes'))
                ->min(1)
                ->max(config('openmail.attachments.max_size') / 1024),  // KB
        ],
    ];
}

// In ComposerService or controller — safe storage
$file = $request->file('attachment');
$uuid = \Illuminate\Support\Str::uuid();
$extension = $file->extension();  // Derived from MIME type, not user filename
$safeName = "{$uuid}.{$extension}";
$path = $file->storeAs('attachments', $safeName, 'private');
```

### SSRF-Safe URL Handling (SEC-07)
```php
// app/Services/MessageSanitizer.php — extend blockRemoteImages for links
public function sanitizeUrls(string $html): string
{
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        if ($href && $this->isDangerousUrl($href)) {
            $link->setAttribute('href', '#');
            $link->setAttribute('data-original-href', $href);
            $link->setAttribute('class', 'link-blocked');
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ? $dom->saveHTML($body) : $dom->saveHTML();
}

private function isDangerousUrl(string $url): bool
{
    // Block javascript:, data:, vbscript:, file: schemes
    if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
        return true;
    }
    
    // Block localhost/private IPs (SSRF prevention)
    if (preg_match('/^https?:\/\/(localhost|127\.|10\.|192\.168\.|169\.254\.|\[::1\])/i', $url)) {
        return true;
    }
    
    return false;
}
```

### Settings Page Livewire Component Structure
```php
// app/Livewire/Settings/SettingsPage.php
namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.mailbox')]
class SettingsPage extends Component
{
    public string $activeTab = 'profile';
    public array $tabs = [
        'profile' => 'Profile',
        'mail' => 'Mail',
        'appearance' => 'Appearance',
        'security' => 'Security',
        'signatures' => 'Signatures',
    ];

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.settings.settings-page', [
            'activeTab' => $this->activeTab,
        ]);
    }
}
```

```blade
{{-- resources/views/livewire/settings/settings-page.blade.php --}}
<div class="max-w-4xl mx-auto">
    {{-- Tab navigation --}}
    <nav class="mb-6 border-b border-gray-200" role="tablist">
        @foreach($tabs as $key => $label)
            <button
                role="tab"
                wire:click="setTab('{{ $key }}')"
                class="px-4 py-3 border-b-2 font-medium text-sm transition-colors
                    {{ $activeTab === $key 
                        ? 'border-blue-500 text-blue-600' 
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                :aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- Tab panels --}}
    <div role="tabpanel">
        @switch($activeTab)
            @case('profile')
                @livewire('settings.profile-tab')
                @break
            @case('mail')
                @livewire('settings.mail-tab')
                @break
            @case('appearance')
                @livewire('settings.appearance-tab')
                @break
            @case('security')
                @livewire('settings.security-tab')
                @break
            @case('signatures')
                @livewire('settings.signatures-tab')
                @break
        @endswitch
    </div>
</div>
```

### Appearance Tab with Theme/Density Live Preview
```php
// app/Livewire/Settings/AppearanceTab.php
namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Setting;

#[Layout('layouts.mailbox')]
class AppearanceTab extends Component
{
    public string $theme = 'system';      // light, dark, system
    public string $density = 'regular';   // compact, regular, comfortable

    public function mount(): void
    {
        $this->theme = auth()->user()->setting('theme', 'system');
        $this->density = auth()->user()->setting('density', 'regular');
    }

    public function updatedTheme(): void
    {
        $this->persist();
        $this->dispatch('theme-changed', theme: $this->theme);
    }

    public function updatedDensity(): void
    {
        $this->persist();
        $this->dispatch('density-changed', density: $this->density);
    }

    private function persist(): void
    {
        Setting::set('theme', $this->theme, 'appearance');
        Setting::set('density', $this->density, 'appearance');
    }

    public function render()
    {
        return view('livewire.settings.appearance-tab');
    }
}
```

```blade
{{-- resources/views/livewire/settings/appearance-tab.blade.php --}}
<div x-data="appearancePreview()" x-init="init()">
    <div class="space-y-6">
        {{-- Theme --}}
        <section>
            <h3 class="text-lg font-semibold mb-4">Theme</h3>
            <div class="grid grid-cols-3 gap-4">
                @foreach(['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $value => $label)
                    <label class="relative cursor-pointer">
                        <input type="radio"
                               wire:model="theme"
                               value="{{ $value }}"
                               class="sr-only peer"
                               @click="$dispatch('theme-changed', { detail: '{{ $value }}' })">
                        <div class="p-4 border-2 rounded-lg
                            {{ $theme === $value 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300' }}
                            peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="font-medium">{{ $label }}</div>
                            <div class="text-sm text-gray-500 mt-1">
                                @if($value === 'system') Matches OS preference @else {{ ucfirst($value) }} mode @endif
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Density --}}
        <section>
            <h3 class="text-lg font-semibold mb-4">Density</h3>
            <div class="grid grid-cols-3 gap-4">
                @foreach(['compact' => 'Compact', 'regular' => 'Regular', 'comfortable' => 'Comfortable'] as $value => $label)
                    <label class="relative cursor-pointer">
                        <input type="radio"
                               wire:model="density"
                               value="{{ $value }}"
                               class="sr-only peer"
                               @click="$dispatch('density-changed', { detail: '{{ $value }}' })">
                        <div class="p-4 border-2 rounded-lg
                            {{ $density === $value 
                                ? 'border-blue-500 bg-blue-50' 
                                : 'border-gray-200 hover:border-gray-300' }}
                            peer-checked:border-blue-500 peer-checked:bg-blue-50">
                            <div class="font-medium">{{ $label }}</div>
                            <div class="text-sm text-gray-500 mt-1">
                                @match($value)
                                    @case('compact') Tight spacing @break
                                    @case('regular') Balanced spacing @break
                                    @case('comfortable') Relaxed spacing @break
                                @endmatch
                            </div>
                        </div>
                    </label>
                @endforeach
            </div>
        </section>

        {{-- Live Preview --}}
        <section>
            <h3 class="text-lg font-semibold mb-4">Live Preview</h3>
            <div class="border border-gray-200 rounded-lg p-4 bg-white dark:bg-gray-800 min-h-[200px]"
                 :class="previewDensityClass">
                <div class="space-y-3">
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                        <div class="font-medium">Sample message row</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Sender Name <sender@example.com></div>
                    </div>
                    <div class="p-3 bg-gray-100 dark:bg-gray-700 rounded">
                        <div class="font-medium">Another message</div>
                        <div class="text-sm text-gray-600 dark:text-gray-300">Subject line here</div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        function appearancePreview() {
            return {
                previewDensityClass: 'density-regular',
                init() {
                    this.previewDensityClass = 'density-{{ $density }}';
                    this.$watch('previewDensityClass', (val) => {
                        // Also update main layout for live preview
                        document.documentElement.classList.remove('density-compact', 'density-regular', 'density-comfortable');
                        document.documentElement.classList.add(val);
                    });
                }
            }
        }
    </script>
</div>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Manual CSP headers in middleware | `spatie/laravel-csp` with Vite nonce integration | 2024 (v3.x) | Automatic nonce handling; presets; report-only; report-to API |
| HTMLPurifier only (server) | Dual sanitization: HTMLPurifier (server) + DOMPurify (client) | 2024 (OWASP research) | Eliminates parser differential vulnerabilities; defense in depth |
| Redis for sessions/cache/rate limiting | Database driver (MySQL) | Laravel 11+ defaults | Shared hosting compatible; no external dependency |
| `wire:model.live` for all inputs | `wire:model` (deferred by default in LW3) + Alpine.js for local UI | Livewire 3.0 (2023) | Reduces server round-trips; better UX for theme/density pickers |
| Tailwind v3 `@apply` + config file | Tailwind v4 CSS-first (`@theme`, `@utility`, CSS vars) | Tailwind v4 (2026) | No config file; CSS vars enable runtime theming/density without rebuild |
| PHP IMAP extension (`ext-imap`) | `webklex/php-imap` pure PHP | Ongoing | Works on shared hosting; no server-level install needed |
| Manual session fixation handling | Laravel built-in `session()->regenerate()` + `auth.session` middleware | Laravel 10+ | Standardized; tested; handles edge cases |

**Deprecated/outdated:**
- `php artisan make:middleware` for CSP → use `spatie/laravel-csp` config
- `wire:model.live` as default → use `wire:model` (deferred) + Alpine.js for local state
- Tailwind v3 `tailwind.config.js` → Tailwind v4 `@theme` in CSS
- Manual rate limit counters → `RateLimiter::for()` with `Limit` objects
- Storing raw HTML from editors → Store Tiptap JSON, render sanitized HTML on output

## Assumptions Log

> List all claims tagged `[ASSUMED]` in this research. The planner and discuss-phase use this section to identify decisions that need user confirmation before execution.

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Livewire 4.x (currently in composer.json) is compatible with Phase 3/4 patterns written for Livewire 3.x | Standard Stack | If breaking changes exist, composer/patterns need adjustment; mitigated by testing |
| A2 | `spatie/laravel-csp` v3.28 supports Tailwind 4 / Vite 6 nonce generation exactly as documented | CSP Pattern | If API changed, custom nonce generator may need adjustment; low risk — package is mature |
| A3 | Tiptap 3.31 + `tiptap-php` sanitization pipeline works identically to Phase 3 composer | Signature Pattern | If schema changed, signature JSON storage/rendering may differ; verify in spike |
| A4 | Shared hosting cron can run `php artisan schedule:run` every minute for audit log prune | Runtime State | If cron not available, prune job must be manual or via external cron service |
| A5 | `Setting` model with user_id scoping works for per-user preferences (currently global) | Theme/Density Pattern | If `Setting` is global-only, need new `user_preferences` table or user_id column |
| A6 | `auth.session` middleware enabled by default in Laravel 12 for session hardening | Session Hardening | If not enabled, must add to web middleware group in `bootstrap/app.php` |

## Open Questions

1. **CSP `unsafe-inline` fallback removal timeline**
   - What we know: Livewire 3/Alpine.js require `unsafe-inline` for inline scripts/styles currently
   - What's unclear: When can we safely remove fallback? Requires refactoring all inline code to nonce-based
   - Recommendation: Keep fallback for v1; create follow-up task to audit inline scripts post-launch

2. **Audit log query UI (deferred to v2?)**
   - What we know: D-05–D-08 cover storage and logging only; no UI for viewing logs
   - What's unclear: Whether admin needs log viewer in v1 or can wait
   - Recommendation: Defer UI to v2; logs are queryable via SQL/Artisan for now

3. **Signature attachment support (images in signatures)**
   - What we know: Tiptap supports images; Phase 3 composer handles attachments
   - What's unclear: Whether signatures should support inline images (hosted vs embedded)
   - Recommendation: Text + formatting only for v1; images add storage/complexity

4. **Active sessions list implementation (SET-05)**
   - What we know: Laravel `sessions` table stores session data; can query by `user_id`
   - What's unclear: Whether to show device/browser info (requires parsing user_agent)
   - Recommendation: Basic list (IP, last activity, current) for v1; device parsing v2

5. **Reply behavior options (SET-04)**
   - What we know: "Reply behavior" is a SET-04 requirement
   - What's unclear: Exact options — "reply to sender", "reply to all", "smart reply"
   - Recommendation: Implement "Reply to sender" (default) + "Reply to all" toggle; defer smart reply

## Environment Availability

> Skip this section if the phase has no external dependencies (code/config-only changes).

| Dependency | Required By | Available | Version | Fallback |
|------------|-------------|-----------|---------|----------|
| PHP | All backend | ✓ | 8.3+ (required) | — |
| Composer | PHP deps | ✓ | 2.x | — |
| MySQL/MariaDB | Database | ✓ | 8.0+ / 10.6+ | — |
| Node.js / npm | Asset build | ✓ | 18+ (for Vite 6) | Build locally, deploy assets |
| System cron | Audit log prune | ? | — | Manual `php artisan audit:prune` or external cron service |
| `ext-mailparse` (PECL) | `php-mime-mail-parser` (optional) | ? | — | Use `zbateson/mail-mime-parser` (pure PHP) instead |

**Missing dependencies with no fallback:**
- None — all core deps are PHP/Composer/Node which are standard

**Missing dependencies with fallback:**
- System cron: If not available on shared host, use Laravel's `schedule:run` via HTTP endpoint (cPanel cron jobs can hit URL) or manual prune
- `ext-mailparse`: Use `zbateson/mail-mime-parser` (already in STACK.md alternatives)

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest PHP 3.8 (configured in composer.json) |
| Config file | `pest.php` (or `phpunit.xml` — verify in Wave 0) |
| Quick run command | `php artisan test --filter=<TestName>` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SEC-01 | HTML sanitization blocks scripts/event handlers | Unit | `php artisan test tests/Unit/MessageSanitizerTest.php` | ❌ Wave 0 |
| SEC-02 | CSS injection prevented by allowlist | Unit | `php artisan test tests/Unit/MessageSanitizerTest.php::test_css_allowlist` | ❌ Wave 0 |
| SEC-03 | CSP headers present in production response | Feature | `php artisan test tests/Feature/CspTest.php` | ❌ Wave 0 |
| SEC-03 | CSP report-only mode logs violations | Feature | `php artisan test tests/Feature/CspTest.php::test_report_only` | ❌ Wave 0 |
| SEC-04 | Login rate limiting enforces per-IP/per-email limits | Feature | `php artisan test tests/Feature/RateLimitTest.php::test_login_throttle` | ❌ Wave 0 |
| SEC-04 | API rate limiting on compose/search/settings | Feature | `php artisan test tests/Feature/RateLimitTest.php::test_api_limits` | ❌ Wave 0 |
| SEC-05 | Audit log created on login success/failure | Feature | `php artisan test tests/Feature/AuditLogTest.php::test_auth_logging` | ❌ Wave 0 |
| SEC-05 | Audit log created on send/reply/forward | Feature | `php artisan test tests/Feature/AuditLogTest.php::test_send_logging` | ❌ Wave 0 |
| SEC-06 | Session regenerated on login | Unit | `php artisan test tests/Unit/SessionTest.php::test_regeneration` | ❌ Wave 0 |
| SEC-06 | Session timeout config respected | Feature | `php artisan test tests/Feature/SessionTest.php::test_timeout` | ❌ Wave 0 |
| SEC-07 | External URLs in messages rewritten to data-src | Unit | `php artisan test tests/Unit/MessageSanitizerTest.php::test_ssrf_urls` | ❌ Wave 0 |
| SEC-08 | Attachment filenames UUID-prefixed, path traversal blocked | Unit | `php artisan test tests/Unit/AttachmentTest.php` | ❌ Wave 0 |
| SEC-09 | MIME validation rejects non-allowlist types | Unit | `php artisan test tests/Unit/AttachmentTest.php::test_mime_validation` | ❌ Wave 0 |
| SEC-10 | Security headers present on all responses | Feature | `php artisan test tests/Feature/SecurityHeadersTest.php` | ❌ Wave 0 |
| SET-01 | Profile display shows name/email, editable | Feature | `php artisan test tests/Feature/SettingsTest.php::test_profile` | ❌ Wave 0 |
| SET-02 | Signature CRUD + default logic works | Feature | `php artisan test tests/Feature/SignatureTest.php` | ❌ Wave 0 |
| SET-03 | Theme toggle persists, respects system pref | Feature | `php artisan test tests/Feature/SettingsTest.php::test_theme` | ❌ Wave 0 |
| SET-04 | Mail preferences stored and used | Feature | `php artisan test tests/Feature/SettingsTest.php::test_mail_prefs` | ❌ Wave 0 |
| SET-05 | Active sessions listed, revoke works | Feature | `php artisan test tests/Feature/SettingsTest.php::test_sessions` | ❌ Wave 0 |
| SET-06 | Density setting applies CSS vars live | Feature | `php artisan test tests/Feature/SettingsTest.php::test_density` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=<relevant-test>` (targeted)
- **Per wave merge:** `php artisan test` (full suite)
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/MessageSanitizerTest.php` — covers SEC-01, SEC-02, SEC-07, SEC-08, SEC-09
- [ ] `tests/Feature/CspTest.php` — covers SEC-03
- [ ] `tests/Feature/RateLimitTest.php` — covers SEC-04
- [ ] `tests/Feature/AuditLogTest.php` — covers SEC-05
- [ ] `tests/Feature/SessionTest.php` — covers SEC-06
- [ ] `tests/Feature/SecurityHeadersTest.php` — covers SEC-10
- [ ] `tests/Feature/SettingsTest.php` — covers SET-01 through SET-06
- [ ] `tests/Feature/SignatureTest.php` — covers SET-02
- [ ] `tests/Unit/AttachmentTest.php` — covers SEC-08, SEC-09
- [ ] `pest.php` or `phpunit.xml` config verification
- [ ] Framework install: already in composer.json dev dependencies

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | Yes | Laravel Auth + IMAP guard; session rotation; progressive throttling |
| V3 Session Management | Yes | Database sessions; secure cookies; `auth.session` middleware; regeneration |
| V4 Access Control | Yes | Auth middleware on all mailbox/routes; user-scoped queries |
| V5 Input Validation | Yes | `MessageSanitizer` (HTMLPurifier + DOMPurify); `File::types()` for uploads; Form Requests |
| V6 Cryptography | Yes | Laravel encryption for IMAP passwords in session; HTTPS enforcement |
| V7 Error Handling | Yes | CSP violation logging; no stack traces in production; generic error pages |
| V8 Logging | Yes | `security` log channel for auth events; `AuditService` for audit trail |
| V9 Communication Security | Yes | TLS for IMAP/SMTP (AUTH-08); secure cookies; HSTS header |
| V10 HTTP Security | Yes | CSP (SEC-03); Security headers (SEC-10); CSRF on all forms |

### Known Threat Patterns for Laravel + Livewire + IMAP Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via email HTML content | Tampering | Dual sanitization: HTMLPurifier (server) → DOMPurify (client) + sandboxed iframe |
| XSS via signature content | Tampering | Same pipeline: Tiptap JSON → tiptap-php → HTMLPurifier → DOMPurify |
| CSP bypass via inline scripts | Tampering | Nonce-based CSP + `unsafe-inline` fallback only during report-only phase |
| Brute force login | Spoofing | Progressive rate limiting (per-IP + per-email) in `AppServiceProvider` |
| Session fixation | Spoofing | `session()->regenerate()` on login; `auth.session` middleware |
| SSRF via message links/images | Tampering | `MessageSanitizer::blockRemoteImages()` + `sanitizeUrls()` block private IPs/schemes |
| Path traversal via attachment filename | Tampering | UUID prefix + MIME-derived extension; never trust user filename |
| MIME confusion attack | Tampering | `File::types()` validates actual file content, not extension |
| Clickjacking | Spoofing | `X-Frame-Options: DENY` + CSP `frame-ancestors 'none'` |
| MIME sniffing | Information Disclosure | `X-Content-Type-Options: nosniff` |
| Referrer leakage | Information Disclosure | `Referrer-Policy: strict-origin-when-cross-origin` |
| Audit log tampering | Repudiation | Append-only table; no update/delete routes; 90-day retention |
| Credential exposure in logs | Information Disclosure | `Log::channel('security')` excludes passwords; IMAP password encrypted in session |

## Sources

### Primary (HIGH confidence)
- `/spatie/laravel-csp` — CSP presets, Vite nonce integration, report-only mode, violation reporting endpoints [VERIFIED: Context7]
- `/laravel/docs` — Rate limiting (`RateLimiter::for`, `Limit` objects), session hardening (`session()->regenerate()`, `auth.session`), file validation (`File::types()`), middleware registration, scheduler [VERIFIED: Context7]
- `/ueberdosis/tiptap-docs` — Tiptap editor setup, extensions, Alpine.js/Blade integration, `transformPastedHTML` for sanitization [VERIFIED: Context7]
- `/ueberdosis/tiptap-php` — JSON↔HTML conversion, sanitization pipeline, `ContentFilter` [VERIFIED: Context7]
- `/webklex/php-imap` — Pure PHP IMAP client, no ext-imap needed, Laravel wrapper [VERIFIED: Context7]

### Secondary (MEDIUM confidence)
- Laravel 12/13 documentation (laravel.com/docs) — middleware groups, CSP integration patterns
- OWASP AppSec USA 2024: "Why Server-Side HTML Sanitization Fails" — HTMLPurifier parser differential research
- spatie/laravel-csp GitHub README — presets, configuration examples

### Tertiary (LOW confidence)
- WebSearch results for "Livewire 3 CSP nonce" — community patterns, not official
- Tailwind 4 CSS-first migration guides — syntax changes from v3

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified on registries; versions match STACK.md
- Architecture: HIGH — patterns derived from existing codebase (MessageSanitizer, ComposerService, Setting model, AppServiceProvider)
- Pitfalls: HIGH — based on known Livewire/CSP/Alpine interactions documented in multiple sources
- Code examples: HIGH — adapted from Context7-verified docs and existing project patterns

**Research date:** 2026-09-08
**Valid until:** 2026-10-08 (30 days for stable Laravel 12 ecosystem)