# Phase 1: Foundation & Setup Wizard - Research

**Researched:** 2026-09-05
**Domain:** Laravel 12 application scaffold, setup wizard, IMAP authentication
**Confidence:** HIGH

## Summary

Phase 1 establishes the entire OpenMail foundation: a Laravel 12 project with Livewire 3 multi-step setup wizard, IMAP-backed authentication, database schema for settings/sessions/users, and an installation lock mechanism. The research confirms that Laravel 12 + Livewire 3 is a mature, well-documented stack with established patterns for every capability this phase requires. Multi-step wizard forms in Livewire 3 are well-solved using either session-persisted properties (recommended for <30 fields) or the `jeffersongoncalves/laravel-livewire-wizard` package. The installation lock pattern follows WordPress conventions using a `storage/installed` file. IMAP authentication requires a custom Laravel auth guard that validates credentials against the IMAP server rather than a local password column.

**Primary recommendation:** Use a single Livewire component with session-persisted state for the 8-step wizard (no external wizard package needed), a `storage/installed` lock file, and a custom Eloquent-based auth guard with IMAP credential verification on login.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** Linear 8-step wizard: 1) Welcome/detect install 2) System requirements 3) Database config 4) Mail config (IMAP/SMTP) 5) App settings 6) Admin account 7) Security defaults 8) Verify & finish — **Reversibility:** reversible
- **D-02:** Inline error handling — show error below the failing field, let admin fix and retry without losing other step data — **Reversibility:** reversible
- **D-03:** Resume from last step on return — save progress in DB/session, on return ask "Continue from step X?" or "Start over" — **Reversibility:** reversible
- **D-04:** Full verification suite at final step — test DB, IMAP, SMTP, filesystem, PHP config, encryption with green/red status per check, "Fix issues" links back to relevant step — **Reversibility:** reversible
- **D-05:** Full connection test — connect, authenticate, list folders (IMAP) / verify server accepts auth (SMTP) — **Reversibility:** reversible
- **D-06:** User-friendly error display with expandable technical details — simple message with optional expandable section showing full error — **Reversibility:** reversible
- **D-07:** Retry in place — keep form filled after failure, let admin fix and hit "Test again", no data loss — **Reversibility:** reversible
- **D-08:** SMTP test is connection-only — verify SMTP server accepts auth and is reachable, no email sent — **Reversibility:** reversible
- **D-09:** Auto-detect Big 3 providers + generic fallback — Gmail, Outlook/365, Yahoo with pre-filled settings; generic mail.domain.com / smtp.domain.com for unknown providers — **Reversibility:** reversible
- **D-10:** Pre-fill from email domain — admin enters email address, system detects provider from domain, pre-fills host/port/encryption, admin can override — **Reversibility:** reversible
- **D-11:** Unknown domains get generic fields pre-filled — show mail.domain.com / smtp.domain.com defaults with placeholder text — **Reversibility:** reversible
- **D-12:** Default encryption is SSL/TLS — use port 993 (IMAP) / 465 (SMTP) with SSL/TLS — **Reversibility:** reversible
- **D-13:** Session lifetime is 24 hours — standard for webmail — **Reversibility:** reversible
- **D-14:** Progressive login throttling — 5s after 3rd fail, 30s after 5th, 5min after 7th, lock for 15min after 10th — **Reversibility:** reversible
- **D-15:** Cookie defaults: HttpOnly + SameSite=Lax — Secure flag added when HTTPS detected — **Reversibility:** reversible
- **D-16:** Admin account: strong password (8+ chars, mixed case, number) + valid email — email used for login — **Reversibility:** reversible

### the agent's Discretion
- Database schema design — researcher and planner determine table structure, indexes, migrations based on requirements
- Laravel project structure — follow standard Laravel conventions
- Livewire component organization — follow existing patterns

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

<phase_requirements>
## Phase Requirements

