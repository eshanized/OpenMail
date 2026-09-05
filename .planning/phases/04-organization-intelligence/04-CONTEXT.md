# Phase 4: Organization & Intelligence - Context

**Gathered:** 2026-09-06
**Status:** Ready for planning

<domain>
## Phase Boundary

Users can search, organize, and thread messages — the mailbox becomes smart and navigable. This phase delivers conversation threading (Message-ID/In-Reply-To/References), full-text search with filters, local address book with IMAP recipient integration, and Gmail-style labels alongside folders.

</domain>

<decisions>
## Implementation Decisions

### Threading Implementation
- **D-01:** On-demand thread reconstruction — fetch headers for visible messages only, build thread tree when user opens folder. Lower memory, works with pagination. Matches Phase 2 message list pattern — **Reversibility:** reversible — Thread building logic change only
- **D-02:** Subject normalization fallback for missing/corrupted headers — strip Re:/Fwd:, trim whitespace, group by normalized subject + ±2 day window. Used by Gmail as fallback — **Reversibility:** reversible — Fallback logic change only
- **D-03:** Alpine.js client-side state for thread expansion/collapse — managed entirely in browser, no server round-trip. Read/unread reflects IMAP \Seen flag per message — **Reversibility:** reversible — UI component change only
- **D-04:** Thread row with expandable children in message list — single thread row showing: sender of latest message, subject, timestamp of latest, unread count badge, attachment indicator. Click expands inline to show all messages in thread — **Reversibility:** reversible — Message list rendering change only

### Search Architecture
- **D-05:** Index message_metadata on save/sync — MessageMetadata model is Scout-searchable. Index: subject, from_address, from_name, to_address, snippet, body_text. Real-time index updates on folder sync — **Reversibility:** reversible — Index strategy change only
- **D-06:** Global search + folder filter — single search bar searches ALL folders by default. Folder dropdown to restrict scope. Additional filters: date range, has attachment, read/unread, flagged — **Reversibility:** reversible — UI filter change only
- **D-07:** MySQL FULLTEXT relevance + date weighting — use MySQL's built-in relevance score (MATCH...AGAINST) as primary sort. Apply slight date decay (newer messages rank higher for same relevance). Highlight matched terms via Scout — **Reversibility:** reversible — Ranking formula change only
- **D-08:** Instant search with debounce (300ms) — show results in dropdown below search bar. Click result to open message. Separate "Search results" page for full listing with filters — **Reversibility:** reversible — UX pattern change only

### Contact Management
- **D-09:** Unified autocomplete: local contacts + cached IMAP recipients — ContactAutocompleteCache (IMAP) + new contacts table. Autocomplete searches both, merges results, deduplicates by email. Local contacts rank higher (user-curated). IMAP cache auto-expires — **Reversibility:** reversible — Data source merge logic change only
- **D-10:** Minimal contact fields — name, email, phone, notes + groups (many-to-many contact_groups pivot). Matches vCard 3.0 basics — **Reversibility:** costly — Schema change via migration; adding fields later requires migration
- **D-11:** Standard vCard 3.0 import/export — export .vcf with FN, EMAIL, TEL, NOTE, CATEGORIES (groups). Import: parse vCard 3.0, map to fields, create/update by email match — **Reversibility:** reversible — Import/export format change only
- **D-12:** Contacts sidebar tab — tabbed sidebar: "Folders" | "Contacts" | "Labels". Click tab to switch panel. Contacts panel shows contact list with search, click to view/edit, "New Contact" button — **Reversibility:** reversible — Sidebar UI change only

### Labels System
- **D-13:** Local labels only — labels stored in DB only (labels table + message_labels pivot). Not synced to IMAP. Works on any IMAP server. Archive action = remove Inbox label + add Archive label + move to Archive folder (IMAP) — **Reversibility:** costly — Schema change via migration; removing labels requires data migration
- **D-14:** Archive action = move to Archive folder + remove Inbox label — IMAP MOVE message to Archive folder (\Archive SPECIAL-USE or heuristic). If message has "Inbox" label, remove it. If no Archive folder exists, create one — **Reversibility:** reversible — Archive logic change only
- **D-15:** Color chips in message list, colored sidebar — each label has user-picked color (from palette). Message list: colored pills/chips showing labels. Sidebar: label list with color dot + name + unread count. Click label in sidebar filters message list — **Reversibility:** reversible — UI component change only
- **D-16:** Inline label management in sidebar + modal — hover label → edit/delete icons. "Create new label" button at bottom opens modal (name + color picker). Right-click → context menu (rename, delete, change color) — **Reversibility:** reversible — UI interaction change only

