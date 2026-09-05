# Phase 1: Foundation & Setup Wizard - Context

**Gathered:** 2026-09-05
**Status:** Ready for planning

<domain>
## Phase Boundary

Deploy and log in — the setup wizard guides administrators through installation, and users can authenticate via IMAP. This phase delivers the project scaffold, MailProvider interface, database schema, authentication system, and the 8-step setup wizard.

</domain>

<decisions>
## Implementation Decisions

### Setup Wizard Flow
- **D-01:** Linear 8-step wizard: 1) Welcome/detect install 2) System requirements 3) Database config 4) Mail config (IMAP/SMTP) 5) App settings 6) Admin account 7) Security defaults 8) Verify & finish — **Reversibility:** reversible — Adding/removing/reordering steps is local to wizard component
- **D-02:** Inline error handling — show error below the failing field, let admin fix and retry without losing other step data — **Reversibility:** reversible
- **D-03:** Resume from last step on return — save progress in DB/session, on return ask "Continue from step X?" or "Start over" — **Reversibility:** reversible
- **D-04:** Full verification suite at final step — test DB, IMAP, SMTP, filesystem, PHP config, encryption with green/red status per check, "Fix issues" links back to relevant step — **Reversibility:** reversible

### IMAP/SMTP Testing
- **D-05:** Full connection test — connect, authenticate, list folders (IMAP) / verify server accepts auth (SMTP). Confirms everything works end-to-end — **Reversibility:** reversible
- **D-06:** User-friendly error display with expandable technical details — simple message ("Cannot connect to mail server") with optional expandable section showing full error — **Reversibility:** reversible
- **D-07:** Retry in place — keep form filled after failure, let admin fix and hit "Test again", no data loss — **Reversibility:** reversible
- **D-08:** SMTP test is connection-only — verify SMTP server accepts auth and is reachable, no email sent — **Reversibility:** reversible

### Auto-Detection Logic
- **D-09:** Auto-detect Big 3 providers + generic fallback — Gmail, Outlook/365, Yahoo with pre-filled settings; generic mail.domain.com / smtp.domain.com for unknown providers — **Reversibility:** reversible — Adding/removing providers is configuration, not code change
- **D-10:** Pre-fill from email domain — admin enters email address, system detects provider from domain (e.g., user@gmail.com → Gmail), pre-fills host/port/encryption, admin can override — **Reversibility:** reversible
- **D-11:** Unknown domains get generic fields pre-filled — show mail.domain.com / smtp.domain.com defaults with placeholder text for remaining fields — **Reversibility:** reversible
- **D-12:** Default encryption is SSL/TLS — use port 993 (IMAP) / 465 (SMTP) with SSL/TLS — **Reversibility:** reversible — Changing default port/encryption is config, but existing installs may need migration

### Session & Auth Defaults
- **D-13:** Session lifetime is 24 hours — standard for webmail, balances security with convenience — **Reversibility:** reversible — Changing lifetime is config, existing sessions unaffected
- **D-14:** Progressive login throttling — 5s after 3rd fail, 30s after 5th, 5min after 7th, lock for 15min after 10th — **Reversibility:** reversible — Thresholds are configurable
- **D-15:** Cookie defaults: HttpOnly + SameSite=Lax — HttpOnly prevents XSS access, SameSite=Lax prevents CSRF, Secure flag added when HTTPS detected — **Reversibility:** reversible — Changing cookie flags is config
- **D-16:** Admin account: strong password (8+ chars, mixed case, number) + valid email — email used for login, no other restrictions — **Reversibility:** reversible — Password policy is configurable

### Agent's Discretion
- Database schema design — researcher and planner determine table structure, indexes, migrations based on requirements
- Laravel project structure — follow standard Laravel conventions
- Livewire component organization — follow existing patterns

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements (SETUP-01 through SETUP-12, AUTH-01 through AUTH-08, DB-01, DB-02, DB-05 for Phase 1)
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, Symfony Mailer, HTMLPurifier + DOMPurify
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004

No external specs — requirements fully captured in decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- No existing code — this is Phase 1 (project scaffold)

### Established Patterns
- Laravel 12 conventions for project structure, migrations, controllers, views
- Livewire 3 component patterns for dynamic UI
- Blade templating with Tailwind CSS for styling
- Alpine.js for lightweight interactivity

### Integration Points
- Database schema (DB-01, DB-02, DB-05) — MySQL/MariaDB for sessions, settings, admin accounts
- MailProvider interface — clean abstraction for IMAP/SMTP (webklex/php-imap for IMAP, Symfony Mailer for SMTP)
- Setup wizard → Authentication flow → Login page

</code_context>

<specifics>
## Specific Ideas

- WordPress-style setup wizard philosophy: "Upload → Visit → Configure → Finish"
- Installation lock prevents re-running setup on existing installation
- Resumable installation state (browser close preserves progress)
- Admin account created during wizard is the initial admin user
- Mail configuration tested in wizard before proceeding to next step

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 1-Foundation & Setup Wizard*
*Context gathered: 2026-09-05*
