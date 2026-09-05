# Phase 3: Compose & Send - Context

**Gathered:** 2026-09-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Users can compose, reply to, and send emails — the full send/receive loop is complete. This phase delivers the full composer with rich text editing, reply/forward with quoted text, draft autosave with IMAP Drafts folder sync, SMTP sending with Sent folder synchronization, configurable undo send, and contact autocomplete from recent IMAP recipients.
</domain>

<decisions>
## Implementation Decisions

### Composer UI & Rich Text Editor
- **D-01:** Modal overlay composer (like Gmail default) — **Reversibility:** reversible — Component structure change only
- **D-02:** Tiptap (headless, ProseMirror-based) for rich text editing — **Reversibility:** reversible — Editor library swap, same Livewire/Alpine integration pattern
- **D-03:** Compact layout: To, Subject, Body with CC/BCC/Attachments in collapsible sections — **Reversibility:** reversible — Blade template change only
- **D-04:** Full toolbar: Bold, Italic, Underline, Link, Lists, Quote, Headings, Code block, Horizontal rule, Alignment, Tables, Image upload, Emoji, Strikethrough, Sub/superscript, Font size/color — **Reversibility:** reversible — Tiptap extension configuration only

### Draft Autosave Strategy
- **D-05:** Autosave triggers on every keystroke (debounced 1-2s) — **Reversibility:** reversible — Debounce timing config change
- **D-06:** LocalStorage first for instant saves, sync to IMAP Drafts folder on interval — **Reversibility:** reversible — Storage strategy change only
- **D-07:** Load drafts from IMAP Drafts folder (server is source of truth) — **Reversibility:** reversible — Load source change only
- **D-08:** On send: delete from IMAP Drafts folder, clear LocalStorage — **Reversibility:** reversible — Cleanup logic change only

### Reply/Forward Quoting Format
- **D-09:** Collapsible quoted sections (Gmail-style) with show/hide toggle — **Reversibility:** reversible — UI component change only
- **D-10:** Full attribution header: "On [date], [sender name] <[sender email]> wrote:" — **Reversibility:** reversible — Template string change only
- **D-11:** Convert HTML to plain text for quoting, wrap in `<blockquote>` — **Reversibility:** reversible — Quoting pipeline change only
- **D-12:** Forward uses same format as reply (collapsible blockquote with attribution) — **Reversibility:** reversible — Shared component, no divergence

### Attachment Handling in Composer
- **D-13:** Drag-drop zone + click to browse, immediate upload to server temp storage — **Reversibility:** reversible — Upload flow change only
- **D-14:** 25MB per file, 50MB total (Gmail standard), MIME type allowlist — **Reversibility:** reversible — Config values and validation logic change
- **D-15:** List display with filename, size, remove button, image preview thumbnails — **Reversibility:** reversible — Blade component change only
- **D-16:** Temp storage → move to IMAP Drafts on draft save, move to Sent on send — **Reversibility:** reversible — Lifecycle logic change only

### SMTP Sending & Sent Folder Sync
- **D-17:** IMAP APPEND to Sent folder first, then SMTP send (reverse order) — **Reversibility:** costly — Changes send flow, error handling, retry logic; undoing requires re-architecting the send pipeline
- **D-18:** On APPEND success but SMTP failure: queue for background retry, keep in Sent with 'pending' flag — **Reversibility:** costly — Tied to D-17; retry infrastructure depends on send order
- **D-19:** Laravel scheduler (cron) runs retry job every minute for pending sends — **Reversibility:** reversible — Scheduler task change only; works within shared hosting constraints
- **D-20:** Sent folder path configurable in setup wizard, default to 'Sent' — **Reversibility:** reversible — Config addition, uses existing setup wizard pattern

### Undo Send Implementation
- **D-21:** Delay SMTP send by configurable 5-30s, hold in database, cancel on undo — **Reversibility:** reversible — Delay value and queue logic change only
- **D-22:** Database `pending_sends` table with serialized message + `send_at` timestamp — **Reversibility:** reversible — Schema change via migration
- **D-23:** Toast notification with 'Undo' button and countdown timer (Gmail style) — **Reversibility:** reversible — UI component change only
- **D-24:** On undo: cancel send, move to Drafts folder (IMAP APPEND to Drafts) — **Reversibility:** reversible — Undo action logic change only

### Contact Autocomplete Source
- **D-25:** Recent recipients from IMAP (scan Sent/Inbox for To/CC addresses) — **Reversibility:** reversible — Data source change only
- **D-26:** On-demand IMAP SEARCH for recent messages, extract addresses, cache in DB — **Reversibility:** reversible — Scan strategy change only
- **D-27:** Autocomplete dropdown supports multiple selection (comma-separated), adds as chips — **Reversibility:** reversible — UI component change only
- **D-28:** All fields (To/CC/BCC) share same source, but BCC hides recipient chips from other fields — **Reversibility:** reversible — Field-specific display logic only

### the agent's Discretion
- Exact Tiptap extension configuration and custom node/views for email-specific needs (e.g., signature insertion placeholder)
- Database schema for `pending_sends` table (indexes, foreign keys, serialization format)
- IMAP SEARCH query optimization for recent recipient extraction (date ranges, folder selection)
- Scheduler frequency and retry backoff strategy for pending sends
- LocalStorage key structure and conflict resolution for draft autosave
- Exact collapsible quote component implementation (Alpine.js + CSS)
- Temp file storage location and cleanup strategy for composer attachments

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements (COMP-01 through COMP-13 for Phase 3)
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, Symfony Mailer, HTMLPurifier + DOMPurify
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004 (especially ADR-004: Dual HTML sanitization)

