# Phase 5: Security & Polish - Context

**Gathered:** 2026-09-08
**Status:** Ready for planning

<domain>
## Phase Boundary

The application is production-ready with security hardening and a polished user experience. This phase delivers CSP headers, rate limiting on API endpoints, comprehensive audit logging, light/dark theme toggle with system preference, density settings (compact/regular/comfortable), signature management (rich text, multiple with default), and profile/mail preferences UI via a dedicated Settings page.
</domain>

<decisions>
## Implementation Decisions

### CSP Policy Configuration
- **D-01:** Deploy CSP in report-only mode first, then enforce after monitoring violations — **Reversibility:** reversible — Can toggle header mode via config without code changes
- **D-02:** Nonce-based scripts/styles with `unsafe-inline` fallback for Livewire 3 and Alpine.js inline code — **Reversibility:** reversible — CSP directives configurable; removing fallback requires refactoring inline scripts
- **D-03:** CSP violation reports POSTed to `/csp-report` endpoint, logged to Laravel `security` log channel — **Reversibility:** reversible — Endpoint and log channel change only
- **D-04:** Use `spatie/laravel-csp` Vite integration for automatic nonce injection in production; dev config allows `unsafe-eval` and HMR origins — **Reversibility:** reversible — Config per environment

### Audit Logging Scope & Storage
- **D-05:** Store audit logs in database table `audit_logs` (uses existing DB sessions/cache driver, no Redis) — **Reversibility:** reversible — Schema via migration; dropping table is local
- **D-06:** Initial scope: Auth + Send actions only (login success/failure, lockout, send, reply, forward) — matches SEC-05 exactly — **Reversibility:** reversible — Adding categories is additive (new event_type values)
- **D-07:** Standard fields: id, user_id, event_type, description, ip, user_agent, metadata (JSON), created_at; 90-day retention with scheduler prune job — **Reversibility:** reversible — Retention period configurable in settings; prune job removable
- **D-08:** Central `AuditService::log()` called explicitly from LoginController, ComposerService, and other controllers/services — **Reversibility:** reversible — Service calls are local to call sites; observer/middleware alternatives not adopted

### Theme & Density Settings
- **D-09:** Store theme (light/dark/system) and density (compact/regular/comfortable) preferences in database `settings` table (per-user, keyed by user_id) — **Reversibility:** reversible — Settings table exists; key pattern change only
- **D-10:** Tailwind 4 class-based dark mode (`dark:` variant) — add `dark` class to `<html>` element; respects `prefers-color-scheme` as default — **Reversibility:** reversible — Class toggle via Alpine.js; media query fallback built-in
- **D-11:** Density implemented via CSS custom properties (`--spacing-unit`, `--font-size-base`, etc.) with Tailwind `@apply` utilities referencing vars — **Reversibility:** reversible — CSS var changes only; no Tailwind config rebuild needed
- **D-12:** Dedicated Settings page with tabbed navigation: Profile, Mail, Appearance, Security — Appearance tab contains theme toggle and density picker — **Reversibility:** reversible — Page structure change only

### Signature Management
- **D-13:** Rich text signatures using Tiptap editor (reuses Phase 3 composer configuration) — **Reversibility:** reversible — Editor config shared; plain-text fallback not implemented
- **D-14:** Multiple signatures per user, one marked as default — **Reversibility:** costly — Schema change (signatures table + pivot); removing requires data migration
- **D-15:** Default signature auto-inserted on new compose; dropdown in composer to swap/remove — **Reversibility:** reversible — Composer component logic change only
- **D-16:** Signature CRUD in Settings page → Signatures tab (under Appearance or separate); Tiptap in modal for create/edit — **Reversibility:** reversible — Settings page tab addition only

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

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements (SEC-01 through SEC-10, SET-01 through SET-06 for Phase 5)
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, Symfony Mailer, HTMLPurifier + DOMPurify, spatie/laravel-csp
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004 (especially ADR-003: Database-only infrastructure, ADR-004: Dual HTML sanitization)

### Phase 1 Decisions (Carried Forward)
- `.planning/phases/01-foundation-setup-wizard/01-CONTEXT.md` — Setup wizard patterns, IMAP/SMTP testing, auto-detection, session/auth defaults (progressive throttling, session rotation, 24hr lifetime, secure cookies)

### Phase 2 Decisions (Carried Forward)
- `.planning/phases/02-mailbox-core/02-CONTEXT.md` — Dual sanitization pipeline (HTMLPurifier server → DOMPurify client), remote image blocking, UUID-prefixed attachment filenames, IMAP service patterns

### Phase 3 Decisions (Carried Forward)
- `.planning/phases/03-compose-send/03-CONTEXT.md` — Tiptap editor patterns, ComposerService, draft autosave, contact autocomplete, IMAP APPEND to Sent/Drafts

