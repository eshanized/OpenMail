# Project Research Summary

**Project:** OpenMail
**Domain:** Self-hosted company webmail application
**Researched:** 2026-09-05
**Confidence:** HIGH

## Executive Summary

OpenMail is a self-hosted webmail application built as a Laravel 12 monolith, targeting shared hosting (cPanel) deployment with a WordPress-like graphical setup wizard as its primary differentiator. Unlike existing webmail clients (Roundcube, SnappyMail, SOGo) that require config file editing or CLI access, OpenMail offers a graphical 8-step installation experience that makes it accessible to non-technical administrators. The application is a pure webmail interface — it connects to external IMAP/SMTP servers, not a mail server itself.

The recommended approach is a server-rendered architecture using Livewire 3 + Blade + Alpine.js + Tailwind CSS, avoiding SPA complexity while delivering dynamic UI. Critical architectural decisions include: (1) a MailProvider interface abstraction that decouples business logic from IMAP/SMTP implementation, (2) database-only infrastructure (no Redis) for shared hosting compatibility, (3) dual HTML sanitization (HTMLPurifier + DOMPurify) to address the August 2026 Black Hat CSS injection attacks against email content, and (4) session-only credential handling to avoid storing mailbox passwords.

The key risks are: email HTML rendering XSS (the most dangerous attack surface), shared hosting deployment failures, IMAP UID identity mismatches, and IMAP connection exhaustion. All have documented mitigation strategies. The product should launch with core webmail features (read, compose, reply, folders, search, contacts) plus the setup wizard, with threading, keyboard shortcuts, Sieve filters, and labels added after validation.

## Key Findings

### Recommended Stack

OpenMail uses a production-proven Laravel stack optimized for shared hosting constraints. All external services (Redis, Elasticsearch, queue workers) are eliminated in favor of database drivers.

**Core technologies:**
- **Laravel 12.x**: Backend framework — stable, security-supported until Feb 2027, avoids immature Laravel 13
- **PHP 8.3+**: Runtime — typed constants, readonly classes, performance improvements
- **Livewire 3.x**: Dynamic UI — built-in Alpine.js, SPA-like navigation via `wire:navigate`, no JS framework overhead
- **Blade + Tailwind CSS 4.x**: Templating and styling — Laravel's native stack, CSS-first Tailwind config
- **Alpine.js 3.x**: Client interactivity — included with Livewire, handles dropdowns/modals
- **Vite 6.x**: Asset bundling — Laravel's default, HMR in dev, optimized production builds
- **webklex/php-imap 6.x**: IMAP client — pure PHP (no ext-imap), supports IDLE, OAuth, folder management
- **Symfony Mailer**: SMTP sending — Laravel's built-in mail transport, no additional package
- **php-mime-mail-parser 10.x**: MIME parsing — highest performance, requires mailparse PECL extension (or zbateson/mail-mime-parser as pure PHP fallback)
- **HTMLPurifier + DOMPurify**: Dual HTML sanitization — server-side defense-in-depth + client-side primary control
- **Laravel Scout (database engine)**: Search — MySQL FULLTEXT indexes, zero external services
- **Database sessions/cache**: No Redis dependency, compatible with shared hosting

### Expected Features

**Must have (table stakes):**
- Read messages (HTML + plain text, sandboxed iframe rendering)
- Compose messages (To/CC/BCC, subject, body, attachments)
- Reply / Reply All / Forward
- Folder navigation (Inbox, Sent, Drafts, Trash, Spam)
- Message list (sender, subject, timestamp, read/unread, flags)
- Attachment download/upload (UUID-based filenames, out of web root)
- Search (IMAP-native minimum, application-level indexing later)
- Contacts / address book (recent recipients, autocomplete)
- Mark read/unread, delete, move between folders
- Draft auto-save
- Login/logout with session management
- Responsive design (desktop-first, mobile-usable)
- Security hardening (HTML sanitization, CSP, rate limiting, session hardening)

**Should have (differentiators):**
- **Graphical setup wizard (8-step)** — killer differentiator, no competitor offers this
- Message threading (JWZ algorithm, header-based, not subject-based)
- Keyboard shortcuts (Gmail muscle memory: c, r, d, j/k, e)
- Sieve filter management (visual rule builder + raw editor)
- Dark/light theme toggle
- Labels/tags (Gmail-style, application-level metadata)
- Archive function, undo send, drag-and-drop
- Bulk selection + actions, right-click context menu
- Email signatures, quota display, audit logging