### UI/UX Integration
- **D-17:** Search bar in top toolbar above message list — persistent, always visible. Keyboard shortcut (/) focuses it. Works with wire:navigate for instant results — **Reversibility:** reversible — Layout change only
- **D-18:** Thread view toggle button in message list toolbar — icon button next to sort/filter: "Threaded" ↔ "Flat". State persists per folder in localStorage. Click to switch instantly via Alpine.js — **Reversibility:** reversible — Toolbar button + localStorage change only
- **D-19:** Tabbed sidebar: Folders | Contacts | Labels — left sidebar has tabs at top. Folders panel shows folder tree. Contacts panel shows contact list with search. Labels panel shows label list with counts. Mobile: tabs become bottom navigation or drawer sections — **Reversibility:** reversible — Sidebar structure change only
- **D-20:** Horizontal label chip row with overflow — show up to 3 label chips horizontally. If more: show "+N" chip. Hover/tap "+N" → popover with all labels. Chips use label colors. Click chip → filters to that label — **Reversibility:** reversible — Message list rendering change only

### the agent's Discretion
- Database schema design for labels, message_labels, contacts, contact_groups tables (indexes, foreign keys, cascading)
- Thread tree data structure and rendering algorithm (Alpine.js component)
- Scout searchable configuration on MessageMetadata (which fields, weights, highlighting)
- Contact deduplication logic (exact email match vs fuzzy)
- vCard parser library choice (spatie/vcard-parser or similar)
- Label color palette (accessibility-compliant 10-12 colors)
- Mobile responsive behavior for tabbed sidebar (breakpoint, drawer vs tabs)
- Search results page layout (filters sidebar, results list, pagination)
- Keyboard shortcuts (/ for search, t for thread toggle, etc.)

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements (THR-01 through THR-05, SRCH-01 through SRCH-07, CONT-01 through CONT-06, LBL-01 through LBL-05 for Phase 4)
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js, webklex/php-imap, Symfony Mailer, HTMLPurifier + DOMPurify, Laravel Scout (database)
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004 (especially ADR-003: Database-only infrastructure, ADR-004: Dual HTML sanitization)

### Phase 1 Decisions (Carried Forward)
- `.planning/phases/01-foundation-setup-wizard/01-CONTEXT.md` — Setup wizard patterns, IMAP/SMTP testing, auto-detection, session/auth defaults

### Phase 2 Decisions (Carried Forward)
- `.planning/phases/02-mailbox-core/02-CONTEXT.md` — Folder mapping, message list patterns, dual sanitization pipeline, IMAP service patterns, bulk selection, Alpine.js selection state

### Phase 3 Decisions (Carried Forward)
- `.planning/phases/03-compose-send/03-CONTEXT.md` — Composer patterns, Tiptap editor, draft autosave, contact autocomplete service, IMAP APPEND to Sent/Drafts

### Existing Codebase Patterns
- `app/Services/ImapMailboxService.php` — IMAP client, folder operations, message fetching, flag management, cached folders, appendMessage, searchRecipients
- `app/Services/ContactAutocompleteService.php` — IMAP recipient caching, search, recent recipients
- `app/Services/FolderMapper.php` — SPECIAL-USE folder detection with heuristic fallback (getSentFolderPath, getDraftsFolderPath)
- `app/Services/MessageSanitizer.php` — HTMLPurifier (server) + DOMPurify (client) dual sanitization
- `app/Models/MessageMetadata.php` — Cached message metadata (UID, Message-ID, flags, snippet, etc.) — extend for Scout
- `app/Models/ContactAutocompleteCache.php` — IMAP-sourced recipient cache — reference for local contacts table
- `app/Models/PendingSend.php` — Database queue pattern — reference for any async operations
- `app/Livewire/Mailbox/MessageList.php` — Pagination, sorting, Alpine.js selection state, bulk actions
- `app/Livewire/Mailbox/FolderSidebar.php` — Hierarchical folder tree, cached counts, expand/collapse
- `app/Livewire/Mailbox/MessageToolbar.php` — Bulk operations with IMAP UID sets
- `resources/views/layouts/mailbox.blade.php` — Mailbox layout with sidebar, mobile drawer — extend for tabbed sidebar
- `config/openmail.php` — IMAP/SMTP configuration structure — extend for search/threading/labels settings

