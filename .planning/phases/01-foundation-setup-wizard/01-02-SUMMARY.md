---
phase: 01-foundation-setup-wizard
plan: 02
subsystem: setup-wizard
tags:
  - livewire
  - wizard
  - imap
  - smtp
  - installation
requires:
  - SETUP-01
  - SETUP-02
  - SETUP-03
  - SETUP-04
  - SETUP-05
  - SETUP-06
  - SETUP-07
  - SETUP-08
  - SETUP-09
  - SETUP-11
provides:
  - setup-wizard-livewire
  - imap-connection-tester
  - smtp-connection-tester
  - installation-verifier
  - wizard-step-views
affects:
  - app/Livewire/SetupWizard.php
  - app/Services/ImapConnectionTester.php
  - app/Services/SmtpConnectionTester.php
  - app/Services/InstallationVerifier.php
  - resources/views/setup/wizard.blade.php
  - resources/views/setup/steps/welcome.blade.php
  - resources/views/setup/steps/requirements.blade.php
  - resources/views/setup/steps/database.blade.php
  - resources/views/setup/steps/mail-config.blade.php
  - resources/views/setup/steps/app-settings.blade.php
  - resources/views/setup/steps/admin-account.blade.php
  - resources/views/setup/steps/security.blade.php
  - resources/views/setup/steps/verify.blade.php
  - config/openmail.php
  - routes/web.php
  - .env
tech-stack:
  added:
    - livewire-component-wizard-pattern
  patterns:
    - livewire-multi-step-wizard-session-persistence
    - structured-env-file-writer
    - flock-atomic-lock
key-decisions:
  - "Single Livewire component (SetupWizard) manages all 8 steps with session persistence"
  - "wire:model (deferred) on all inputs, never wire:model.live"
  - "Session key 'openmail:setup:wizard' stores all step data for resumable installation"
  - "Database connection test uses temporary config connection + Artisan::call migrate"
  - "IMAP/SMTP connection testers use webklex/php-imap and Symfony Mailer with 10s timeout"
  - "Auto-detection on email domain blur via MailConfigDetector"
  - "Structured .env writer preserves existing keys, never overwrites APP_KEY"
  - "flock() for atomic lock file creation"
  - "InstallationVerifier runs 7 checks with fixStep links"
  - "Alpine.js for expandable technical error details"
requirements-completed:
  - SETUP-01
  - SETUP-02
  - SETUP-03
  - SETUP-04
  - SETUP-05
  - SETUP-06
  - SETUP-07
  - SETUP-08
  - SETUP-09
  - SETUP-11
duration: 90 min
completed: "2026-09-05T11:30:00Z"
---

# Phase 1 Plan 2: Complete 8-Step Setup Wizard

## Summary

Built the complete 8-step setup wizard with mail configuration, connection testing, auto-detection, and verification suite — the full administrator installation experience.

## What Was Built

### Livewire SetupWizard Component
Single component managing all 8 steps with:
- Session persistence via `session()->put('openmail:setup:wizard', ...)`
- Step validation (only current step validated on navigation)
- Resumable state: detects saved session on mount, offers "Continue from step X" or "Start over"
- `wire:model` (deferred) on all inputs — never `wire:model.live`
- Auto-save after every step navigation

### Step 1: Welcome/Detect Install
- Detects fresh vs existing installation
- Shows appropriate message
- Application name input

### Step 2: System Requirements
- Uses `SystemRequirementsChecker` service from Plan 01
- Displays PHP version, extensions, writable directories, database driver
- Green checkmark/red X per check
- Retry button re-runs checks without losing data

### Step 3: Database Config
- Fields: host, port, database name, username, password
- "Test Connection" button:
  - Creates temporary DB config
  - Tests via `DB::purge()` + reconnect
  - On success: writes to .env (structured writer), runs `php artisan migrate`
- Inline errors with expandable technical details
- Form data persists after failure

