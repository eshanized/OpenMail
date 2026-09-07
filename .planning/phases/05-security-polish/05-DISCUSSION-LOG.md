# Phase 5: Security & Polish - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-08
**Phase:** 5-Security & Polish
**Areas discussed:** CSP Policy Configuration, Audit Logging Scope & Storage, Theme & Density Settings, Signature Management

---

## CSP Policy Configuration

| Option | Description | Selected |
|--------|-------------|----------|
| Report-only first, then enforce | Start with Content-Security-Policy-Report-Only header to catch violations without breaking anything. Switch to enforce after monitoring. | ✓ |
| Direct enforce with nonce | Configure nonce-based CSP from start. Requires Vite integration for automatic nonce injection. | |
| Per-environment configs | Dev: permissive (unsafe-inline, unsafe-eval, HMR). Staging/Prod: strict with nonces. Different config files per environment. | |

**User's choice:** Report-only first, then enforce

---

| Option | Description | Selected |
|--------|-------------|----------|
| Nonce-based scripts/styles + allow inline for Livewire/Alpine | Use nonces for external scripts/styles. Allow 'unsafe-inline' for scripts/styles as fallback for Livewire/Alpine inline code. | ✓ |
| Strict nonces only, refactor inline code | Move all inline scripts/styles to external files. Use nonces exclusively. | |
| Hash-based for static inline, nonce for dynamic | Use 'sha256-<hash>' for known static inline scripts/styles. Nonces for dynamic content. | |

**User's choice:** Nonce-based scripts/styles + allow inline for Livewire/Alpine

---

| Option | Description | Selected |
|--------|-------------|----------|
| Log to Laravel log channel (security) | Reports POSTed to /csp-report endpoint, logged to security channel. Simple, no external service. | ✓ |
| External CSP reporting service | Send reports to report-uri.com, sentry.io, or similar. Better dashboard/alerting but adds external dependency. | |
| Both: log locally + forward to external | Redundancy — log locally for immediate debugging, forward to external for long-term tracking and alerts. | |

**User's choice:** Log to Laravel log channel (security)

---

| Option | Description | Selected |
|--------|-------------|----------|
| spatie/laravel-csp Vite integration (auto nonce) | Package has built-in Vite support. Generates nonce, injects into Vite-built assets, adds to CSP header automatically. | ✓ |
| Manual nonce middleware + Vite manifest | Custom middleware generates nonce, reads Vite manifest for script/style hashes. | |
| Separate CSP configs per environment | config/csp.php with dev/staging/prod arrays. Dev allows unsafe-inline/eval + HMR origins. Prod uses nonces. | |

**User's choice:** spatie/laravel-csp Vite integration (auto nonce)

**Notes:** Dev config will allow `unsafe-eval` and HMR origins; production uses nonces with `unsafe-inline` fallback for Livewire/Alpine.

---

## Audit Logging Scope & Storage

| Option | Description | Selected |
|--------|-------------|----------|
| Database table (audit_logs) | Structured, queryable, filterable in UI. Use DB sessions/cache driver (no Redis). Can build admin audit log viewer later. | ✓ |
| File-based (daily rotating logs) | Laravel's daily log files. Simpler, no DB schema. Harder to query/filter. | |
| Both: DB for recent (30 days) + file for archive | Hot data in DB for queries/UI, cold data archived to files. | |

**User's choice:** Database table (audit_logs)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Auth + Send actions (SEC-05 scope) | Login success/failure, lockout, send/reply/forward. Matches SEC-05 exactly. Minimal viable scope. | ✓ |
| Auth + Send + Settings changes | Add profile updates, signature changes, theme/density prefs, mail preferences. | |
| Full security events (Auth + Send + Settings + CSP + Rate limit) | Everything security-relevant. CSP violations, rate limit hits, failed IMAP/SMTP ops. | |

**User's choice:** Auth + Send actions (SEC-05 scope)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Standard fields + 90-day retention | id, user_id, event_type, description, ip, user_agent, metadata (JSON), created_at. Auto-prune >90 days via scheduler. | ✓ |
| Standard fields + 1-year retention | Same structure, longer retention for stricter compliance. | |
| Minimal fields + configurable retention | Only required fields (user_id, event_type, created_at). Retention set in settings table. | |

**User's choice:** Standard fields + 90-day retention

---

| Option | Description | Selected |
|--------|-------------|----------|
| AuditService + explicit calls in controllers/services | Central AuditService::log() called from LoginController, ComposerService, etc. Explicit, testable, clear where events originate. | ✓ |
| Model observers on User, MessageMetadata, Setting | Auto-log on created/updated/deleted. Less code but can be noisy. | |
| Middleware for route-based logging | Middleware logs all authenticated requests matching patterns. | |

**User's choice:** AuditService + explicit calls in controllers/services

**Notes:** Scheduler job will prune logs older than 90 days. Event types: login_success, login_failure, lockout, send, reply, forward.

---