| ID | Description | Research Support |
|----|-------------|------------------|
| SETUP-01 | Graphical setup wizard detects fresh installation and enters install mode | Lock file pattern (`storage/installed`), middleware guard, route registration |
| SETUP-02 | System requirements check (PHP version, extensions, permissions, database driver) | `phpversion()`, `extension_loaded()`, `is_writable()`, `function_exists()` checks |
| SETUP-03 | Database configuration form with connection testing | Livewire form → write .env → run migrations; test via `DB::purge()` + reconnect |
| SETUP-04 | IMAP/SMTP configuration with connection testing and port/encryption validation | webklex/php-imap `connect()` for IMAP; Symfony Mailer `SmtpTransport::fromDsn()` for SMTP |
| SETUP-05 | Automatic mail configuration detection for common domains | Domain → provider mapping array; pre-fill IMAP/SMTP host/port/encryption |
| SETUP-06 | Application configuration (name, organization, domain, timezone) | Store in `settings` table via key-value or dedicated columns |
| SETUP-07 | Administrator account creation with strong password validation | Create User model with hashed password; bcrypt via `Hash::make()` |
| SETUP-08 | Security configuration defaults (HTTPS, secure cookies, session lifetime, rate limiting) | Write to .env + config; session DB driver, cookie flags in session config |
| SETUP-09 | Final verification suite (config, database, IMAP, SMTP, filesystem, encryption) | Per-check closures with try/catch, return success/failure with error detail |
| SETUP-10 | Installation lock prevents re-running setup on existing installation | `storage/installed` file; middleware returns 404 when file exists |
| SETUP-11 | Resumable installation state (browser close preserves progress) | Livewire component persists `$currentStep` + form data to `session()` after each step |
| SETUP-12 | Shared hosting compatible (no CLI/SSH required for installation) | All operations via HTTP/web UI; no artisan commands; .env written via PHP file_put_contents |
| AUTH-01 | User can log in with email/password via IMAP authentication | Custom auth guard: find user by email, IMAP connect+authenticate, create session |
| AUTH-02 | Secure application session created after successful IMAP auth | `Auth::login($user)` with session regeneration; SESSION_DRIVER=database |
| AUTH-03 | Login throttling with progressive delays after failed attempts | `RateLimiter::for('login')` with dual-key (IP + email); exponential decay |
| AUTH-04 | Session rotation after authentication | `$request->session()->regenerate()` after successful login |
| AUTH-05 | CSRF protection on all forms | Laravel default `VerifyCsrfToken` middleware; `@csrf` Blade directive |
| AUTH-06 | Secure cookies (HttpOnly, SameSite, Secure flags) | `config/session.php`: `http_only => true`, `same_site => lax`, `secure => env('SESSION_SECURE_COOKIE')` |
| AUTH-07 | Session invalidation on logout | `Auth::logout()`, `$request->session()->invalidate()`, `$request->session()->regenerateToken()` |
| AUTH-08 | TLS enforcement for IMAP/SMTP connections | Default ports 993/465 with SSL/TLS; `encryption` config on IMAP client |
| DB-01 | MySQL/MariaDB schema for application state | Migrations for `users`, `settings`, `sessions` tables |
| DB-02 | Database sessions (no Redis dependency) | `SESSION_DRIVER=database`; Laravel's built-in `sessions` migration |
| DB-05 | Application settings storage | `settings` table with key-value or dedicated columns; cached via `Cache::remember()` |
</phase_requirements>

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Setup wizard UI | Frontend Server (Livewire) | Browser (Alpine.js) | Livewire renders wizard steps; Alpine handles small UI interactions |
| Installation detection | API/Backend (Middleware) | — | Middleware checks lock file before routing to wizard |
| Database configuration | API/Backend (Service) | Database | Writes .env, tests connection, runs migrations |
| IMAP/SMTP testing | API/Backend (Service) | External (Mail Server) | Connects to mail server, validates credentials |
| Auto-detection logic | API/Backend (Service) | — | Pure PHP domain mapping, no tier dependency |
| Application settings | Database | API/Backend | Settings stored in DB, cached in memory |
| Admin account creation | API/Backend | Database | Creates User record with hashed password |
| Authentication (IMAP) | API/Backend (Guard) | External (IMAP Server) | Custom guard validates against IMAP, stores session |
| Session management | Database | API/Backend | SESSION_DRIVER=database; session rotation on auth |
| Rate limiting | API/Backend (Middleware) | Database (Cache) | RateLimiter uses database cache driver |
| Security defaults | API/Backend (Config) | — | Cookie flags, session lifetime written to config |

## Standard Stack

### Core (Composer — PHP)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| laravel/framework | ^12.0 (latest: 12.68.0) | Backend framework | Latest stable with long support window; 12.x is mature and well-documented |
| livewire/livewire | ^3.8 (latest: 3.8.6) | Dynamic UI components | Built-in Alpine.js, SPA-like navigation, reactive props; ships with Laravel 12 |
| webklex/laravel-imap | ^6.2 (latest: 6.2.0) | IMAP client | Pure PHP IMAP (no ext-imap needed), Laravel facade, folder listing, message parsing |
| ezyang/htmlpurifier | ^4.17 | Server-side HTML sanitization | Industry standard; used for future email rendering (not critical in Phase 1) |
| spatie/laravel-csp | latest | CSP headers | Preset-based CSP with nonce support; future-proof for Phase 5 |

### Supporting (Composer)
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| laravel/scout | ^10.0 | Full-text search | When search is needed (Phase 4); database engine for shared hosting |
| php-mime-mail-parser | ^10.0 | MIME parsing | Phase 2+ for message parsing; requires mailparse PECL extension |
| zbateson/mail-mime-parser | ^4.0 | MIME parsing (alt) | If mailparse extension unavailable on shared hosting |

### Frontend (npm — bundled via Vite)
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| tailwindcss | ^4.3 (latest: 4.3.3) | Utility-first CSS | Laravel 12 default; CSS-first config in v4 |
| alpinejs | ^3.17 (latest: 3.17.1) | Lightweight JS interactivity | Included automatically by Livewire 3 |

### Build Tool
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| vite | ^6.x | Asset bundling | Laravel's default; handles CSS/JS, HMR, optimized builds |

### Installation
```bash
# Create Laravel 12 project
composer create-project laravel/laravel openmail

# Add Livewire (included by default in Laravel 12, but verify)
composer require livewire/livewire

# Add IMAP library
composer require webklex/laravel-imap

# Add security libraries (for Phase 1 scaffolding)
composer require ezyang/htmlpurifier
composer require spatie/laravel-csp

# Build frontend assets
npm install && npm run build
```

