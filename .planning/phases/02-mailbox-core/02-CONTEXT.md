# Phase 2: Mailbox Core - Context

**Gathered:** 2026-09-05
**Status:** Ready for planning

<domain>
## Phase Boundary

Users can browse folders and read messages — the core mailbox experience works end-to-end. This phase delivers folder navigation, message list with pagination/bulk selection, message viewer with sanitized HTML rendering, attachment handling, and local metadata caching for performance.

</domain>

<decisions>
## Implementation Decisions

### Folder Navigation & Mapping
- **D-01:** Use IMAP XLIST/SEARCH SPECIAL-USE (RFC 6154) as primary method to map folders to standard roles (\\Inbox, \\Sent, \\Drafts, \\Trash, \\Junk, \\Archive), with heuristic name matching as fallback — **Reversibility:** reversible — Changing detection logic is local to folder service
- **D-02:** Hierarchical folder tree with expand/collapse for nested folders — **Reversibility:** reversible — UI component change only
- **D-03:** Direct IMAP operations (CREATE/RENAME/DELETE) for custom folder mutations — **Reversibility:** reversible — IMAP command change only
- **D-04:** Cached unread/total counts in DB (DB-03) with periodic IMAP STATUS refresh (e.g., every 30s) and on folder switch — **Reversibility:** reversible — Cache strategy change only

### Message List Fetching & Pagination
- **D-05:** IMAP SEARCH + SORT (server-side) for filtering and ordering; fetch only the needed page — **Reversibility:** reversible — Fetch strategy change in message list service
- **D-06:** Headers-only fetch (BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE)]) + first ~200 chars of body for snippet — **Reversibility:** reversible — Fetch fields change only
- **D-07:** IMAP \\Seen flag as source of truth for read/unread state; no local mirror — **Reversibility:** reversible — State source change only
- **D-08:** Map IMAP SORT criteria to friendly UI labels: Date (newest), Sender, Subject, Size — **Reversibility:** reversible — UI label mapping change only

### Message Viewer & HTML Sanitization
- **D-09:** Sandboxed iframe via `srcdoc` attribute for sanitized HTML rendering — **Reversibility:** reversible — Iframe rendering approach change only
- **D-10:** Dual sanitization pipeline: HTMLPurifier (server-side) → DOMPurify (client-side at render) — **Reversibility:** reversible — Sanitization pipeline change; matches ADR-004 from Phase 1
- **D-11:** Block remote images by default; show "Display images" banner for user opt-in per message — **Reversibility:** reversible — Image handling policy change only
- **D-12:** Stream attachments directly from IMAP on download (FETCH BODY.PEEK[part]); no local caching — **Reversibility:** reversible — Attachment delivery change only

### Local Metadata Caching & Sync
- **D-13:** Store core metadata per message: UID, Message-ID, folder, flags (\\Seen, \\Flagged), date, from, to, subject, snippet — **Reversibility:** reversible — Schema change via migration
- **D-14:** Cache invalidation via UIDVALIDITY per folder + periodic UID FETCH (FLAGS) for flag changes; full resync on UIDVALIDITY mismatch — **Reversibility:** reversible — Sync strategy change only
- **D-15:** Sync metadata on folder open (blocking or async) + background periodic refresh (every 2-5 min) for current folder — **Reversibility:** reversible — Sync trigger change only

### Bulk Selection & Actions
- **D-16:** Alpine.js manages selection state (checkbox, shift-click, select all) client-side; Livewire handles bulk actions (move, delete, flag, mark read/unread) — **Reversibility:** reversible — Selection/action pattern change only
- **D-17:** Single IMAP STORE/COPY command with UID set (e.g., 1,2,3 or 1:10) for bulk operations — **Reversibility:** reversible — IMAP command batching change only
- **D-18:** Gmail-style toolbar above message list when messages selected: Archive, Delete, Spam, Move to..., Mark read/unread, Flag — **Reversibility:** reversible — UI component change only

### the agent's Discretion
- Database schema design for message metadata table (indexes, foreign keys) — researcher and planner determine
- Livewire component organization for mailbox (folder sidebar, message list, message viewer) — follow existing patterns
- Background sync implementation (scheduler vs manual trigger) — choose based on shared hosting constraints
- Exact IMAP FETCH fields for snippet extraction — optimize for common MIME structures

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements (MAIL-01 through MAIL-04, MSG-01 through MSG-07, VIEW-01 through VIEW-10, DB-03, DB-04 for Phase 2)
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, Symfony Mailer, HTMLPurifier + DOMPurify
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004 (especially ADR-004: Dual HTML sanitization)

### Phase 1 Decisions (Carried Forward)
- `.planning/phases/01-foundation-setup-wizard/01-CONTEXT.md` — Setup wizard patterns, IMAP/SMTP testing, auto-detection, session/auth defaults

### Existing Codebase Patterns
- `app/Livewire/SetupWizard.php` — 8-step wizard with session resume, inline validation, IMAP/SMTP testing patterns
- `app/Livewire/LoginForm.php` — IMAP authentication guard, progressive throttling, encrypted password in session
- `app/Services/ImapConnectionTester.php` — webklex/php-imap client usage, folder listing
- `app/Services/MailConfigDetector.php` — Provider auto-detection logic
- `config/openmail.php` — IMAP/SMTP configuration structure
- `resources/views/layouts/app.blade.php` — Main app layout with navigation
- `resources/views/livewire/login-form.blade.php` — Livewire + Alpine form patterns

No external specs — requirements fully captured in decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **SetupWizard component pattern**: Multi-step state management, session persistence, inline validation with error display — apply to folder/message list state
- **ImapConnectionTester service**: webklex/php-imap client initialization, connection, folder listing — reuse for folder navigation and message fetching
- **MailConfigDetector service**: Provider auto-detection — extend for SPECIAL-USE folder detection
- **LoginForm IMAP auth**: Custom guard authentication, encrypted password storage in session — reuse for per-request IMAP connections
- **Livewire + Alpine form patterns**: wire:model, wire:click, wire:loading, Alpine x-data/x-show — established UI patterns

### Established Patterns
- Laravel 12 conventions for project structure, migrations, controllers, views
- Livewire 3 component patterns for dynamic UI with wire:navigate for SPA-like navigation
- Blade templating with Tailwind CSS for styling
- Alpine.js for lightweight interactivity (dropdowns, modals, toggles, selection state)
- Database sessions/cache (no Redis) — use for folder metadata, UI preferences, message metadata
- Installation lock via storage/installed file with flock atomicity

### Integration Points
- **MailProvider interface** (from Phase 1) — clean abstraction for IMAP/SMTP operations
- **Authentication flow**: Login → session with encrypted IMAP password → mailbox routes
- **Database schema**: users, sessions, settings tables — extend with folders, messages, message_metadata tables
- **Routes**: /mailbox (authenticated) — extend with folder/message endpoints
- **Views**: layouts/app.blade.php for authenticated mailbox UI

</code_context>

<specifics>
## Specific Ideas

- WordPress-style setup wizard philosophy carries into mailbox: "It just works" — folders auto-mapped, messages render safely, actions feel instant
- Gmail/Outlook as UX benchmarks: hierarchical folders, message list with checkboxes, toolbar actions, srcdoc iframe viewer, image blocking banner
- Security-first: HTML email never renders as trusted app HTML (dual sanitization), remote images blocked by default, attachments streamed not stored
- Shared hosting reality: No background workers, no Redis — sync on folder open + lightweight periodic refresh via scheduler (if available) or user-triggered

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 2-Mailbox Core*
*Context gathered: 2026-09-05*