## Theme & Density Settings

| Option | Description | Selected |
|--------|-------------|----------|
| Database (settings table or user_preferences) | Persists across devices/browsers. Settings table already exists. | ✓ |
| localStorage only | Instant, no server round-trip. Lost on device/browser change. | |
| Both: DB as source of truth, localStorage cache | Load from DB on login, cache in localStorage for instant apply. Sync back to DB on change. | |

**User's choice:** Database (settings table or user_preferences)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Class-based (dark: variant) | Add 'dark' class to <html> when dark mode. Tailwind's default. Works with Alpine.js toggle. Respects system preference via media query fallback. | ✓ |
| Media query only (prefers-color-scheme) | No manual toggle — only follows OS setting. | |
| Data attribute (data-theme="dark") | Use [data-theme="dark"] selector instead of .dark class. | |

**User's choice:** Class-based (dark: variant)

---

| Option | Description | Selected |
|--------|-------------|----------|
| CSS custom properties + Tailwind @apply | Define --spacing-unit, --font-size-base as CSS vars. Density changes var values. Tailwind utilities use vars via @apply. | ✓ |
| Separate Tailwind config per density | Generate different CSS builds or use @layer with density-specific utilities. | |
| Alpine.js toggles utility classes on container | Add 'density-compact'/'density-comfortable' class to mailbox container. | |

**User's choice:** CSS custom properties + Tailwind @apply

---

| Option | Description | Selected |
|--------|-------------|----------|
| Dedicated Settings page (tabs: Profile, Mail, Appearance, Security) | Full settings page with tabbed navigation. Appearance tab has theme toggle + density picker. | ✓ |
| Modal/drawer from user menu | Quick access from avatar dropdown. | |
| Both: quick toggle in menu, full page for density | Theme toggle in user menu (instant), density + other appearance settings on dedicated page. | |

**User's choice:** Dedicated Settings page (tabs: Profile, Mail, Appearance, Security)

**Notes:** System preference respected on first visit; user toggle persists to DB. Density changes CSS vars on `:root` with live preview in Settings.

---

## Signature Management

| Option | Description | Selected |
|--------|-------------|----------|
| Rich text (Tiptap) — same as composer | Full formatting, links, images. Reuses Tiptap config from composer. | ✓ |
| Plain text only | Simpler, no XSS risk, smaller storage. | |
| Both: rich text default, plain text fallback | Store both formats. Composer uses rich, plain text for text-only emails. | |

**User's choice:** Rich text (Tiptap) — same as composer

---

| Option | Description | Selected |
|--------|-------------|----------|
| Multiple signatures, one default | User can create many, mark one as default. Default auto-inserted on new compose. | ✓ |
| Single signature only | Simpler schema, one signature per user. | |
| Multiple, no default — manual select each time | User picks signature per compose. | |

**User's choice:** Multiple signatures, one default

---

| Option | Description | Selected |
|--------|-------------|----------|
| Auto-insert default on new compose; manual swap via dropdown | Default signature loads automatically. Dropdown in composer to switch/remove. Stores HTML in DB (sanitized). | ✓ |
| Insert on demand only (button in composer) | No auto-insert. User clicks 'Insert Signature' button. | |
| Per-account signatures (when multi-account lands) | Design schema for future multi-account: signatures belong to account, not user. | |

**User's choice:** Auto-insert default on new compose; manual swap via dropdown

---

| Option | Description | Selected |
|--------|-------------|----------|
| Settings page → Signatures tab (part of Appearance or separate) | List signatures, create/edit/delete, set default. Uses Tiptap in modal. | ✓ |
| Composer modal → signature dropdown → 'Manage signatures' | Edit signatures without leaving compose flow. | |
| Both: full management in Settings, quick edit in Composer | Settings for full CRUD, Composer for quick edit/insert. | |

**User's choice:** Settings page → Signatures tab (part of Appearance or separate)

**Notes:** Signature HTML sanitized via MessageSanitizer before storage. Tiptap config shared with composer.

---

## the agent's Discretion

- Exact CSP directive values (script-src, style-src, img-src, connect-src, font-src, frame-src)
- `audit_logs` table schema details (indexes, foreign keys, partitioning strategy)
- Scheduler frequency for audit log prune (daily vs weekly) and exact prune query
- CSS custom property names and Tailwind `@apply` utility mappings for density
- Settings page Livewire component organization and tab state management
- Signatures table schema and pivot if needed
- Composer signature dropdown component (Alpine.js) and insertion point in Tiptap editor
- Security headers middleware (SEC-10)
- Rate limiting extension to API endpoints (SEC-04)
- Session hardening completion (SEC-06)
- SSRF-safe URL handling (SEC-07)
- Attachment filename sanitization (SEC-08)
- MIME type validation (SEC-09)
- Profile display (SET-01)
- Mail preferences (SET-04)
- Session/logout controls (SET-05)

## Deferred Ideas

None — discussion stayed within phase scope.