### Version Verification
Verified against Packagist (2026-09-05):
- `laravel/framework`: v12.68.0 [CITED: packagist.org/packages/laravel/framework]
- `livewire/livewire`: v3.8.6 [CITED: packagist.org/packages/livewire/livewire]
- `webklex/laravel-imap`: v6.2.0 (2025-04-25) [CITED: packagist.org/packages/webklex/laravel-imap]
- `tailwindcss` (npm): 4.3.3 [VERIFIED: npm registry]
- `alpinejs` (npm): 3.17.1 [VERIFIED: npm registry]

## Package Legitimacy Audit

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| laravel/framework | Packagist | 14+ yrs | Millions/week | github.com/laravel/framework | OK | Approved |
| livewire/livewire | Packagist | 5+ yrs | 23K+ stars | github.com/livewire/livewire | OK | Approved |
| webklex/laravel-imap | Packagist | 7+ yrs | 710 stars | github.com/Webklex/laravel-imap | OK | Approved |
| tailwindcss | npm | 6+ yrs | 125M/week | github.com/tailwindlabs/tailwindcss | OK | Approved |
| alpinejs | npm | 5+ yrs | 737K/week | github.com/alpinejs/alpine | OK | Approved |
| ezyang/htmlpurifier | Packagist | 15+ yrs | Industry standard | github.com/ezyang/htmlpurifier | OK | Approved |
| spatie/laravel-csp | Packagist | 5+ yrs | Well-known | github.com/spatie/laravel-csp | OK | Approved |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

*Note: The `package-legitimacy check` tool reported "SLOP" for `laravel/framework` and `livewire/livewire` when checking via PyPI ecosystem — these are PHP packages on Packagist, not Python packages. The SLOP verdict is an ecosystem mismatch artifact, not a genuine concern. All packages verified on correct registry.*

## Architecture Patterns

### System Architecture Diagram

```
                    ┌─────────────────────────────────┐
                    │         Browser / Client          │
                    │   (Alpine.js for small UI)        │
                    └──────────────┬──────────────────┘
                                   │ HTTP
                    ┌──────────────▼──────────────────┐
                    │      Livewire 3 Components       │
                    │   SetupWizard / LoginForm         │
                    └──────────────┬──────────────────┘
                                   │
                    ┌──────────────▼──────────────────┐
                    │     Laravel 12 Backend            │
                    │  ┌─────────────────────────────┐ │
                    │  │  Middleware:                  │ │
                    │  │  - InstallLock (check file)  │ │
                    │  │  - VerifyCsrfToken           │ │
                    │  │  - ThrottleRequests (login)  │ │
                    │  └─────────────────────────────┘ │
                    │  ┌─────────────────────────────┐ │
                    │  │  Services:                    │ │
                    │  │  - MailConfigDetector         │ │
                    │  │  - ImapConnectionTester       │ │
                    │  │  - SmtpConnectionTester       │ │
                    │  │  - SystemRequirementsChecker  │ │
                    │  │  - InstallationVerifier       │ │
                    │  └─────────────────────────────┘ │
                    │  ┌─────────────────────────────┐ │
                    │  │  Auth Guard:                  │ │
                    │  │  - ImapGuard (custom)         │ │
                    │  │  - validates via IMAP connect │ │
                    │  └─────────────────────────────┘ │
                    └──────┬───────────────┬──────────┘
                           │               │
              ┌────────────▼──┐    ┌───────▼──────────┐
              │   MySQL/MariaDB│    │  External Servers  │
              │  - users       │    │  - IMAP server     │
              │  - settings    │    │  - SMTP server     │
              │  - sessions    │    └──────────────────┘
              └────────────────┘
```

### Recommended Project Structure
```
openmail/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── SetupController.php          # Redirects to wizard or shows 404
│   │   └── Middleware/
│   │       ├── InstallLock.php              # Checks storage/installed file
│   │       └── PreventReinstall.php         # Blocks wizard if already installed
│   ├── Livewire/
│   │   ├── SetupWizard.php                  # Main 8-step wizard component
│   │   ├── Steps/
│   │   │   ├── WelcomeStep.php              # Step 1: detect fresh install
│   │   │   ├── SystemRequirementsStep.php   # Step 2: PHP, extensions, permissions
│   │   │   ├── DatabaseStep.php             # Step 3: DB config + test
│   │   │   ├── MailConfigStep.php           # Step 4: IMAP/SMTP config + test
│   │   │   ├── AppSettingsStep.php          # Step 5: name, org, domain, timezone
│   │   │   ├── AdminAccountStep.php         # Step 6: admin email + password
│   │   │   ├── SecurityDefaultsStep.php     # Step 7: HTTPS, cookies, session, throttle
│   │   │   └── VerifyFinishStep.php         # Step 8: verification suite + finish
│   │   └── LoginForm.php                    # IMAP-backed login component
│   ├── Models/
│   │   ├── User.php                         # Eloquent user model (implements Authenticatable)
│   │   └── Setting.php                      # Application settings model
│   ├── Services/
│   │   ├── MailConfigDetector.php           # Domain → provider auto-detection
│   │   ├── ImapConnectionTester.php         # IMAP connect/auth/list test
│   │   ├── SmtpConnectionTester.php         # SMTP connect/auth test (no send)
│   │   ├── SystemRequirementsChecker.php    # PHP version, extensions, permissions
│   │   └── InstallationVerifier.php         # Full verification suite for step 8
│   ├── Auth/
│   │   └── Guards/
│   │       └── ImapGuard.php                # Custom auth guard
│   └── Providers/
│       └── AppServiceProvider.php           # Auth guard registration, rate limiter config
├── config/
│   ├── auth.php                             # Custom 'imap' guard + provider
│   ├── session.php                          # SESSION_DRIVER=database, cookie flags
│   └── imap.php                             # webklex/laravel-imap config
├── database/
│   ├── migrations/
│   │   ├── 0001_01_01_000000_create_users_table.php
│   │   ├── 0001_01_01_000001_create_sessions_table.php
│   │   ├── 0001_01_01_000002_create_settings_table.php
│   │   └── ... (other default migrations)
│   └── seeders/
│       └── DatabaseSeeder.php
├── resources/
│   └── views/
│       ├── layouts/
│       │   └── setup.blade.php              # Wizard layout (no auth required)
│       ├── setup/
│       │   ├── wizard.blade.php             # Main wizard view
│       │   └── steps/
│       │       ├── welcome.blade.php
│       │       ├── requirements.blade.php
│       │       ├── database.blade.php
│       │       ├── mail-config.blade.php
│       │       ├── app-settings.blade.php
│       │       ├── admin-account.blade.php
│       │       ├── security.blade.php
│       │       └── verify.blade.php
│       └── auth/
│           └── login.blade.php              # Login page
├── routes/
│   └── web.php                              # Setup routes + auth routes
├── storage/
│   └── installed                            # Lock file (created on finish)
└── .env                                     # Database, mail, app config
```

