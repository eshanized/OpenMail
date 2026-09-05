---
phase: 01-foundation-setup-wizard
plan: 01
subsystem: foundation
tags:
  - tracer
  - scaffold
  - database
  - auth
  - setup-wizard
requires:
  - SETUP-01
  - SETUP-02
  - SETUP-10
  - SETUP-12
  - DB-01
  - DB-02
  - DB-05
provides:
  - laravel-12-scaffold
  - install-lock-middleware
  - database-schema
  - user-model
  - setting-model
  - mail-config-detector
  - system-requirements-checker
  - wizard-shell
  - login-stub
affects:
  - config/openmail.php
  - config/session.php
  - config/auth.php
  - app/Http/Middleware/InstallLock.php
  - app/Http/Controllers/SetupController.php
  - app/Http/Controllers/Auth/LoginController.php
  - app/Livewire/LoginForm.php
  - app/Models/User.php
  - app/Models/Setting.php
  - app/Services/MailConfigDetector.php
  - app/Services/SystemRequirementsChecker.php
  - database/migrations/0001_01_01_000000_create_users_table.php
  - database/migrations/0001_01_01_000001_create_sessions_table.php
  - database/migrations/0001_01_01_000002_create_settings_table.php
  - resources/views/layouts/setup.blade.php
  - resources/views/setup/wizard.blade.php
  - resources/views/auth/login.blade.php
  - resources/views/livewire/login-form.blade.php
  - routes/web.php
tech-stack:
  added:
    - laravel/framework^12.0
    - livewire/livewire^4.4
    - webklex/laravel-imap^6.2
    - ezyang/htmlpurifier^4.19
    - spatie/laravel-csp^3.28
  patterns:
    - install-lock-middleware
    - database-sessions
    - custom-auth-guard-preparation
    - alpine-js-wizard-shell
key-decisions:
  - "Laravel 12 with Livewire 3 (v4.4) for reactive UI"
  - "Database sessions (SESSION_DRIVER=database) for shared hosting compatibility"
  - "Installation lock via storage/installed file + middleware"
  - "IMAP authentication via custom guard (to be implemented in Plan 03)"
  - "MailConfigDetector auto-detects Gmail, Outlook, Yahoo + generic fallback"
  - "SystemRequirementsChecker validates PHP 8.2+, extensions, permissions"
  - "Session lifetime 24h (1440 min), HttpOnly, SameSite=Lax, Secure on HTTPS"
  - "Default IMAP port 993, SMTP port 465, SSL encryption"
  - "Wizard shell uses Alpine.js for interactivity; Livewire implementation in Plan 02"
requirements-completed:
  - SETUP-01
  - SETUP-02
  - SETUP-10
  - SETUP-12
  - DB-01
  - DB-02
  - DB-05
duration: 45 min
completed: "2026-09-05T10:45:00Z"
---

# Phase 1 Plan 1: Foundation & Setup Wizard Scaffold

## Summary

Created the Laravel 12 project scaffold with database schema, installation lock middleware, auto-detection services, and stub login/wizard pages — the end-to-end foundation that proves the full stack works.

## What Was Built

### Laravel 12 Project Scaffold
- Created fresh Laravel 12.69.1 project with all dependencies
- Installed required packages: `livewire/livewire`, `webklex/laravel-imap`, `ezyang/htmlpurifier`, `spatie/laravel-csp`
- Built frontend assets with Vite + Tailwind CSS v4 (`npm run build`)

### Database Schema (3 migrations)
- **Users table**: `id`, `name`, `email` (unique), `password` (nullable - IMAP auth), `remember_token`, `timestamps`
- **Sessions table**: `id` (PK), `user_id` (FK, nullable), `ip_address`, `user_agent`, `payload`, `last_activity` (indexed)
- **Settings table**: `id`, `key` (unique), `value` (longText, nullable), `group` (default: 'general'), `timestamps`

### Installation Lock Middleware (`InstallLock`)
- Checks for `storage/installed` file on every request
- If installed + accessing `/install*` → returns 404
- If not installed + accessing other routes → redirects to `/install`
- Registered in `bootstrap/app.php` middleware stack

### Models
- **User**: Implements `Authenticatable`, nullable password for IMAP auth
- **Setting**: Key-value store with `get()`/`set()` helpers using Cache

### Services
- **MailConfigDetector**: Auto-detects Gmail, Outlook/365, Yahoo from email domain; generic fallback for unknown domains
- **SystemRequirementsChecker**: Validates PHP ≥8.2, required extensions (mbstring, openssl, pdo, pdo_mysql, curl, xml, tokenizer), writable directories, SQLite driver

### Controllers & Routes
- `SetupController@show`: Renders wizard shell, passes system requirements
- `LoginController@show`: Renders login page, redirects to `/install` if not installed
- Routes: `/install`, `/login`, `POST /logout`, `/mailbox` (protected)

### Views
- **Setup layout**: Shared layout for wizard pages
- **Wizard shell** (`setup.wizard`): 8-step navigation with Alpine.js interactivity (steps 1-8, steps 5-8 are placeholders for Plan 02)
- **Login page** (`auth.login`): Renders Livewire `LoginForm` component
- **LoginForm** (`livewire.login-form`): Email/password fields with error display

### Configuration
- `config/openmail.php`: IMAP/SMTP defaults (port 993/465, SSL), provider detection config
- `config/session.php`: Database driver, 1440 min lifetime, HttpOnly, SameSite=Lax
- `config/auth.php`: Standard setup (IMAP guard to be added in Plan 03)

## Verification Results

All acceptance criteria verified:

| Criteria | Status |
|----------|--------|
| Laravel 12 app boots | ✓ |
| Migrations create 3 tables | ✓ |
| Lock file blocks /install (404) | ✓ |
| No lock file → wizard renders | ✓ |
| /login renders email/password form | ✓ |
| / → redirects to /install | ✓ |
| MailConfigDetector: Gmail → correct settings | ✓ |
| MailConfigDetector: unknown → generic fallback | ✓ |
| SystemRequirementsChecker: PHP version, extensions, permissions | ✓ |
| config/openmail.php: IMAP 993, SMTP 465, SSL | ✓ |
| config/session.php: database, 24h, HttpOnly, SameSite=Lax | ✓ |
| npm run build: Tailwind CSS compiled | ✓ |
| php artisan test: all pass | ✓ |

## Deviations from Plan

None - plan executed exactly as written.

## Ready for Next Plan

Plan 02 will build the complete 8-step setup wizard with:
- Full Livewire `SetupWizard` component with session persistence
- IMAP/SMTP connection testing (`ImapConnectionTester`, `SmtpConnectionTester`)
- Database configuration with connection test and migration running
- Admin account creation with strong password validation
- Security defaults configuration
- Full verification suite (`InstallationVerifier`)
- Resumable installation state