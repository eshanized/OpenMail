# Phase 2: Mailbox Core - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-05
**Phase:** 2-Mailbox Core
**Areas discussed:** Folder Navigation & Mapping, Message List Fetching & Pagination, Message Viewer & HTML Sanitization, Local Metadata Caching & Sync, Bulk Selection & Actions

---

## Folder Navigation & Mapping

| Option | Description | Selected |
|--------|-------------|----------|
| XLIST/SEARCH SPECIAL-USE (RFC 6154) — preferred standard | Use IMAP XLIST or SEARCH RETURN (SPECIAL-USE) to detect \Inbox, \Sent, \Drafts, \Trash, \Junk, \Archive automatically. Falls back to heuristics if unsupported. (Recommended — standard-compliant, works with Gmail/Outlook/Yahoo) | ✓ |
| Heuristic name matching — pragmatic fallback | Match folder names against known patterns (INBOX, Sent, Sent Items, Drafts, Trash, Deleted, Spam, Junk, Archive) case-insensitively. Works everywhere but less reliable for non-English folders. | |
| User maps manually in settings — explicit control | Show all IMAP folders, let admin/user assign each to a standard role during setup or in settings. Most control but adds setup friction. | |

**User's choice:** XLIST/SEARCH SPECIAL-USE (RFC 6154) — preferred standard
**Notes:** Primary detection with heuristic fallback for unsupported servers.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Hierarchical tree — nested folders with expand/collapse | Render IMAP folder hierarchy (parent/child via delimiter). Users expand/collapse. Matches Gmail/Outlook desktop behavior. (Recommended — standard webmail UX) | ✓ |
| Flat list with visual indentation — simpler implementation | All folders in one list, indented by depth. No expand/collapse. Simpler Livewire component, less JS. | |
| Flat list only — no hierarchy | Ignore IMAP hierarchy, show all folders at same level with full path names (e.g., INBOX/Projects/ClientA). Simplest but loses folder organization. | |

**User's choice:** Hierarchical tree — nested folders with expand/collapse
**Notes:** Standard webmail UX pattern.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Direct IMAP operations — immediate server sync | CREATE/RENAME/DELETE IMAP commands execute immediately. User sees result instantly. Failure shows error. (Recommended — simple, honest, matches user expectation) | ✓ |
| Optimistic local + background sync — snappier UI | Update local cache immediately, queue IMAP operation in background. Show pending state. More complex but feels faster on slow connections. | |
| You decide — agent discretion | Implementation detail. Choose based on what's simpler given the stack. | |

**User's choice:** Direct IMAP operations — immediate server sync
**Notes:** Simple, honest, matches user expectation.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Cached counts + periodic IMAP STATUS refresh | Store unread/total in DB (DB-03). Refresh via IMAP STATUS on folder switch or timer (e.g., every 30s). Fast UI, slight staleness. (Recommended — balances performance/accuracy) | ✓ |
| On-demand IMAP STATUS per folder click | Fetch STATUS when user clicks folder. Always accurate. Slower on high-latency IMAP. Simpler cache logic. | |
| IMAP IDLE for real-time — future enhancement | Push notifications via IMAP IDLE. Not in Phase 2 scope (ADV-04 is v2). Note for later. | |

**User's choice:** Cached counts + periodic IMAP STATUS refresh
**Notes:** Balances performance and accuracy.

---

## Message List Fetching & Pagination

| Option | Description | Selected |
|--------|-------------|----------|
| IMAP SEARCH + SORT (server-side) — efficient for large mailboxes | Use IMAP SEARCH for filters + SORT extension for ordering. Only fetch needed page. Requires server support (most modern IMAP servers). (Recommended — scales to 100k+ messages) | ✓ |
| Fetch all UIDs once, paginate/sort in PHP — simpler, works everywhere | FETCH UID list on folder select, store in session/cache. Paginate/sort in memory. Works on any IMAP server. Memory heavy for huge mailboxes. | |
| Hybrid — SEARCH for filters, client paginate — balanced | Use SEARCH for date/folder/flag filters, fetch matching UIDs, then paginate client-side. Good middle ground. | |

**User's choice:** IMAP SEARCH + SORT (server-side) — efficient for large mailboxes
**Notes:** Scales to large mailboxes, leverages server capabilities.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Headers-only + snippet — fast list rendering | FETCH BODY.PEEK[HEADER.FIELDS (FROM TO SUBJECT DATE)] + first ~200 chars of body for snippet. Minimal IMAP traffic. (Recommended — standard webmail pattern) | ✓ |
| Full message — simpler parsing, more data | FETCH RFC822 (full message) for each row. Parse once, have everything. Heavy on bandwidth for large pages. | |
| Headers + separate snippet fetch — optimized | Headers for list, then separate FETCH BODY.PEEK[TEXT] for snippet only when visible. More IMAP round-trips but minimal data. | |