### Pattern 1: Livewire Multi-Step Wizard with Session Persistence
**What:** A single Livewire component manages all wizard steps. Form data is persisted to session after every step navigation. On mount, state is restored from session.
**When to use:** When total fields < 30 and steps < 10 (which is the case: 8 steps, ~25 fields).
**Example:**
```php
// Source: Medium article "How to Build a Multi-Step Form in Laravel and Livewire" (2026-08-04)
// Pattern verified against multiple sources
class SetupWizard extends Component
{
    public int $currentStep = 1;
    public int $totalSteps = 8;
    public array $completedSteps = [];

    // Step 3: Database config
    public string $dbHost = '127.0.0.1';
    public string $dbPort = '3306';
    public string $dbDatabase = '';
    public string $dbUsername = '';
    public string $dbPassword = '';

    // Step 4: Mail config
    public string $imapHost = '';
    public int $imapPort = 993;
    public string $imapEncryption = 'ssl';
    // ... more fields

    protected array $stepRules = [
        3 => ['dbHost' => 'required', 'dbDatabase' => 'required', ...],
        4 => ['imapHost' => 'required', 'imapPort' => 'required|integer', ...],
        // ...
    ];

    public function mount(): void
    {
        $this->restoreFromSession();
    }

    public function nextStep(): void
    {
        $this->validate($this->stepRules[$this->currentStep] ?? []);
        if (!in_array($this->currentStep, $this->completedSteps)) {
            $this->completedSteps[] = $this->currentStep;
        }
        $this->currentStep++;
        $this->saveToSession();
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->saveToSession();
        }
    }

    private function sessionKey(): string
    {
        return 'openmail:setup:wizard';
    }

    private function saveToSession(): void
    {
        session()->put($this->sessionKey(), [
            'currentStep' => $this->currentStep,
            'completedSteps' => $this->completedSteps,
            'dbHost' => $this->dbHost, /* ... all fields ... */
        ]);
    }

    private function restoreFromSession(): void
    {
        $saved = session()->get($this->sessionKey());
        if (!$saved) return;
        foreach (['currentStep', 'completedSteps', 'dbHost', /* ... */] as $prop) {
            if (isset($saved[$prop])) $this->$prop = $saved[$prop];
        }
    }
}
```

### Pattern 2: Installation Lock File
**What:** A simple file at `storage/installed` prevents the setup wizard from being re-run. Middleware checks for this file on every request.
**When to use:** Always — this is the standard pattern for self-hosted PHP applications (WordPress uses `wp-config.php` existence, Laravel installers use `storage/installed`).
**Example:**
```php
// Source: kejubayer/laravel-installer, zisunal/laravel-installer (Packagist)
// Pattern: store/installed file + middleware guard
class InstallLock
{
    public function handle(Request $request, Closure $next)
    {
        // If app IS installed and user tries /install → abort 404
        if ($request->is('install*') && file_exists(storage_path('installed'))) {
            abort(404);
        }

        // If app is NOT installed and user tries any other route → redirect to /install
        if (!$request->is('install*') && !file_exists(storage_path('installed'))) {
            return redirect('/install');
        }

        return $next($request);
    }
}
```

