# OpenMail 2.0.0 Implementation Roadmap

## Runtime compatibility change

OpenMail 2.0.0 requires PHP 8.4.1 or newer. The existing dependency lock contains Symfony 8.1 packages requiring PHP 8.4.1, so the previously advertised PHP 8.2/8.3 range could not install the committed dependencies in CI. The installer, Composer manifest, CI matrix, and deployment documentation must agree on this minimum. Administrators should confirm their hosting provider supports PHP 8.4.1 before upgrading.

## Product goal

Evolve OpenMail from a secure, self-hosted IMAP/SMTP webmail interface into a more productive organizational email workspace without abandoning its deployment constraints: Laravel monolith, MySQL/MariaDB, support for cPanel/shared hosting, no required Redis, and no permanently running worker for basic mail operations.

This document is a target scope, not a claim that every item is already implemented. Features should be marked complete only after code review, automated tests, and end-to-end verification.

## Workstream 0 — Release baseline and audit (P0)

- Reconcile README and changelog claims against actual implementation and tests.
- Resolve stale checked/unchecked v1 requirements and record an evidence-backed status.
- Establish database fixtures for fresh installation and upgrades from 1.1.0.
- Preserve mandatory Pint, dependency audits, PHP-version matrix, browser build, and Pest CI gates.
- Add end-to-end email tests against a controllable SMTP server and IMAP test service.
- Capture baseline performance for folder listing, search, threading, large messages, and attachments.

Acceptance: no critical/high regression is knowingly waived; CI reports real failures rather than suppressing them; install and upgrade paths are repeatable.

## Workstream 1 — Mail productivity (P0)

- Durable send-later scheduling, user-visible scheduled queue, cancellation, and draft recovery.
- Snooze and follow-up reminders with explicit folder/UID identity and safe wake-up behavior.
- User-managed email templates and reusable signatures.
- Saved searches, configurable inbox views, bulk operations, keyboard shortcuts, and command palette.
- Per-message and per-thread actions with accessible status/error feedback.

Acceptance: schedule and snooze state survives restarts; cancelled messages do not dispatch; retries and concurrent workers remain safe; stale UID handling never changes the wrong message.

## Workstream 2 — Multiple accounts and synchronization (P0)

- Account registry per user with encrypted credentials and connection health.
- Account-scoped folders, drafts, signatures, message metadata, search, attachments, and pending delivery.
- Safe migration of existing users to a default account without credential loss.
- Incremental refresh, reconnect/backoff, and an observable sync-health state.
- IMAP IDLE only as an optional optimization; retain polling/refresh fallback for shared hosting.

Acceptance: cross-account reads and writes are denied server-side; account switch does not leak data through cached UI, search, attachment URLs, or background jobs.

## Workstream 3 — OpenMail Intelligence (P0)

- Provider abstraction for configurable remote APIs and optional local model endpoints.
- Thread summaries, editable reply drafts, tone rewrite, translation, and action-item extraction.
- Explicit per-provider enablement, payload disclosure, timeouts, bounded input/output size, and redacted diagnostics.
- Treat inbound mail content as untrusted prompt input and never expose tools capable of sending/deleting messages to model output.
- Never send generated email automatically; require user review and explicit send.

Acceptance: tests cover provider failures, malformed output, prompt-injection content, privacy settings, and maximum payload sizes. AI is optional and core mailbox functionality remains usable without it.

## Workstream 4 — Trust and security center (P0)

- Present sender authentication results (SPF/DKIM/DMARC) only when trustworthy evidence is available in headers or a verified provider API.
- Explain reply-to/from mismatches, external sender context, suspicious URL patterns, and risky attachments.
- Preserve remote-image blocking and user consent behavior.
- Add clear session history, account security diagnostics, and safe audit-log inspection.
- Do not represent a passing authentication signal as proof a message is benign.

Acceptance: security signals include evidence and limitations; rendering and URL/attachment tests include adversarial fixtures; no diagnostics expose mailbox credentials or message bodies.

## Workstream 5 — Shared inbox and collaboration (P1)

- Shared addresses such as support/sales with explicit membership.
- Conversation assignment, status tracking, internal notes, collision warnings, and assignment history.
- Roles and policies separating personal mailboxes from shared mailbox operations.
- Basic team metrics using minimally necessary metadata.

Acceptance: every shared-mail action is authorized server-side; internal notes cannot enter outbound MIME; memberships and audit events are tested for cross-user access.

## Workstream 6 — UX, PWA, and accessibility (P1)

- Unified three-pane desktop workspace and adaptive mobile layouts.
- Keyboard-first command palette, shortcut help, focus management, and screen-reader labels.
- Installable PWA shell and optional browser notifications. The first slice includes a manifest, app icon, and static-asset service worker; authenticated documents and mailbox content are intentionally never cached.
- Resilient draft editing and clear online/offline/sync state.
- Do not claim full offline mailbox operation unless storage, encryption, revocation, and conflict resolution are actually implemented.

Acceptance: automated browser journeys cover critical flows at mobile and desktop widths; no horizontal overflow, inaccessible primary actions, or uncaught browser-console errors in the supported matrix.

## Workstream 7 — Administration and operations (P0)

- User/role management appropriate to a single organization.
- Delivery queue inspection with retry/uncertain outcome information. A user-scoped, read-only delivery history is implemented; automatic retries of uncertain SMTP outcomes remain intentionally unsupported.
- Installation and upgrade diagnostics, health checks, retention settings, and safe operational logs.
- Keep required deployment dependencies compatible with shared hosting.

Acceptance: diagnostics are actionable and redact secrets; upgrade and backup/restore procedures are documented and rehearsed.

## Later 2.x candidates

- Gmail API and Microsoft Graph provider implementations.
- OAuth/SSO/LDAP/SCIM after user and role administration stabilizes.
- Calendar and standards-based contact synchronization.
- Multi-tenant SaaS deployment.
- End-to-end encryption only after a complete key/recovery/search architecture is designed.

## Release gates for 2.0.0

- Fresh install and supported 1.1.0 upgrade succeed on supported MySQL/MariaDB and PHP combinations.
- Required CI, static analysis, dependency audit, build, and automated tests pass.
- Email behavior is exercised across schedule, cancel, retry, uncertain SMTP outcomes, attachments, threading, and Bcc privacy.
- Cross-user and cross-account authorization tests cover every new entity.
- Desktop and mobile browser workflows pass against realistic test data.
- README, setup docs, changelog, compatibility matrix, and release artifacts match the shipped implementation.

## Current execution note

Implemented slices now include durable send-later with cancellation, per-user email templates, snooze/return, persisted follow-up reminders with due/upcoming states, saved searches, user-scoped delivery activity, command-palette navigation, a static-only PWA shell that never caches private mail, opt-in AI summaries and editable reply drafts, and evidence-based message-security indicators. Follow-up reminders are surfaced in OpenMail and do not yet send push notifications. Multi-account isolation, shared inboxes/roles, richer synchronization, admin diagnostics, bulk operations, saved inbox views, broader AI actions, browser-level accessibility verification, and complete release verification remain incomplete; this roadmap is not a claim that OpenMail 2.0 is finished.
