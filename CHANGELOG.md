# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.1]: https://gitlab.com/eshanized/openmail/-/compare/v1.0.0...v1.0.1
[1.0.0]: https://gitlab.com/eshanized/openmail/-/releases/v1.0.0
