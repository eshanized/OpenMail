# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

### Added
- **Durable Send Later**: Persist scheduled email delivery in the existing delivery state machine, expose a scheduled-message queue, and allow users to cancel future messages back to Drafts.
- **Scheduled MIME Durability**: Deliver the persisted MIME payload so attachment delivery does not depend on temporary browser-upload paths still existing at the scheduled time; restore Bcc recipients only to the SMTP envelope.


The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

---

## [1.1.0] - 2026-10-16

### Added
- **Durable Email Delivery Pipeline**: Explicit state machine (`pending`, `claimed`, `processing`, `sent`, `retry_waiting`, `cancelled`, `failed`, `uncertain`) with idempotency keys and stable Message-IDs.
- **Concurrency-Safe Worker Claiming**: Atomic compare-and-set worker claiming using unique worker tokens, bounded batching, and stale lease recovery in `pending-sends:process`.
- **Dedicated Mail Transport Service**: Dedicated MIME construction and delivery service with explicit error categorization (`transport`, `rejection`, `uncertain`) and envelope recipient segregation (no Bcc leakage in message headers).
- **Outbound Connection Guard**: SSRF prevention guarding SMTP and IMAP connection testers against loopback, private RFC 1918, cloud metadata IPs, and port misuse.
- **TipTap Pure-PHP Renderer**: Built-in JSON-to-HTML converter for signatures eliminating external dependency crashes while enforcing HTML sanitization.
- **Atomic Configuration Writer**: Safe temp-file-to-rename `.env` writer enforcing strict `0600` permissions.
- **Delivery & Security Regression Suite**: Comprehensive feature tests covering atomic claiming, retry backoff, MIME Unicode, search XSS escaping, signature conversion, and vCard payload bounds.

### Changed
- **Decoupled SMTP and IMAP Sent Sync**: SMTP acceptance and IMAP Sent-folder synchronization now operate independently, preventing duplicate transmissions if Sent sync encounters intermittent network issues.
- **Safe Draft Replacement**: `appendToDrafts()` appends replacement draft first, verifies new UID, and only then removes old draft, completely eliminating draft loss.
- **Authentic IMAP Sorting**: Real in-memory sorting by sender, subject, date, and size for mail folders.
- **Strict Quality Gates in CI**: Removed `continue-on-error` on Pint, composer audit, and npm audit; added PHP 8.2-8.4 matrix testing.
- **Dependency Upgrades**: Upgraded `league/commonmark`, `symfony/http-foundation`, `symfony/http-kernel`, `dompurify`, and tooling dependencies to achieve 0 vulnerabilities in both Composer and npm audits.

### Fixed
- **Undo Send Countdown & Persistence**: Fixed UI race condition where `resetForm()` clobbered `pendingSendId` and toast state; implemented real server-side cancellation window before SMTP transmission.
- **Signature HTML Corruption**: Fixed bug where signatures were stored as raw JSON strings; HTML is now properly rendered and sanitized.
- **Search Highlighting XSS**: Fixed HTML injection vulnerability in `SearchService::highlightMatches()` and client-side search bar by pre-escaping text before wrapping in `<mark>`.
- **Session Revocation IDOR**: Enforced server-authoritative `session()->getId()` and authentication verification on session revocation.
- **Contact and Label Authorization**: Added ownership checks on `ContactModal`, `LabelModal`, and `MessageViewer` mutating actions.
- **Setup Route Lock**: Removed unauthorized login bypasses while the application is in an uninstalled state.

---

## [1.0.1] - 2026-09-12

### Added
- **Mobile Responsiveness**: Comprehensive responsive design optimizations for mobile viewports, including sliding drawer navigation for folder and contact sidebars.
- **Multi-Recipient Input Validation**: Enhanced email composer with real-time recipient validation, recipient chips, and error feedback for invalid address formats.
- **Non-Technical Setup Guide**: Added [`SETUP.md`](SETUP.md) — a complete step-by-step guide with screenshots and troubleshooting for cPanel shared hosting and VPS deployment without CLI requirements.
- **Regression Test Coverage**: Added dedicated unit and feature tests covering empty UID handling, IMAP flag stripping, and SMTP connection error recovery.

### Changed
- **Brand Visual Overhaul**: Adopted vibrant Coral Red primary branding palette across navigation, button states, active badges, and focus rings.
- **Narrow Viewport Layouts**: Re-engineered conversation thread rows and message rows to provide legible text truncation and compact layouts on small screens.
- **Build Pipeline Optimization**: Optimized autoloader generation and streamlined deployment zip artifact generation.

### Fixed
- **IMAP Empty UID Handling**: Resolved fatal errors when IMAP servers returned empty, null, or malformed message UID sets during folder sync.
- **IMAP Flag Normalization**: Fixed issue where leading backslashes on IMAP system flags (`\Seen`, `\Flagged`, `\Answered`) caused improper state persistence.
- **Theme & Density Bootstrapping**: Fixed theme toggle flash and density preference synchronization on initial user login.
- **Folder Navigation State**: Corrected active folder indicator in mobile responsive view when navigating between nested mailboxes.

---

## [1.0.0] - 2026-09-11

### Added
- **Browser-Based Setup Wizard**: An 8-step WordPress-style graphical installer at `/install` that auto-detects mail servers (Gmail, Outlook, Yahoo, Custom), verifies PHP extensions, runs database migrations, and locks the installer upon completion.
- **Headless CLI Installer**: `php artisan openmail:install` for automated CI/CD and VPS installations.
- **Pure-PHP IMAP Engine**: Native IMAP integration via `webklex/laravel-imap` with support for folder hierarchy, message pagination, flag synchronization, and nested MIME attachment handling without requiring PHP's legacy `ext-imap`.
- **Rich Email Composer**: Powered by TipTap 3 with formatting toolbar, inline link insertion, table support, task lists, and emoji picker.
- **Dual-Layer HTML Sanitization**: Server-side HTMLPurifier paired with client-side DOMPurify in a sandboxed iframe to prevent XSS and script execution.
- **Remote Image Blocking**: External tracking pixels and remote images blocked by default with one-click consent loading.
- **Conversation Threading**: JWZ conversation threading algorithm grouping related messages by Message-ID, In-Reply-To, and References headers.
- **Full-Text Search**: Fast relevance-ranked search powered by Laravel Scout with native MySQL/MariaDB FULLTEXT indexes.
- **Gmail-Style Labels**: 10-color customizable label palette with instant filtering and bulk label application.
- **Address Book & Autocomplete**: Contact management with contact groups, vCard export/import, and instant composer autocomplete from recent recipients.
- **Undo Send**: Configurable delivery delay (5–30 seconds) allowing users to cancel sent messages before SMTP dispatch.
- **Multi-Signature Management**: Create and manage multiple rich-text email signatures with automatic default insertion.
- **Audit Logging**: Comprehensive security event logging for logins, session revocations, and sending operations with 90-day automatic pruning.
- **Content Security Policy (CSP)**: Hardened CSP headers with cryptographic nonces and violation reporting endpoint.
- **Zero-Daemon Architecture**: Database-backed sessions and cache eliminating the need for Redis, Memcached, or long-running workers on shared hosting.

[1.0.1]: https://github.com/eshanized/OpenMail/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/eshanized/OpenMail/releases/tag/v1.0.0