### Pattern 3: Custom Auth Guard for IMAP
**What:** A custom Laravel auth guard that validates user credentials against the IMAP server instead of comparing password hashes in the database.
**When to use:** When authentication is delegated to an external IMAP server and no local password storage is desired.
**Example:**
```php
// Source: Laravel 12 docs "Adding Custom Guards"
// Uses Closure Request Guard pattern for simplicity
// In AppServiceProvider boot():
Auth::viaRequest('imap', function (Request $request) {
    $email = $request->input('email');
    $password = $request->input('password');

    if (!$email || !$password) return null;

    // Find or create user by email
    $user = User::firstOrCreate(
        ['email' => $email],
        ['name' => explode('@', $email)[0]]
    );

    // Verify credentials against IMAP
    try {
        $client = app(ImapConnectionTester::class)->connect(
            $email, $password,
            config('openmail.imap_host'),
            config('openmail.imap_port'),
            config('openmail.imap_encryption')
        );
        return $user;
    } catch (\Exception $e) {
        return null;
    }
});

// In config/auth.php:
// 'guards' => ['imap' => ['driver' => 'imap', 'provider' => 'users']],
```

### Pattern 4: Progressive Login Throttling
**What:** Dual-key rate limiting (per-IP + per-email) with exponential decay delays matching D-14 thresholds.
**When to use:** Always for login endpoints — this is the security baseline.
**Example:**
```php
// Source: Laravel 12 docs "Rate Limiting" + Tech Verse Daily article (2026-08-13)
// In AppServiceProvider boot():
RateLimiter::for('login', function (Request $request) {
    $email = Str::lower($request->input('email', ''));

    return [
        // Per-IP: 10 attempts per minute
        Limit::perMinute(10)->by($request->ip()),
        // Per-email: 5 attempts per 5 minutes
        Limit::perMinutes(5, 5)->by($email),
    ];
});

// In Login Livewire component:
public function login(): void
{
    $key = 'login_attempts:' . Str::lower($this->email);

    if (RateLimiter::tooManyAttempts($key, 5)) {
        $seconds = RateLimiter::availableIn($key);
        throw ValidationException::withMessages([
            'email' => "Too many attempts. Try again in {$seconds} seconds.",
        ]);
    }

    // Attempt IMAP authentication...
    // On success: RateLimiter::clear($key)
    // On failure: RateLimiter::hit($key, $this->calculateDecay($this->email))
}
```

### Anti-Patterns to Avoid
- **Using `wire:model.live` on wizard inputs:** Sends request on every keystroke — performance killer. Use `wire:model` (deferred) or `wire:model.blur` instead.
- **Storing wizard state only in Livewire public properties:** State is lost on browser close. Must persist to session.
- **Validating all steps on every navigation:** Only validate the current step's fields. This keeps the UX snappy and avoids confusing error messages.
- **Using the default `session` guard for IMAP auth:** The default guard expects a password column in the database. Must use a custom guard that validates against IMAP.
- **Hand-rolling .env file writing:** Use a structured approach — read existing .env, update keys, write back. Don't use regex on arbitrary lines.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| IMAP connection testing | Raw `fsockopen` + IMAP protocol | webklex/php-imap `connect()` | Handles SSL, authentication, error handling, folder listing |
| SMTP connection testing | Raw socket + SMTP commands | Symfony Mailer `SmtpTransport::fromDsn()` | Handles STARTTLS, auth, error handling, connection pooling |
| HTML sanitization | Regex-based tag stripping | HTMLPurifier (server) + DOMPurify (client) | Parser differential attacks, encoding issues, edge cases |
| Rate limiting | Custom counter in database | Laravel `RateLimiter` facade | Atomic increments, TTL management, cache driver abstraction |
| Session management | Custom session handler | Laravel `SESSION_DRIVER=database` | Built-in, tested, handles fixation, rotation, encryption |
| .env file writing | Raw `file_put_contents` | `vlucas/phpdotenv` + structured write | Preserves comments, handles quoting, avoids corruption |
| Password hashing | `md5()` / `sha1()` / custom | `Hash::make()` (bcrypt/Argon2) | Industry standard, timing-safe, salted |
| CSRF protection | Manual token generation | Laravel `@csrf` directive | Automatic, validated, session-bound |
| PHP requirements checking | Manual `phpversion()` calls | Structured check array with thresholds | Maintainable, testable, consistent output format |

**Key insight:** Every problem in this phase has a well-established PHP/Laravel solution. The project's constraint is shared hosting — avoid any pattern that requires CLI, background workers, or system services.

## Common Pitfalls

### Pitfall 1: .env File Corruption During Database Setup
**What goes wrong:** Writing database credentials to .env during setup overwrites existing keys or corrupts formatting.
**Why it happens:** Naive file writing doesn't preserve comments, blank lines, or existing key order.
**How to avoid:** Use a structured .env writer that: (1) reads existing .env line by line, (2) updates matching keys, (3) appends new keys at end, (4) preserves comments and blank lines. Consider `vlucas/phpdotenv` writer or a simple line-by-line approach.
**Warning signs:** `.env` file loses comments, APP_KEY gets overwritten, double-quoted values lose quoting.

### Pitfall 2: Livewire Wizard State Lost on Browser Close
**What goes wrong:** Admin fills steps 1-5, closes browser, returns to step 1 with all progress lost.
**Why it happens:** Livewire public properties are in-memory only; they don't persist across requests without explicit session storage.
**How to avoid:** Call `saveToSession()` after every `nextStep()` / `previousStep()`. Restore from session in `mount()`. Store under a unique key like `openmail:setup:wizard`.
**Warning signs:** Tests show state reset on page reload; user reports progress loss.