**User's choice:** Headers-only + snippet — fast list rendering
**Notes:** Standard webmail pattern, minimal IMAP traffic.

---

| Option | Description | Selected |
|--------|-------------|----------|
| IMAP \Seen flag as source of truth — simple, consistent | Read/unread comes directly from IMAP flags on every fetch. No local sync needed. Accurate across clients. (Recommended — IMAP is source of truth per constraints) | ✓ |
| Mirror to local DB + IMAP — fast local filters | Store flag state in DB (DB-04) for instant filtering/sorting without IMAP round-trip. Sync on flag changes. More complex but snappier. | |
| You decide — agent discretion | Implementation detail. Choose based on what's simpler. | |

**User's choice:** IMAP \Seen flag as source of truth — simple, consistent
**Notes:** IMAP is source of truth per project constraints.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Map IMAP SORT to friendly labels — Date, Sender, Subject, Size | UI shows 'Date (newest)', 'Sender', 'Subject', 'Size'. Backend maps to IMAP SORT criteria. Clean UX. (Recommended) | ✓ |
| Raw IMAP criteria — technical users | Show IMAP SORT keys directly. Less user-friendly. | |
| Date only for v1 — simplify | Only sort by date in Phase 2. Add others in Phase 4 with search. | |

**User's choice:** Map IMAP SORT to friendly labels — Date, Sender, Subject, Size
**Notes:** Clean UX with friendly labels.

---

## Message Viewer & HTML Sanitization

| Option | Description | Selected |
|--------|-------------|----------|
| srcdoc attribute — simplest, no extra route | Set iframe srcdoc="<sanitized HTML>". Content inline, no network request. CSP applies to parent. (Recommended — simplest, secure) | ✓ |
| Blob URL — separate origin, stricter isolation | Create blob URL from sanitized HTML. iframe gets unique origin. Stronger isolation but more complex. | |
| Dedicated route /message/{id}/html — traditional | Separate endpoint returns sanitized HTML. iframe src=/message/123/html. Allows caching, separate CSP. More routes/controllers. | |

**User's choice:** srcdoc attribute — simplest, no extra route
**Notes:** Simplest and secure approach.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Dual: HTMLPurifier (server) → DOMPurify (client) — defense in depth | Server strips dangerous tags/attrs first. Client re-sanitizes at render time. Catches parser differentials. (Recommended — matches ADR-004, Phase 1 decision D-15) | ✓ |
| DOMPurify only (client) — simpler, modern | Trust client-side sanitization. Faster server. Risk: parser differentials if server ever renders HTML elsewhere. | |
| HTMLPurifier only (server) — no JS dependency | Sanitize once on server. Client renders as-is. Simpler but misses client-side parser differentials. | |

**User's choice:** Dual: HTMLPurifier (server) → DOMPurify (client) — defense in depth
**Notes:** Matches ADR-004 from Phase 1, defense in depth.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Block by default, user opt-in per message — privacy-first | Strip/block external images initially. Show 'Display images' banner. On click, rewrite img src to proxy through app or allow direct load. (Recommended — Gmail/Outlook pattern, privacy-preserving) | ✓ |
| Proxy all images through app — always safe | Rewrite all img src to /image/proxy?url=... Server fetches, serves with correct headers. No external requests from client. Higher bandwidth. | |
| Allow all — simpler, less private | Load images directly from sender's servers. Tracks opens. Simplest but leaks IP/cookies. | |

**User's choice:** Block by default, user opt-in per message — privacy-first
**Notes:** Gmail/Outlook pattern, privacy-preserving.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Stream directly from IMAP — no local storage | On download, FETCH BODY.PEEK[part] and stream to response. No disk usage. Slower for large attachments. (Recommended — shared hosting friendly, no temp storage) | ✓ |
| Cache to local temp storage — faster repeat downloads | Save attachment to storage/app/attachments on first fetch. Serve from disk. Cleanup job needed. Faster for repeated access. | |
| You decide — agent discretion | Implementation detail. Choose based on what's simpler. | |

**User's choice:** Stream directly from IMAP — no local storage
**Notes:** Shared hosting friendly, no temp storage needed.

---

## Local Metadata Caching & Sync

| Option | Description | Selected |
|--------|-------------|----------|
| UID, Message-ID, folder, flags (\Seen, \Flagged), date, from, to, subject, snippet — core set | Enables fast list rendering, search indexing (Phase 4), threading (Phase 4), offline-ish UX. ~200 bytes/msg. (Recommended — balances utility/storage) | ✓ |
| Minimal: UID, Message-ID, folder, flags only — lean | Just enough for sync tracking and flag state. List rendering still needs IMAP fetch. Smaller DB. | |
| Full headers + parsed structure — maximal | Store all headers, MIME structure, references. Powers advanced features without IMAP. Large DB, complex sync. | |