**Defer (v2+):**
- Multi-account support (unified inbox across multiple mailboxes)
- OAuth2/SSO/LDAP (enterprise identity integration)
- Calendar/tasks (groupware — different product category)
- PWA/offline support
- Multi-tenancy (SaaS model)
- Plugin system
- Email scheduling, read receipts, collaborative features
- AI features (privacy concerns for self-hosted email)

### Architecture Approach

OpenMail follows a three-tier architecture: Presentation (Blade/Livewire/Alpine), Application (Services/Actions), Domain (MailProvider interface + entities), Infrastructure (IMAP/SMTP/Database). The key architectural decision is the **MailProvider interface** that abstracts all mail operations — the ImapSmtpProvider implements this interface, but future providers (OAuth, API) can swap in without rewriting consumers. The project is domain-organized (app/Mail, app/Auth, app/Install, app/Contacts, app/Security), not type-organized. Livewire components are thin presentation-layer controllers that delegate to service/action classes. Email HTML is always rendered in sandboxed iframes with strict CSP. The IMAP server is external and authoritative — OpenMail stores only application metadata (preferences, contacts, search indexes) in MySQL.

**Major components:**
1. **Mail Domain** — MailProvider interface, IMAP/SMTP implementation, Message/Folder/Thread entities, MailReader/MailSender/MailSearch services
2. **Install Subsystem** — 8-step setup wizard with requirement checking, database setup, IMAP/SMTP testing, installation lock
3. **Auth Domain** — Login/logout, session management, IMAP credential validation
4. **Security Domain** — HTML sanitization (HTMLPurify + DOMPurify), CSP headers, rate limiting, audit logging
5. **Contacts Domain** — Address book, autocomplete, recent recipients
6. **Settings Domain** — User preferences, display density, theme toggle

### Critical Pitfalls

1. **Email HTML XSS / CSS Injection** — Email content is the most hostile input surface on the internet. August 2026 Black Hat research showed CSS-only attacks bypass sanitization. Prevention: render in sandboxed iframe with `sandbox="allow-same-origin"` (no `allow-scripts`), strict CSP inside iframe, DOMPurify as primary sanitizer, strip `<style>` tags entirely, whitelist safe CSS properties only.

2. **IMAP UID as Sole Message Identity** — UIDs are folder-specific, change on UIDVALIDITY reset, and are not unique across folders. Prevention: use composite identity `(folder_path, uid)`, cross-reference with Message-ID header for deduplication, track UIDVALIDITY per folder.

3. **Attachment Filename Path Traversal** — Attackers send attachments named `../../shell.php`. CVE-2023-35169 pattern. Prevention: generate UUID-based filenames, store original name in DB only, validate resolved path stays within storage directory, use `finfo_file()` for MIME validation, store outside web root.

4. **Shared Hosting Deployment Failures** — Wrong document root, unwritable storage/, missing APP_KEY, missing PHP extensions. Prevention: setup wizard must check PHP version/extensions, verify writable directories, generate APP_KEY, test database connection, verify document root points to `public/`.

5. **IMAP Connection Exhaustion** — Each HTTP request opening new IMAP connections exhausts server limits (Dovecot default: 64 per user). Prevention: singleton/request-scoped connection pool, reuse across Livewire updates, cache folder metadata with 30-60 second TTL, set explicit timeouts.

## Implications for Roadmap

Based on research, suggested phase structure:

### Phase 1: Foundation & Setup Wizard
**Rationale:** Everything depends on the MailProvider interface being designed and the installation wizard working. The setup wizard is the first user encounter and the primary differentiator. Shared hosting deployment must be validated early.
**Delivers:** Laravel project scaffold, MailProvider interface + ImapSmtpProvider implementation, database schema (with composite identity from day one), authentication system, 8-step setup wizard with requirement checking and installation lock.
**Addresses:** Setup wizard (P1 differentiator), authentication (P1), IMAP/SMTP provider abstraction (P1), shared hosting compatibility (P1).
**Avoids:** Pitfall #3 (SMTP port/encryption mismatch — wizard validates pairs), Pitfall #4 (attachment path traversal — UUID filenames from day one), Pitfall #5 (credential storage — session-only architecture), Pitfall #6 (shared hosting 500 errors — wizard checks everything), Pitfall #9 (setup wizard re-entrancy — installation lock outside web root).
**Stack:** Laravel 12, PHP 8.3, MySQL, Composer, webklex/php-imap, Symfony Mailer.
**Research flag:** HIGH — shared hosting deployment testing on actual cPanel environment needed.