### Step 4: Mail Config (IMAP/SMTP)
- Two sections: IMAP and SMTP
- Each: host, port, encryption (SSL/TLS/STARTTLS/None), username, password
- **Auto-detection**: Email domain field triggers `MailConfigDetector` on blur
  - Gmail → imap.gmail.com:993/SSL, smtp.gmail.com:465/SSL
  - Outlook/365 → outlook.office365.com:993/SSL, smtp.office365.com:587/TLS
  - Yahoo → imap.mail.yahoo.com:993/SSL, smtp.mail.yahoo.com:465/SSL
  - Unknown → mail.domain.com:993/SSL, smtp.domain.com:465/SSL
- Default encryption: SSL/TLS, ports 993/465
- "Test IMAP Connection" → `ImapConnectionTester` (10s timeout)
- "Test SMTP Connection" → `SmtpConnectionTester` (connection-only, no email sent)
- Expandable technical error details on failure

### Step 5: App Settings
- App name, organization name, domain, timezone (dropdown)
- Stored in `settings` table via `Setting` model

### Step 6: Admin Account
- Email, password, password confirmation
- Strong password validation: 8+ chars, mixed case, number (Laravel rules)
- Password strength indicator (Alpine.js)
- Creates User with `Hash::make($password)`

### Step 7: Security Defaults
- HTTPS enforcement explanation + current status detection
- Cookie settings: HttpOnly + SameSite=Lax (fixed), Secure flag (auto-detect HTTPS)
- Session lifetime: 24 hours (1440 min)
- Progressive throttling table: 5s/3rd, 30s/5th, 5min/7th, 15min/10th
- Writes security config to .env

### Step 8: Verify & Finish
- `InstallationVerifier` service runs 7 checks:
  1. Config readable (.env)
  2. Database connection
  3. IMAP connection (using stored config)
  4. SMTP connection (using stored config)
  5. Filesystem writable
  6. APP_KEY exists
  7. PHP version ≥8.2
- Each check: green/red status, expandable technical details
- Failed checks show "Fix issues → Step X" link
- "Finish" button:
  - Creates admin user
  - Saves app settings
  - Writes mail + security config to .env
  - Creates `storage/installed` lock file with `flock()` atomicity
  - Clears wizard session
  - Redirects to `/login`

### Services Created
- **ImapConnectionTester**: Uses `Webklex\PHPIMAP\Client`, 10s timeout, returns folders on success
- **SmtpConnectionTester**: Uses `Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport`, `checkConnection()`, never sends email
- **InstallationVerifier**: `verify()` returns array of 7 check results with fixStep links

### .env Writing
- Structured approach: read existing .env line by line, update matching keys, append new keys, preserve comments
- Called after Step 3 (DB), Step 4 (Mail), Step 7 (Security)
- `Artisan::call('config:clear')` after each write

### Views
- 8 step Blade views in `resources/views/setup/steps/`
- Main wizard view includes steps via `@include`
- Alpine.js for expandable technical details (`x-show` toggle)
- Step indicator with 8 dots, current highlighted

## Verification Results

| Criteria | Status |
|----------|--------|
| Wizard renders 8-step navigation | ✓ |
| Step 1: fresh/continue detection | ✓ |
| Step 2: requirements green/red | ✓ |
| Step 3: DB test + migrate + .env write | ✓ |
| Step 4: auto-detect Gmail/Outlook/Yahoo | ✓ |
| Step 4: IMAP test (10s timeout) | ✓ |
| Step 4: SMTP test (connection-only) | ✓ |
| Inline errors with expandable details | ✓ |
| Form data persists after failure | ✓ |
| Session saves after each step | ✓ |
| wire:model (deferred) on all inputs | ✓ |
| Auto-detect: gmail.com → correct settings | ✓ |
| Auto-detect: unknown.com → generic fallback | ✓ |
| .env written with DB config after step 3 | ✓ |
| Step 5: app settings form | ✓ |
| Step 6: admin account + strong password | ✓ |
| Step 7: security config + .env write | ✓ |
| Step 8: 7 verification checks with fixStep | ✓ |
| Finish: creates lock file + clears session | ✓ |
| Resumable state on return | ✓ |
| `php artisan test` passes | ✓ |

## Deviations from Plan

None - plan executed exactly as written.

## Ready for Next Plan

Plan 03 will implement IMAP-backed authentication with session management, login throttling, and security hardening.