**User's choice:** UID, Message-ID, folder, flags (\Seen, \Flagged), date, from, to, subject, snippet — core set
**Notes:** Balances utility and storage, enables future phases.

---

| Option | Description | Selected |
|--------|-------------|----------|
| UIDVALIDITY + periodic UID FETCH — standard IMAP sync | Track UIDVALIDITY per folder. On mismatch, full resync. Periodic FETCH (UID FLAGS) for flag changes. Reliable, standard. (Recommended — works everywhere) | ✓ |
| CONDSTORE/QRESYNC (RFC 7162) — incremental sync | Use MODSEQ to fetch only changed messages since last sync. Much faster for large mailboxes. Requires server support (Gmail, Outlook, Dovecot, Courier). | |
| Full resync on folder open — simple, honest | Fetch all UIDs + flags every time folder opens. Accurate but slow for large folders. Simplest implementation. | |

**User's choice:** UIDVALIDITY + periodic UID FETCH — standard IMAP sync
**Notes:** Reliable, works everywhere.

---

| Option | Description | Selected |
|--------|-------------|----------|
| On folder open + background periodic — responsive + fresh | Sync when user opens folder (blocking or async). Background timer (e.g., every 2-5 min) refreshes current folder. Best UX. (Recommended) | ✓ |
| On folder open only — simpler | Sync when folder selected. No background process. Stale if user stays in folder. Simpler, no scheduler needed. | |
| Background only (with loading state) — non-blocking | Show cached data immediately, refresh in background. Never blocks. Most complex (loading states, race conditions). | |

**User's choice:** On folder open + background periodic — responsive + fresh
**Notes:** Best UX, responsive and fresh.

---

## Bulk Selection & Actions

| Option | Description | Selected |
|--------|-------------|----------|
| Alpine.js for selection state + Livewire actions — hybrid | Alpine manages checkbox state (shift-click, select all) client-side. Livewire handles bulk actions (move, delete, flag) via wire:click. Best of both. (Recommended — leverages Alpine included with Livewire) | ✓ |
| Pure Livewire — wire:model on each checkbox | Each row has wire:model="selected.{{ $msg->uid }}". Shift-click via JS dispatching events to Livewire. Simpler mental model but more round-trips. | |
| Pure Alpine + separate action endpoint — minimal Livewire | Alpine handles all selection UI. Bulk actions call a separate POST endpoint. Less Livewire coupling. | |

**User's choice:** Alpine.js for selection state + Livewire actions — hybrid
**Notes:** Leverages Alpine included with Livewire, best of both.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Single IMAP STORE/COPY with UID set — efficient | IMAP accepts UID sets (1,2,3 or 1:10). Send one STORE +FLAGS.SILENT or COPY command for all selected. Fast, atomic-ish. (Recommended — standard IMAP) | ✓ |
| Loop per UID — simpler code, slower | Iterate selected UIDs, send individual STORE/COPY. Works everywhere. N round-trips for N messages. Fine for small selections. | |
| Queue to background job — non-blocking UI | Dispatch bulk operation to queue. Show progress. User continues. Phase 2 has no queue workers (no Redis). Would need database queue. | |

**User's choice:** Single IMAP STORE/COPY with UID set — efficient
**Notes:** Standard IMAP, fast and atomic-ish.

---

| Option | Description | Selected |
|--------|-------------|----------|
| Toolbar above list — Archive, Delete, Spam, Move to..., Mark read/unread, Flag | Visible actions when messages selected. Gmail/Outlook pattern. Discoverable. (Recommended) | ✓ |
| Dropdown menu — compact | Single 'Actions' dropdown with all options. Saves vertical space. Less discoverable. | |
| Context menu (right-click) — power user | Right-click message or selection for actions. Nice for power users but hidden on mobile/touch. | |

**User's choice:** Toolbar above list — Archive, Delete, Spam, Move to..., Mark read/unread, Flag
**Notes:** Gmail/Outlook pattern, discoverable.

---

## the agent's Discretion

- Database schema design for message metadata table (indexes, foreign keys) — researcher and planner determine
- Livewire component organization for mailbox (folder sidebar, message list, message viewer) — follow existing patterns
- Background sync implementation (scheduler vs manual trigger) — choose based on shared hosting constraints
- Exact IMAP FETCH fields for snippet extraction — optimize for common MIME structures

## Deferred Ideas

None — discussion stayed within phase scope.