### Phase 2: Mailbox Core
**Rationale:** With the foundation in place, build the core user-facing features: reading and navigating email. This phase validates the MailProvider interface in real usage and establishes the HTML sanitization pipeline.
**Delivers:** Folder navigation, message list (paginated, 25 per page), message viewer (HTML + plain text in sandboxed iframe), attachment download (UUID filenames, out of web root), attachment upload in compose.
**Addresses:** Folder navigation (P1), message list (P1), message viewer (P1), attachment handling (P1), responsive design (P1).
**Avoids:** Pitfall #1 (email HTML XSS — 4-layer defense: DOMPurify + sandboxed iframe + CSP + no dangerouslySetInnerHTML), Pitfall #7 (IMAP connection leak — singleton connection pool), Pitfall #10 (CSS injection — strip `<style>`, whitelist safe properties).
**Stack:** Livewire 3 components, Blade templates, DOMPurify (client-side), HTMLPurifier (server-side defense-in-depth).
**Architecture:** Thin Livewire components delegate to MailReader service via MailProvider interface.
**Research flag:** MEDIUM — DOMPurify integration with Livewire needs validation.

### Phase 3: Compose & Send
**Rationale:** After reading email works, build sending capabilities. This completes the core email loop and tests the SMTP transport path.
**Delivers:** Compose (To/CC/BCC, subject, body, attachments), Reply / Reply All / Forward, Delete / Move messages, Draft auto-save (IMAP APPEND to Drafts), Signatures, Send to Sent folder after SMTP.
**Addresses:** Compose (P1), Reply/Forward (P1), Delete/Move (P1), Draft auto-save (P1), Signatures (P2).
**Stack:** Symfony Mailer (SMTP), Livewire compose component, FormRequest validation.
**Research flag:** LOW — standard Laravel mail patterns, well-documented.

### Phase 4: Search & Organization
**Rationale:** With the core email loop working, add features that make OpenMail competitive: search, contacts, threading, and labels. These depend on message data flowing correctly.
**Delivers:** Basic search (IMAP-native), Contacts (recent recipients, autocomplete, address book), Message threading (JWZ algorithm, header-based), Labels/tags (application-level metadata in MySQL).
**Addresses:** Search (P1), Contacts (P1), Threading (P2), Labels (P2), Sieve filters (P2).
**Avoids:** Pitfall #8 (thread reconstruction — header-based threading, not subject-based).
**Stack:** Laravel Scout (database engine), MySQL FULLTEXT indexes, JWZ threading algorithm.
**Architecture:** ThreadingService builds thread tree from Message-ID/In-Reply-To/References headers.
**Research flag:** MEDIUM — JWZ threading implementation needs careful edge case handling.

### Phase 5: Security Hardening & Polish
**Rationale:** Security is pervasive and must be layered on after core features work but before launch. This phase adds the remaining security controls and UX polish.
**Delivers:** CSP headers (spatie/laravel-csp), rate limiting (login + IMAP operations), audit logging, CSS sanitization rules, keyboard shortcuts, dark/light theme, bulk actions, context menus, density settings.
**Addresses:** CSP (P1), rate limiting (P1), audit logging (P2), keyboard shortcuts (P2), theme toggle (P2), bulk actions (P2).
**Avoids:** Pitfall #10 (CSS injection — strict property whitelist), Pitfall #7 (IMAP connection exhaustion — rate limiting).
**Stack:** spatie/laravel-csp, DOMPurify CSS rules, Alpine.js for keyboard shortcuts.
**Research flag:** LOW — standard Laravel security patterns.

### Phase Ordering Rationale

