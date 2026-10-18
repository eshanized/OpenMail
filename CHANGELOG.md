# Changelog

All notable changes to this project will be documented in this file.

## [Unreleased]

No unreleased changes yet.

## [2.0.0] - 2026-10-18

OpenMail 2.0 is a major productivity, privacy, reliability, and user-experience release. It expands the self-hosted IMAP/SMTP workspace while keeping the Laravel/Livewire architecture and shared-hosting deployment model.

### Added

- **Durable Send Later**: Schedule a message for future delivery and inspect it in a dedicated scheduled queue. Scheduled work is persisted rather than relying on the browser remaining open, and eligible messages can be cancelled back to Drafts.
- **Safer scheduled MIME delivery**: Persist the prepared MIME message so future delivery does not depend on temporary browser-upload paths remaining available. Keep Bcc recipients in the SMTP envelope rather than exposing them as message headers.
- **Per-user email templates**: Create, manage, and insert reusable templates from the rich-text composer. Template records and actions are scoped to their owner.
- **Snooze and return**: Hide a message until a chosen wake time, use quick snooze presets, and manage snoozed messages from a dedicated view.
- **Saved searches**: Save a search with its filters, revisit it from a personal saved-search library, and manage saved searches without sharing them across accounts.
- **Follow-up reminders**: Schedule an in-app reminder for a message, review due and upcoming reminders, and complete or dismiss them. Reminders are displayed in OpenMail; this release does not send push notifications for them.
- **Delivery activity**: Review a user-scoped, filterable history of delivery attempts and outcomes. Uncertain SMTP results are called out explicitly, and raw transport error details are not exposed in the user-facing history.
- **Explainable email security indicators**: Surface evidence-based sender, authentication-header, link, and attachment signals with meaningful limitations. These indicators are risk context, not a guarantee that a message is safe.
- **Keyboard-first command palette**: Navigate key mail workflows and access actions such as search and compose from a global command palette, with additional keyboard shortcuts.
- **Installable PWA shell**: Add web app metadata, an application icon, and a service worker for static application assets. Private mailbox content, authenticated pages, and message bodies are not cached by the service worker.
- **Responsive mailbox workspace**: Introduce a three-pane workspace and improve the responsive behavior of navigation, message lists, and the reader across desktop, tablet, and mobile sizes.
- **Expanded regression coverage**: Add and extend tests for scheduled delivery, cancellation, persisted MIME, template and search ownership, snooze/reminder lifecycles, delivery history, security signals, installer behavior, and responsive layout regressions.

### Changed

- **Runtime requirement**: PHP **8.4.1 or newer** is now required. This aligns the Composer constraints, locked Symfony dependencies, installer checks, CI, and deployment documentation. PHP 8.2 and 8.3 are not supported by this release.
- **Premium interface refresh**: Refine the mailbox, navigation bar, folder sidebar, message list, reader, composer, sign-in page, settings, setup wizard, and secondary productivity pages. Use more consistent spacing, visual hierarchy, focus states, controls, and responsive layouts.
- **More efficient folder counts**: Prefer a single IMAP STATUS request for folder count data, retaining fallback behavior for providers that do not return the expected status fields.
- **Stronger delivery state handling**: Use explicit send states, idempotency keys, stable Message-IDs, atomic worker claims, bounded processing batches, retry backoff, and stale-claim recovery to make queued delivery more resilient to retries and worker interruptions.
- **More reliable configuration writes**: Write .env changes atomically where the filesystem supports it and apply restrictive file permissions.
- **Clearer CI gates**: Run PHP formatting and dependency security audits as required checks rather than silently allowing those checks to fail. Add broader installation, mail transport, authorization, and security regression tests.
- **GitHub-native project automation**: Move continuous integration and release packaging to GitHub Actions, and keep the production archive workflow aligned with the bundled dependencies and compiled frontend assets.

### Security and privacy

- **No external AI analysis**: OpenMail 2.0 ships without AI/LLM features or an AI-provider integration. Email bodies and drafts are not sent to third-party AI services for analysis.
- **Outbound connection protection**: Harden IMAP/SMTP connection validation against unsafe network destinations, including loopback, private-network, and cloud metadata targets.
- **Consistent ownership checks**: Strengthen server-side ownership enforcement for user-managed templates, saved searches, snoozed messages, follow-up reminders, and delivery history, as well as existing mailbox actions.
- **Safer rendering and transport**: Continue to sanitize message HTML and URLs, block remote images until the user consents, protect Bcc recipients from message-header leakage, and avoid exposing raw transport diagnostics in normal UI responses.
- **Installation lock verification**: Do not treat installation as complete unless the installation marker can be written and read back successfully. If the filesystem refuses the write, report an actionable error instead of silently sending users back to the setup wizard.
- **Stricter dependency review**: Keep Composer and npm security audits in the CI quality gate.

### Fixed

- **Scheduled attachments**: Avoid persisting temporary upload file paths for messages that will be delivered later; deliver the durable MIME payload instead.
- **Bcc privacy**: Preserve Bcc delivery via the SMTP envelope without adding Bcc as a visible MIME header.
- **Scheduling consistency**: Make scheduling retries idempotent, synchronize rich-editor content before scheduling, keep overdue pending messages available for recovery, and show only appropriate cancellable items in the scheduled queue.
- **Template authorization errors**: Reject requests for templates owned by another user safely rather than returning a server error.
- **Saved-search filtering**: Preserve explicitly selected unread filters when saving and restoring searches.
- **Signature selection**: Resolve ties when promoting the default signature deterministically.
- **Mailbox responsiveness**: Fix a CSS cascade regression where desktop grid rules overrode the intended tablet/mobile mailbox layout.
- **Repeated setup prompt**: Remove the misleading setup-wizard prompt from the installed sign-in page, unify installation-state checks, and fail clearly if the installation marker cannot be persisted.

### Upgrade notes

- Back up the database, application files, .env, and persistent storage contents before upgrading.
- Confirm the host runs PHP 8.4.1 or newer before deploying OpenMail 2.0. The updated dependency lock is not compatible with PHP 8.2 or 8.3.
- Deploy the production release archive (which includes compiled frontend assets and production dependencies), preserve the existing .env and persistent storage, and run database migrations with php artisan migrate --force before using the upgraded application.
- Do not delete storage/installed as an upgrade step. It is the installation marker; deleting it makes the application enter first-install mode again.
- OpenMail remains a self-hosted IMAP/SMTP client. The PWA work provides an installable shell, not offline access to private mailbox content. Multi-account isolation, shared inboxes/roles, and other roadmap-only features are not claimed as part of this release.

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

[Unreleased]: https://github.com/eshanized/OpenMail/compare/v2.0.0...master
[2.0.0]: https://github.com/eshanized/OpenMail/compare/v1.0.1...v2.0.0
[1.1.0]: https://github.com/eshanized/OpenMail/compare/v1.0.1...master
[1.0.1]: https://github.com/eshanized/OpenMail/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/eshanized/OpenMail/releases/tag/v1.0.0
