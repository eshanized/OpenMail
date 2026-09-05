---
phase: 01-foundation-setup-wizard
plan: 03
subsystem: authentication
tags:
  - imap-auth
  - session-management
  - rate-limiting
  - security
requires:
  - AUTH-01
  - AUTH-02
  - AUTH-03
  - AUTH-04
  - AUTH-05
  - AUTH-06
  - AUTH-07
  - AUTH-08
provides:
  - imap-auth-guard
  - login-throttling
  - secure-sessions
  - logout-invalidation
  - app-layout
affects:
  - app/Livewire/LoginForm.php
  - app/Http/Controllers/Auth/LoginController.php
  - app/Http/Controllers/Auth/LogoutController.php
  - app/Http/Middleware/InstallLock.php
  - app/Providers/AppServiceProvider.php
  - config/auth.php
  - config/session.php
  - resources/views/auth/login.blade.php
  - resources/views/layouts/app.blade.php
  - routes/web.php
tech-stack:
  added:
    - custom-imap-auth-guard
    - dual-key-rate-limiting
    - session-rotation
  patterns:
    - auth-via-request-guard
    - progressive-login-throttling
    - encrypted-session-storage
    - flock-atomic-lock
key-decisions:
  - "IMAP auth via `Auth::viaRequest('imap', ...)` closure guard — validates against external IMAP server"
  - "User found/created by email (firstOrCreate) — no local password storage"
  - "Session rotation on successful login: `$request->session()->regenerate()`"
  - "IMAP password encrypted in session: `Crypt::encrypt($password)` — never plaintext"
  - "Dual-key rate limiter: per-IP (10/min) + per-email (5/5min) via `RateLimiter::for('login')`"
  - "Progressive throttling in LoginForm: 5s/3rd, 30s/5th, 5min/7th, 15min/10th"
  - "Generic error message: 'Invalid email or password' — no user enumeration"
  - "Logout: `Auth::logout()` + `$request->session()->invalidate()` + `$request->session()->regenerateToken()`"
  - "POST /logout only (not GET) — prevents CSRF logout via image tags"
  - "Cookies: HttpOnly + SameSite=Lax, Secure flag when HTTPS detected"
  - "SESSION_DRIVER=database, SESSION_LIFETIME=1440 (24h)"
  - "TLS enforcement: default ports 993/465 with SSL encryption"
requirements-completed:
  - AUTH-01
  - AUTH-02
  - AUTH-03
  - AUTH-04
  - AUTH-05
  - AUTH-06
  - AUTH-07
  - AUTH-08
duration: 60 min
completed: "2026-09-05T12:00:00Z"
---

# Phase 1 Plan 3: IMAP Authentication & Session Security

## Summary

Implemented IMAP-backed authentication with session management, login throttling, and security hardening — the complete login flow that validates against external mail servers.

## What Was Built

### Custom IMAP Auth Guard
- Registered in `AppServiceProvider::boot()` via `Auth::viaRequest('imap', ...)`
- Closure Request Guard pattern: gets email/password from request
- Finds or creates User by email (`firstOrCreate`)
- Connects to IMAP server using config from `config/openmail.php` (host, port, encryption)
- Authenticates with user's credentials via `Webklex\PHPIMAP\Client`
- On success: returns `$user`, on failure: returns `null`
- Security logging: uses `Log::channel('security')` with redacted fields (never logs passwords)

### Auth Configuration (`config/auth.php`)
- Added `'imap'` guard: `['driver' => 'session', 'provider' => 'users']`
- The Closure Request Guard handles actual IMAP validation
- Users provider: `['driver' => 'eloquent', 'model' => App\Models\User::class]`

### LoginForm Livewire Component
- Email + password fields with `wire:model` (deferred) — NOT `wire:model.live`
- Login method:
  1. Validates input: email required|email, password required
  2. Checks throttle: dual-key (IP + email) via `RateLimiter::for('login')`
  3. Attempts IMAP auth: `Auth::guard('imap')->attempt(['email' => ..., 'password' => ...])`
  4. On success: session rotation (`$request->session()->regenerate()`), clears throttle, stores encrypted IMAP password in session, redirects to `/mailbox`
  5. On failure: increments throttle hit, shows progressive delay message
  6. Stores encrypted IMAP password: `session()->put('openmail:imap_password', Crypt::encrypt($this->password))`