### Phase 1 Decisions (Carried Forward)
- `.planning/phases/01-foundation-setup-wizard/01-CONTEXT.md` — Setup wizard patterns, IMAP/SMTP testing, auto-detection, session/auth defaults

### Phase 2 Decisions (Carried Forward)
- `.planning/phases/02-mailbox-core/02-CONTEXT.md` — Folder mapping, message list patterns, dual sanitization pipeline, IMAP service patterns, bulk selection

### Existing Codebase Patterns
- `app/Livewire/SetupWizard.php` — 8-step wizard with session resume, inline validation, IMAP/SMTP testing patterns
- `app/Services/ImapMailboxService.php` — webklex/php-imap client usage, folder operations, message fetching, flag management, cached folders
- `app/Services/MessageSanitizer.php` — HTMLPurifier (server) + DOMPurify (client) dual sanitization, remote image blocking
- `app/Services/FolderMapper.php` — SPECIAL-USE folder detection with heuristic fallback
- `app/Services/ImapConnectionTester.php` / `SmtpConnectionTester.php` — Connection testing patterns
- `app/Livewire/Mailbox/MessageViewer.php` — Message data extraction as simple types, Alpine.js image toggle, action buttons
- `app/Livewire/Mailbox/MessageList.php` — Pagination, sorting, Alpine.js selection state, bulk actions
- `app/Livewire/Mailbox/MessageToolbar.php` — Bulk operations with IMAP UID sets
- `app/Livewire/Mailbox/FolderSidebar.php` — Hierarchical folder tree, cached counts, expand/collapse
- `resources/views/layouts/mailbox.blade.php` — Mailbox layout with sidebar, mobile drawer
- `resources/views/components/email-renderer.blade.php` — Sandboxed iframe with srcdoc, Alpine.js image blocking banner
- `config/openmail.php` — IMAP/SMTP configuration structure

No external specs — requirements fully captured in decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **SetupWizard component pattern**: Multi-step state management, session persistence, inline validation with error display — apply to composer multi-step flows if needed
- **ImapMailboxService**: IMAP client initialization, connection, folder operations (create/rename/delete), message fetching, flag management, UID-based bulk operations — extend for APPEND to Sent/Drafts, message sending prep
- **MessageSanitizer**: Dual sanitization pipeline (HTMLPurifier server → DOMPurify client), remote image blocking — reuse for composer preview if needed, apply to forwarded HTML content
- **FolderMapper**: SPECIAL-USE detection (\\Sent, \\Drafts) with heuristic fallback — use for determining Sent/Drafts folder paths for APPEND
- **Livewire + Alpine form patterns**: `wire:model`, `wire:click`, `wire:loading`, Alpine `x-data`/`x-show`/`x-transition` — established patterns for composer modal, autocomplete, attachment list
- **Database sessions/cache**: No Redis — use for pending_sends queue, draft autosave metadata, autocomplete cache
- **Modal/drawer patterns**: `resources/views/layouts/mailbox.blade.php` mobile sidebar drawer — adapt for composer modal
- **Email renderer component**: Sandboxed iframe with `srcdoc`, Alpine.js image blocking — adapt for composer preview

### Established Patterns
- Laravel 12 conventions for project structure, migrations, controllers, views
- Livewire 3 component patterns for dynamic UI with `wire:navigate` for SPA-like navigation
- Blade templating with Tailwind CSS for styling
- Alpine.js for lightweight interactivity (dropdowns, modals, toggles, selection state, chips)
- Database sessions/cache (no Redis) — use for pending_sends queue, draft autosave metadata, autocomplete cache
- Installation lock via `storage/installed` file with flock atomicity
- IMAP password encrypted in session via `Crypt::encrypt`/`decrypt`
- Message data extracted as simple types (not webklex objects) for Livewire serialization

### Integration Points
- **MailProvider interface** (from Phase 1) — clean abstraction for IMAP/SMTP operations
- **Authentication flow**: Login → session with encrypted IMAP password → mailbox routes
- **Database schema**: users, sessions, settings, folders, message_metadata tables — extend with `pending_sends`, `draft_autosave`, `contact_autocomplete_cache` tables
- **Routes**: `/mailbox` (authenticated) — extend with `/compose`, `/compose/reply/{folder}/{uid}`, `/compose/forward/{folder}/{uid}`, `/drafts/{uid}`
- **Views**: `layouts/mailbox.blade.php` for authenticated mailbox UI — composer modal mounts here
- **Services**: `ImapMailboxService` for all IMAP operations, `MessageSanitizer` for HTML safety

</code_context>

<specifics>
## Specific Ideas

- Gmail/Outlook as UX benchmarks: modal composer, collapsible quotes, chip-based recipients, toast undo, drag-drop attachments
- Security-first: HTML email never renders as trusted app HTML (dual sanitization applies to forwarded content too), attachments validated via MIME type allowlist
- Shared hosting reality: No background workers, no Redis — scheduler (cron) for retry jobs, database for pending_sends queue, LocalStorage for instant draft saves
- Tiptap editor must integrate with Livewire: `wire:model` on hidden textarea syncing editor content, Alpine.js for toolbar interactions
- Composer modal should support keyboard shortcuts (Ctrl+Enter to send, Escape to close/discard)
- Signature insertion: Tiptap custom node or placeholder that swaps with user's signature on send
- Draft autosave: LocalStorage key per composition session, merge strategy on composer open
- Contact autocomplete: Debounced IMAP SEARCH, cache results in DB with TTL, merge with any future local address book (Phase 4)

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 3-Compose & Send*
*Context gathered: 2026-09-06*