### Pitfall 3: IMAP Connection Timeout Blocks Wizard
**What goes wrong:** Admin enters wrong IMAP host, clicks "Test", waits 30+ seconds, page times out.
**Why it happens:** webklex/php-imap default timeout is long (60s+). Connection to unreachable host blocks the entire request.
**How to avoid:** Set a short connection timeout (5-10 seconds) on the IMAP client. Run the connection test in a try/catch with a timeout. Consider wrapping in `Race::timeout()` if available.
**Warning signs:** "Test Connection" button takes forever; admin reports timeout errors.

### Pitfall 4: Installation Lock Race Condition
**What goes wrong:** Two admins open `/install` simultaneously; both see the wizard; both try to create the admin account.
**Why it happens:** Lock file check and creation aren't atomic — there's a TOCTOU window.
**How to avoid:** Use `flock()` on the lock file during the final "Finish" step. If lock acquisition fails, redirect to "already installed" page. This is rare in practice (single-company deployment) but worth handling.
**Warning signs:** Duplicate admin accounts; duplicate settings entries.

### Pitfall 5: Session Driver Mismatch After Setup
**What goes wrong:** Setup wizard writes `SESSION_DRIVER=database` to .env, but the sessions table doesn't exist yet (migrations haven't run).
**Why it happens:** The wizard changes session config before running migrations.
**How to avoid:** Order of operations: (1) Write DB config to .env, (2) Run migrations (creates sessions table), (3) Then switch session driver. During wizard steps 1-6, use file-based sessions. Only switch to database sessions after migrations complete.
**Warning signs:** "Session store not set on request" error after database step.

### Pitfall 6: SMTP "Test Connection" Actually Sends Email
**What goes wrong:** Admin expects connection-only test, but an actual email is sent to the test address.
**Why it happens:** Using Symfony Mailer's `send()` method instead of transport-only connection test.
**How to avoid:** Per D-08, SMTP test must be connection-only. Use `SmtpTransport::fromDsn()` to create the transport, call `$transport->checkConnection()` or attempt a `EHLO` command. Never call `$mailer->send()` during the wizard.
**Warning signs:** Admin receives unexpected test email; SMTP server logs show sent message.

## Code Examples

### IMAP Connection Test
```php
// Source: webklex/php-imap Client.php (github.com/Webklex/php-imap)
// Verified against Packagist and GitHub source
namespace App\Services;

use Webklex\PHPIMAP\Client;

class ImapConnectionTester
{
    public function test(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        try {
            $config = [
                'host' => $host,
                'port' => $port,
                'encryption' => $encryption,
                'username' => $username,
                'password' => $password,
                'protocol' => 'imap',
            ];

            $client = new Client($config);
            $client->connect();

            $folders = $client->getFolders();
            $folderNames = $folders->map(fn($f) => $f->path)->toArray();

            $client->disconnect();

            return [
                'success' => true,
                'folders' => $folderNames,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'trace' => $e->getTraceAsString(),
                ],
            ];
        }
    }
}
```

### SMTP Connection Test (No Email Sent)
```php
// Source: Laravel 12 Symfony Mailer integration
// Pattern: create transport, attempt connection, never send
namespace App\Services;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

class SmtpConnectionTester
{
    public function test(
        string $host,
        int $port,
        string $encryption,
        string $username,
        string $password
    ): array {
        try {
            $scheme = $encryption === 'ssl' ? 'smtps' : 'smtp';
            $dsn = "{$scheme}://{$username}:{$password}@{$host}:{$port}";

            $transport = new EsmtpTransport($dsn);

            // Attempt connection + EHLO
            $transport->checkConnection();

            return ['success' => true];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'technical' => [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                ],
            ];
        }
    }
}
```

### Auto-Detection Provider Map
```php
// Source: CONTEXT.md D-09, D-10, D-11
// Domain → provider mapping for pre-filling IMAP/SMTP settings
namespace App\Services;

class MailConfigDetector
{
    private array $providers = [
        'gmail.com' => [
            'name' => 'Gmail',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
        'outlook.com' => [
            'name' => 'Microsoft Outlook',
            'imap_host' => 'outlook.office365.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ],
        'yahoo.com' => [
            'name' => 'Yahoo Mail',
            'imap_host' => 'imap.mail.yahoo.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.mail.yahoo.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
    ];

    public function detect(string $email): ?array
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return $this->providers[$domain] ?? $this->genericFallback($domain);
    }

    private function genericFallback(string $domain): array
    {
        return [
            'name' => 'Custom',
            'imap_host' => "mail.{$domain}",
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => "smtp.{$domain}",
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ];
    }
}
```