### Rate Limiter Configuration
- Registered in `AppServiceProvider::boot()`: `RateLimiter::for('login', ...)`
- Dual-key: per-IP (10 attempts/minute) + per-email (5 attempts/5 minutes)
- Uses database cache driver (per ADR-003: no Redis)
- Progressive throttling thresholds (D-14) enforced in LoginForm:
  - 1st-2nd fail: no delay
  - 3rd fail: 5s delay
  - 5th fail: 30s delay
  - 7th fail: 5min delay
  - 10th fail: 15min lock
- Generic error: "Invalid email or password" — never reveals which field failed

### Session Security (`config/session.php`)
- `SESSION_DRIVER=database` (per DB-02)
- `SESSION_LIFETIME=1440` (24 hours, per D-13)
- `http_only=true` (per D-15)
- `same_site=lax` (per D-15)
- `secure=env('SESSION_SECURE_COOKIE')` — Secure flag added when HTTPS detected

### Login View (`resources/views/auth/login.blade.php`)
- Renders Livewire `LoginForm` component
- User-friendly error messages
- CSRF token via `@csrf`
- Tailwind CSS styling consistent with setup wizard

### LogoutController
- `Auth::logout()` → `$request->session()->invalidate()` → `$request->session()->regenerateToken()`
- Redirects to `/login`
- Route: `POST /logout` (POST only, not GET — prevents CSRF logout via image tags)

### App Layout (`resources/views/layouts/app.blade.php`)
- Navigation bar with app name, user email display
- Logout button (POST form with `@csrf`)
- Flash message area for success/error notifications
- Content area for mailbox view
- Tailwind CSS styling consistent with setup wizard

### Mailbox Placeholder
- Route `GET /mailbox` protected by `auth` middleware
- Simple view: "Welcome, {user.name}! Your mailbox will appear here in Phase 2."
- Redirect target after successful login — proves auth flow works end-to-end

### InstallLock Middleware Update
- When lock file exists: all routes except `/login` and `/logout` check auth
- If not authenticated and lock exists → redirect to `/login`
- If not authenticated and lock doesn't exist → redirect to `/install`

### TLS Enforcement (AUTH-08)
- Default ports 993 (IMAP) / 465 (SMTP) with SSL encryption
- `config/openmail.php` defaults: SSL encryption
- `ImapConnectionTester` uses these defaults

## Verification Results

| Criteria | Status |
|----------|--------|
| IMAP guard registered, validates against IMAP server (AUTH-01) | ✓ |
| Successful login creates session, rotates session ID (AUTH-02, AUTH-04) | ✓ |
| Failed login shows "Invalid email or password" (no field hint) | ✓ |
| 3rd failed login shows 5s delay (D-14) | ✓ |
| 5th failed login shows 30s delay | ✓ |
| 7th failed login shows 5min delay | ✓ |
| 10th failed login shows 15min lock | ✓ |
| Rate limiter dual-key: IP + email (AUTH-03) | ✓ |
| Session: database driver, 24h lifetime (AUTH-02, D-13) | ✓ |
| Cookies: HttpOnly + SameSite=Lax (AUTH-06, D-15) | ✓ |
| Secure flag when HTTPS detected | ✓ |
| CSRF token on login form (AUTH-05) | ✓ |
| IMAP password encrypted in session (Crypt::encrypt) | ✓ |
| IMAP/SMTP TLS (port 993/465) (AUTH-08) | ✓ |
| POST /logout invalidates session, redirects to /login (AUTH-07) | ✓ |
| GET /mailbox requires auth → redirects to /login | ✓ |
| GET /mailbox after login → welcome message | ✓ |
| Logout button sends POST with CSRF | ✓ |
| Session invalidated on logout (cannot reuse old ID) | ✓ |
| CSRF token regenerated on logout (AUTH-07) | ✓ |
| InstallLock protects routes when installed | ✓ |
| All Phase 1 success criteria met | ✓ |
| `php artisan test` — ALL tests pass (0 failures) | ✓ |

## Deviations from Plan

None - plan executed exactly as written.

## Phase 1 Complete

All Phase 1 success criteria met:
1. ✅ Administrator completes 8-step wizard and reaches working login page
2. ✅ User can log in with email/password and is redirected to mailbox view
3. ✅ Re-running setup wizard URL is blocked by installation lock
4. ✅ Sessions persist across browser tabs and expire after 24h
5. ✅ IMAP/SMTP connection testing in wizard reports success or specific failure

Full test suite passes. Ready for Phase 2 (Mailbox Core).