### Phase 4 Decisions (Carried Forward)
- `.planning/phases/04-organization-intelligence/04-CONTEXT.md` — Search service, LabelService, ContactService, ThreadBuilder, tabbed sidebar patterns

### Existing Codebase Patterns
- `app/Services/MessageSanitizer.php` — HTMLPurifier + DOMPurify dual sanitization, remote image blocking — extend for signature content sanitization
- `app/Services/ComposerService.php` — Composer logic, Tiptap integration — extend for signature insertion
- `app/Http/Middleware/InstallLock.php` — Middleware pattern — reference for CSP/security headers middleware
- `app/Providers/AppServiceProvider.php` — RateLimiter config, IMAP auth guard — extend for API rate limiting
- `app/Livewire/LoginForm.php` — Progressive throttling implementation — reference for rate limiting patterns
- `app/Models/Setting.php` — Settings model with cache — use for theme/density/signature preferences
- `app/Models/User.php` — User model — extend for profile display fields
- `resources/views/layouts/mailbox.blade.php` — Mailbox layout with sidebar — extend for Settings page layout
- `resources/views/components/email-renderer.blade.php` — Sandboxed iframe with srcdoc — reference for CSP nonce handling
- `config/openmail.php` — IMAP/SMTP configuration — extend for CSP, security, appearance settings
- `vendor/spatie/laravel-csp` — CSP package with Vite integration — configure per decisions above

No external specs — requirements fully captured in decisions above.
</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **MessageSanitizer**: Dual sanitization pipeline (HTMLPurifier server → DOMPurify client) — apply to signature HTML content before storage/rendering
- **ComposerService**: Tiptap editor integration, draft autosave, signature insertion point — extend for signature dropdown and auto-insert
- **Setting model**: Key-value storage with cache — use for theme, density, signature preferences per user
- **AppServiceProvider RateLimiter config**: Per-IP and per-email limits — extend for API endpoints (compose, search, settings)
- **spatie/laravel-csp Vite integration**: Automatic nonce generation and injection — configure for report-only → enforce
- **LoginForm throttling**: Progressive delays (5s/30s/5min/15min) — pattern for API rate limiting
- **Mailbox layout**: `resources/views/layouts/mailbox.blade.php` — adapt for Settings page with tabbed sidebar
- **Database sessions/cache**: No Redis — use for audit log queries, settings cache, rate limiter storage

### Established Patterns
- Laravel 12 conventions for project structure, migrations, controllers, views
- Livewire 3 component patterns with `wire:navigate` for SPA-like navigation
- Blade templating with Tailwind 4 CSS-first configuration
- Alpine.js for lightweight interactivity (dropdowns, modals, toggles, theme toggle, density picker)
- Database sessions/cache (no Redis) — use for audit log queries, settings cache, rate limiter
- IMAP password encrypted in session via `Crypt::encrypt`/`decrypt`
- Message data extracted as simple types (not webklex objects) for Livewire serialization

### Integration Points
- **MailProvider interface** (from Phase 1) — clean abstraction for IMAP/SMTP operations
- **Authentication flow**: Login → session with encrypted IMAP password → mailbox routes
- **Database schema**: users, sessions, settings, folders, message_metadata, labels, contacts, pending_sends — extend with: `audit_logs`, `signatures`, `user_preferences` (or extend settings)
- **Routes**: `/mailbox` (authenticated) — extend with `/settings`, `/settings/appearance`, `/settings/signatures`, `/csp-report`
- **Views**: `layouts/mailbox.blade.php` for authenticated UI — Settings page mounts here
- **Services**: `ImapMailboxService`, `ComposerService`, `MessageSanitizer`, `SearchService`, `LabelService`, `ContactService` — add `AuditService`, `SignatureService`

</code_context>

<specifics>
## Specific Ideas

- Gmail/Outlook as UX benchmarks: CSP report-only monitoring, audit log query UI (future), theme toggle in header/user menu, density picker with live preview, signature management with rich text editor
- Security-first: CSP blocks inline scripts by default (nonce exception), audit logs capture security events without PII, rate limiting prevents brute force on all auth endpoints
- Shared hosting reality: No background workers, no Redis — scheduler (cron) for audit log prune, database for rate limiting storage, file-based CSP violation logs
- Theme toggle: Alpine.js on `<html>` class, persists to DB, respects system preference on first visit
- Density: CSS vars on `:root`, density classes on mailbox container, live preview in Settings
- Signatures: Tiptap editor in modal (reuses composer config), default badge, dropdown in composer toolbar

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.
</deferred>

---

*Phase: 5-Security & Polish*
*Context gathered: 2026-09-08*