No external specs — requirements fully captured in decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **ImapMailboxService**: IMAP client initialization, connection, folder operations, message fetching, flag management, UID-based bulk operations, appendMessage, searchRecipients — extend for thread header fetching, message body fetching for search indexing
- **ContactAutocompleteService**: IMAP recipient caching, search, recent recipients — extend to merge with local contacts table
- **FolderMapper**: SPECIAL-USE detection (\Sent, \Drafts, \Archive) with heuristic fallback — use for Archive folder detection
- **MessageSanitizer**: Dual sanitization pipeline (HTMLPurifier server → DOMPurify client) — apply to search result snippets if needed
- **Livewire + Alpine patterns**: `wire:model`, `wire:click`, `wire:loading`, Alpine `x-data`/`x-show`/`x-transition` — established for message list, composer, now thread expansion, search dropdown, contact sidebar
- **Database sessions/cache**: No Redis — use for search query cache, thread state, label filter state
- **Modal/drawer patterns**: `resources/views/layouts/mailbox.blade.php` mobile sidebar drawer — adapt for contact/label management modals
- **Scout integration**: Laravel Scout with database driver configured — extend MessageMetadata for search

### Established Patterns
- Laravel 12 conventions for project structure, migrations, controllers, views
- Livewire 3 component patterns for dynamic UI with `wire:navigate` for SPA-like navigation
- Blade templating with Tailwind CSS for styling
- Alpine.js for lightweight interactivity (dropdowns, modals, toggles, selection state, chips, tabs)
- Database sessions/cache (no Redis) — use for search query cache, thread state, label filter state
- Installation lock via `storage/installed` file with flock atomicity
- IMAP password encrypted in session via `Crypt::encrypt`/`decrypt`
- Message data extracted as simple types (not webklex objects) for Livewire serialization

### Integration Points
- **MailProvider interface** (from Phase 1) — clean abstraction for IMAP/SMTP operations
- **Authentication flow**: Login → session with encrypted IMAP password → mailbox routes
- **Database schema**: users, sessions, settings, folders, message_metadata, pending_sends, contact_autocomplete_cache — extend with: `labels`, `message_labels`, `contacts`, `contact_groups`, `contact_group_contact` tables
- **Routes**: `/mailbox` (authenticated) — extend with `/search`, `/labels/{label}`, `/contacts`, `/contacts/{contact}`, `/thread/{messageId}`
- **Views**: `layouts/mailbox.blade.php` for authenticated mailbox UI — thread view, search results, contact sidebar, label chips
- **Services**: `ImapMailboxService` for all IMAP operations, `ContactAutocompleteService` for recipient cache, `MessageSanitizer` for HTML safety

</code_context>

<specifics>
## Specific Ideas

- Gmail/Outlook as UX benchmarks: threaded conversation view, instant search dropdown, contact sidebar, label chips, archive button
- Security-first: HTML email never renders as trusted app HTML (dual sanitization applies to search snippets), search queries sanitized
- Shared hosting reality: No background workers, no Redis — Scout database driver, scheduler for any batch indexing, localStorage for UI state
- Thread reconstruction: Fetch Message-ID, In-Reply-To, References headers for visible page + thread roots. Build tree in Alpine.js component
- Search indexing: Hook into ImapMailboxService sync — when message_metadata saved/updated, trigger Scout index update
- Labels: `labels` table (id, user_id, name, color, created_at), `message_labels` pivot (message_metadata_id, label_id). Labels apply per-user (not global)
- Contacts: `contacts` table (id, user_id, name, email, phone, notes, avatar, created_at), `contact_groups` (id, user_id, name, color), `contact_group_contact` pivot
- vCard import: Parse with spatie/vcard-parser, map FN→name, EMAIL→email, TEL→phone, NOTE→notes, CATEGORIES→groups
- Mobile: Tabbed sidebar collapses to bottom navigation on <768px. Search bar stays in top toolbar. Thread toggle in message list toolbar.

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 4-Organization & Intelligence*
*Context gathered: 2026-09-06*