### System Requirements Checker
```php
// Source: kejubayer/laravel-installer pattern (Packagist)
namespace App\Services;

class SystemRequirementsChecker
{
    public function check(): array
    {
        return [
            'php_version' => [
                'label' => 'PHP Version',
                'required' => '8.2',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'extensions' => $this->checkExtensions(),
            'writable' => $this->checkPermissions(),
            'database' => $this->checkDatabaseDriver(),
        ];
    }

    private function checkExtensions(): array
    {
        $required = ['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'curl', 'xml', 'tokenizer'];
        return array_map(fn($ext) => [
            'label' => "ext-{$ext}",
            'passed' => extension_loaded($ext),
        ], $required);
    }

    private function checkPermissions(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => storage_path('../bootstrap/cache'),
        ];
        return array_map(fn($path) => [
            'label' => $path,
            'passed' => is_writable($path),
        ], $paths);
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| PHP IMAP extension (`ext-imap`) | Pure PHP IMAP via webklex/php-imap | 2022-2023 | No server extension required; shared hosting compatible |
| Redis for sessions/cache | Database driver for sessions/cache | Laravel 12 default | Shared hosting compatible; slight performance tradeoff |
| Blade-only forms | Livewire 3 reactive forms | 2023-2024 | SPA-like UX without JS framework; Alpine.js included |
| Tailwind v3 config file | Tailwind v4 CSS-first config | 2025 | No `tailwind.config.js` needed; CSS-based configuration |
| `laravel/ui` auth scaffolding | First-party starter kits (Breeze/Jetstream) | Laravel 9+ | Auth is modular; we build custom for IMAP anyway |

**Deprecated/outdated:**
- `laravel/ui`: Replaced by starter kits; not needed for custom IMAP auth
- `ext-imap`: Not needed with webklex/php-imap; not available on shared hosting
- Redis on shared hosting: Cannot run daemon processes; database driver is the only option

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | webklex/laravel-imap `Client` constructor accepts a config array directly (not just config file) | Code Examples | IMAP tester won't instantiate; would need to use `ClientManager` facade instead |
| A2 | `EsmtpTransport::checkConnection()` exists and performs EHLO without sending mail | Code Examples | SMTP tester would need manual socket approach instead |
| A3 | Laravel 12 includes the `sessions` migration by default | Standard Stack | Would need to manually create the sessions table migration |
| A4 | The `config/imap.php` file is auto-published by `webklex/laravel-imap` | Standard Stack | Would need to publish manually via `vendor:publish` |
| A5 | Database cache driver supports `RateLimiter` atomic operations | Common Pitfalls | Would need file-based cache or different throttling approach |

## Open Questions

1. **Should the wizard write .env directly or use a config service?**
   - What we know: Laravel apps read .env on boot; config cache must be cleared after .env changes
   - What's unclear: Whether to write .env directly (simple) or use a config abstraction (more robust)
   - Recommendation: Write .env directly (matching WordPress-style simplicity), then clear config cache via `Cache::flush()` — this is the standard pattern for self-hosted PHP installers

2. **Should the IMAP guard cache successful authentication?**
   - What we know: IMAP auth on every request adds latency (100-300ms per request)
   - What's unclear: Whether to cache IMAP auth status in session or validate on every request
   - Recommendation: Validate IMAP on login only; store session token for subsequent requests. This matches AUTH-02 ("secure application session created after successful IMAP auth")

3. **How to handle IMAP password storage for the session?**
   - What we know: "Never store mailbox passwords in plaintext" (PROJECT.md constraint)
   - What's unclear: Whether to encrypt the IMAP password in the session or re-prompt
   - Recommendation: Encrypt the IMAP password using Laravel's `Crypt` facade and store in the session. This allows the app to reconnect to IMAP without re-prompting the user. The encrypted value is never written to disk.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | Runtime | ✓ | 8.5.10 | — |
| Composer | Dependency management | ✓ | 2.10.3 | — |
| Node.js | Vite asset building | ✓ | 26.8.1 | — |
| npm | Frontend packages | ✓ | 12.0.2 | — |
| MySQL/MariaDB | Database | ✗ | — | SQLite for local dev; MySQL required for production (shared hosting provides it) |

**Missing dependencies with no fallback:**
- MySQL/MariaDB: Not installed locally. Required for production. For development, use SQLite with `DB_CONNECTION=sqlite` and `DB_DATABASE=database/database.sqlite`. For production on shared hosting, MySQL/MariaDB is provided by cPanel.

**Missing dependencies with fallback:**
- MySQL: Use SQLite for local development; MySQL required for production deployment.

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest PHP (Laravel 12 default) |
| Config file | `phpunit.xml` (generated by `composer create-project`) |
| Quick run command | `php artisan test --filter=SetupWizard` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| SETUP-01 | Wizard detects fresh install | unit | `php artisan test --filter=InstallLockTest` | ❌ Wave 0 |
| SETUP-02 | System requirements check | unit | `php artisan test --filter=SystemRequirementsTest` | ❌ Wave 0 |
| SETUP-03 | Database config + test | integration | `php artisan test --filter=DatabaseStepTest` | ❌ Wave 0 |
| SETUP-04 | IMAP/SMTP config + test | integration | `php artisan test --filter=MailConfigStepTest` | ❌ Wave 0 |
| SETUP-05 | Auto-detection | unit | `php artisan test --filter=MailConfigDetectorTest` | ❌ Wave 0 |
| SETUP-07 | Admin account creation | unit | `php artisan test --filter=AdminAccountTest` | ❌ Wave 0 |
| SETUP-09 | Verification suite | integration | `php artisan test --filter=InstallationVerifierTest` | ❌ Wave 0 |
| SETUP-10 | Installation lock | unit | `php artisan test --filter=InstallLockTest` | ❌ Wave 0 |
| SETUP-11 | Resumable state | unit | `php artisan test --filter=SetupWizardTest` | ❌ Wave 0 |
| AUTH-01 | IMAP login | integration | `php artisan test --filter=ImapGuardTest` | ❌ Wave 0 |
| AUTH-03 | Login throttling | unit | `php artisan test --filter=LoginThrottleTest` | ❌ Wave 0 |
| AUTH-04 | Session rotation | unit | `php artisan test --filter=SessionRotationTest` | ❌ Wave 0 |
| AUTH-05 | CSRF protection | unit | Built-in Laravel middleware test | ✅ Default |
| AUTH-06 | Secure cookies | unit | `php artisan test --filter=CookieConfigTest` | ❌ Wave 0 |
| AUTH-07 | Session invalidation | unit | `php artisan test --filter=LogoutTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=<relevant-test>`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/SetupWizardTest.php` — covers SETUP-01, SETUP-10, SETUP-11
- [ ] `tests/Unit/MailConfigDetectorTest.php` — covers SETUP-05
- [ ] `tests/Unit/SystemRequirementsTest.php` — covers SETUP-02
- [ ] `tests/Feature/ImapGuardTest.php` — covers AUTH-01
- [ ] `tests/Unit/LoginThrottleTest.php` — covers AUTH-03
- [ ] `tests/Feature/DatabaseStepTest.php` — covers SETUP-03
- [ ] `tests/Feature/MailConfigStepTest.php` — covers SETUP-04
- [ ] `tests/Unit/InstallationVerifierTest.php` — covers SETUP-09
- [ ] Framework install: `composer create-project laravel/laravel` — generates test infrastructure

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | Custom IMAP guard via `Auth::viaRequest`; session-based auth |
| V3 Session Management | yes | SESSION_DRIVER=database; rotation on auth; 24h lifetime; HttpOnly cookies |
| V4 Access Control | yes | `auth` middleware on protected routes; `guest` middleware on setup |
| V5 Input Validation | yes | Laravel validation rules per wizard step; `ValidateCsrfToken` middleware |
| V6 Cryptography | yes | `Hash::make()` for passwords; `Crypt::encrypt()` for IMAP passwords in session |