- **Foundation first** because MailProvider interface is the architectural spine — everything depends on it. The setup wizard validates shared hosting compatibility early.
- **Mailbox second** because reading email is the primary use case and validates the IMAP connection architecture.
- **Compose third** because it depends on MailProvider::send() and the SMTP path, which is simpler than the read path.
- **Search & Organization fourth** because it depends on message data flowing correctly and threading requires Message-ID parsing from Phase 1.
- **Security last** because it's a cross-cutting concern layered on top of working features, but before launch.

This ordering avoids the critical pitfalls by addressing identity model (Phase 1), HTML sanitization (Phase 2), connection management (Phase 2), and deployment validation (Phase 1) before they cause problems.

### Research Flags

Phases likely needing deeper research during planning:
- **Phase 1:** Shared hosting deployment testing on actual cPanel — must validate PHP extensions, permissions, .env handling, Composer vendor directory
- **Phase 2:** DOMPurify integration with Livewire — validate sandboxed iframe rendering in Livewire components, nonce handling with CSP
- **Phase 4:** JWZ threading algorithm implementation — edge cases with missing Message-ID, duplicate headers, cross-client threading

Phases with standard patterns (skip research-phase):
- **Phase 3:** Standard Laravel mail sending patterns, well-documented
- **Phase 5:** Standard Laravel security patterns (CSP, rate limiting, audit logging)

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Laravel 12 + Livewire 3 + Tailwind 4 is the officially recommended stack. All packages are production-proven with active maintenance. Version compatibility confirmed. |
| Features | HIGH | Competitive analysis against Roundcube, SnappyMail, SOGo, Bulwark is thorough. Table stakes are well-established in the webmail domain. Feature dependencies mapped. |
| Architecture | MEDIUM | Three-tier with Provider Abstraction is well-documented for Laravel. JWZ threading and IMAP connection pooling need implementation validation. Domain-organized structure is community consensus but not universally adopted. |
| Pitfalls | HIGH | 10 pitfalls identified with specific prevention strategies. CVE references (CVE-2023-35169, CVE-2023-43770) and 2026 Black Hat research provide concrete evidence. Phase mapping is clear. |

**Overall confidence:** HIGH

### Gaps to Address

- **Shared hosting testing:** Must deploy to actual cPanel environment during Phase 1 to validate all wizard checks. VPS testing is insufficient.
- **IMAP connection pooling:** Singleton connection architecture needs implementation validation with webklex/php-imap — the library creates new connections per Client instance.
- **Livewire + iframe sandbox:** Rendering email in sandboxed iframes within Livewire components needs architectural validation — Livewire's DOM diffing may conflict with iframe isolation.
- **MIME parser choice:** Must decide between php-mime-mail-parser (needs mailparse PECL extension) vs zbateson/mail-mime-parser (pure PHP, slower) based on target hosting capabilities. This should be validated during Phase 1.
- **Draft auto-save behavior:** Decide between local-only draft (lost if browser closes) vs IMAP APPEND to Drafts (requires IMAP connection). May need both: local-first, sync to IMAP periodically.

## Sources

### Primary (HIGH confidence)
- Laravel releases page (laravel.com/docs/releases) — version support matrix confirmed
- Packagist: webklex/laravel-imap 6.2.0 — IMAP library version and features
- Packagist: php-mime-mail-parser 10.0.0 — MIME parser PHP 8.2+ requirement
- OWASP AppSec USA 2024: "Why Server-Side HTML Sanitization Fails" — HTMLPurifier vulnerability research
- Laravel Scout docs (laravel.com/docs/scout) — database engine documentation
- Black Hat USA 2026: Gareth Heyes CSS attacks research — email CSS injection vectors
- CVE-2023-35169: php-imap path traversal → RCE via attachment filename
- CVE-2023-43770: Roundcube XSS through plain text email links

### Secondary (MEDIUM confidence)
- Panelica.com: Roundcube vs SnappyMail vs SOGo comparison (2026)
- selfhosting.sh: Best Self-Hosted Webmail Clients (2026)
- DeployHQ: cPanel Laravel deployment patterns (2026-07)
- Laravel modular monolith patterns (Laracasts, 200OK, WireFuture)
- ImapEngine docs (imapengine.com) — alternative IMAP library evaluation

### Tertiary (LOW confidence)
- Tachyon Webmail feature matrix — newer product, less community validation
- Stalwart + Bulwark analysis (devopspack.com) — JMAP-focused, different architecture

---
*Research completed: 2026-09-05*
*Ready for roadmap: yes*