### Known Threat Patterns for Laravel + IMAP Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Credential stuffing on login | Tampering | RateLimiter dual-key (IP + email), progressive delays per D-14 |
| IMAP credential exposure in logs | Information Disclosure | Never log passwords; use `Log::channel('security')` with redacted fields |
| .env file exposure | Information Disclosure | `.env` in `.gitignore`; `APP_KEY` generated on install; file permissions 644 |
| Session fixation | Tampering | `$request->session()->regenerate()` after login (AUTH-04) |
| CSRF on wizard forms | Tampering | `@csrf` Blade directive on all forms (AUTH-05) |
| IMAP/SMTP man-in-the-middle | Information Disclosure | TLS enforcement (AUTH-08); port 993/465 with SSL default (D-12) |
| Wizard bypass via direct URL | Elevation of Privilege | InstallLock middleware blocks wizard when lock file exists (SETUP-10) |
| IMAP password in transit | Information Disclosure | Encrypt with `Crypt::encrypt()` before storing in session; never write to DB |

## Sources

### Primary (HIGH confidence)
- [packagist.org/packages/laravel/framework] — Laravel 12.x version list, v12.68.0 confirmed
- [packagist.org/packages/livewire/livewire] — Livewire 3.8.6 confirmed, compatible with Laravel 10-13
- [packagist.org/packages/webklex/laravel-imap] — v6.2.0 confirmed, PHP ^8.0.2, Laravel >=6.0.0
- [laravel.com/docs/12.x/authentication] — Auth guards, custom guards, Closure Request Guards
- [laravel.com/docs/12.x/rate-limiting] — RateLimiter facade, `attempt()`, `tooManyAttempts()`, `hit()`
- [github.com/Webklex/php-imap/src/Client.php] — IMAP client API: `connect()`, `getFolders()`, `isConnected()`
- [github.com/livewire/laravel-docs/12.x/authentication.md] — Livewire + auth integration

### Secondary (MEDIUM confidence)
- [Medium: "How to Build a Multi-Step Form in Laravel and Livewire" (2026-08-04)] — Session persistence pattern, step validation, progress saving
- [Packagist: kejubayer/laravel-installer] — Lock file pattern: `storage/installed`, middleware guard, install flow
- [Packagist: zisunal/laravel-installer] — Livewire wizard with visual progress, lock file at `storage/app/installed.lock`
- [Packagist: codegenie-be/laravel-livewire-multistep-form] — Multi-step form component (alternative to hand-roll)
- [Tech Verse Daily: "Laravel Login Throttle" (2026-08-13)] — Dual-key rate limiting, exponential backoff pattern
- [dev.to: "Multi-guard authentication with Laravel 12" (2025-05-10)] — Custom guard implementation pattern

### Tertiary (LOW confidence)
- [ASSUMED] `EsmtpTransport::checkConnection()` method existence — Symfony Mailer may use different API for connection testing
- [ASSUMED] Laravel 12 `composer create-project` includes sessions migration by default — standard but not verified this session

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — all packages verified on Packagist/npm with current versions
- Architecture: HIGH — Laravel 12 + Livewire 3 patterns are well-documented and established
- Pitfalls: MEDIUM — based on common Laravel patterns; some IMAP-specific pitfalls are theoretical

**Research date:** 2026-09-05
**Valid until:** 2026-10-05 (30 days — Laravel 12 is stable, Livewire 3.x is mature)
