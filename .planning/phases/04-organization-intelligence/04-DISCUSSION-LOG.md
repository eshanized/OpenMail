# Phase 4: Organization & Intelligence - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-06
**Phase:** 4-Organization & Intelligence
**Areas discussed:** Threading Implementation, Search Architecture, Contact Management, Labels System, UI/UX Integration

---

## Threading Implementation

| Option | Description | Selected |
|--------|-------------|----------|
| On-demand thread reconstruction (recommended) | Fetch headers for visible messages only, build thread tree when user opens folder. Lower memory, works with pagination. Matches Phase 2 message list pattern. | ✓ |
| Full mailbox thread index | Fetch ALL message headers from IMAP on folder open, build complete thread tree in memory/DB. Faster thread view but heavy on large mailboxes. Requires background sync. | |
| Hybrid: Cache thread roots, expand on demand | Identify thread root messages (no In-Reply-To), cache them. When user expands a thread, fetch children via IMAP SEARCH for References. Balances performance and memory. | |

**User's choice:** On-demand thread reconstruction (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Subject normalization fallback (recommended) | When References/In-Reply-To missing or broken, normalize subject (strip Re:/Fwd:, trim whitespace) and group by normalized subject + approximate time window (±2 days). Used by Gmail as fallback. | ✓ |
| Strict header-only threading | Only thread messages with valid References/In-Reply-To chains. Broken threads appear as separate conversations. Cleaner but fragments real threads. | |
| IMAP THREAD command (if supported) | Use IMAP THREAD=REFERENCES or THREAD=ORDEREDSUBJECT server-side command. Only works if mail server supports it (Dovecot yes, Exchange limited, Gmail IMAP no). Requires capability detection. | |

**User's choice:** Subject normalization fallback (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Alpine.js client-side state (recommended) | Thread expansion/collapse managed entirely in browser via Alpine.js. No server round-trip. Read/unread reflects IMAP \Seen flag per message. Matches Phase 2 message list selection pattern. | ✓ |
| Livewire-managed with wire:navigate | Thread state in Livewire component. URL updates with thread ID when expanded (wire:navigate). Allows deep-linking to specific threads. More complex but enables shareable URLs. | |
| Persist expansion in user preferences | Save expanded thread IDs in DB per user. On folder reload, restore expanded state. Useful for power users but adds DB writes per interaction. | |

**User's choice:** Alpine.js client-side state (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Thread row with expandable children (recommended) | Single thread row in list showing: sender of latest message, subject, timestamp of latest, unread count badge, attachment indicator (if any in thread). Click expands inline to show all messages in thread. Matches Gmail/Outlook thread view. | ✓ |
| Flat list with thread indicators | Keep flat message list but add visual thread markers (vertical line connector, indentation, thread icon). Messages from same thread grouped visually but individually selectable. Simpler, preserves existing selection/bulk actions. | |
| Toggle: Threaded vs Flat view | User switches between threaded conversation view and traditional flat list via toolbar button. Both modes available. More flexible but doubles UI complexity. | |

**User's choice:** Thread row with expandable children (recommended)

---

## Search Architecture

| Option | Description | Selected |
|--------|-------------|----------|
| Index message_metadata on save/sync (recommended) | When ImapMailboxService syncs message metadata to DB, also update Scout search index. MessageMetadata model is Scout-searchable. Index: subject, from_address, from_name, to_address, snippet, body_text. Real-time index updates on folder sync. | ✓ |
| Batch index via scheduler | Run nightly/weekly scheduler job to rebuild full search index from IMAP. Handles initial import and drift. Simpler sync logic but search lags behind new messages. | |
| Hybrid: Index on-demand for current folder | Index messages when user searches. If index empty, trigger background fetch+index for that folder. Good for large mailboxes where full index is heavy. | |

**User's choice:** Index message_metadata on save/sync (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Global search + folder filter (recommended) | Single search bar searches ALL folders by default. Folder dropdown to restrict scope. Additional filters: date range, has attachment, read/unread, flagged. Matches Gmail/Outlook search pattern. | ✓ |
| Folder-scoped search only | Search only within currently selected folder. No global search. Simpler index, faster results per folder. User must switch folders to search elsewhere. | |
| Saved searches / smart folders | Allow saving common searches as virtual folders (e.g., "Unread with attachments", "From: boss@company.com last week"). Appears in folder sidebar. More powerful but adds UI complexity. | |

**User's choice:** Global search + folder filter (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| MySQL FULLTEXT relevance + date weighting (recommended) | Use MySQL's built-in relevance score (MATCH...AGAINST) as primary sort. Apply slight date decay (newer messages rank higher for same relevance). Highlight matched terms in subject/snippet using Scout's highlight feature. | ✓ |
| Pure date sort with relevance as tiebreaker | Sort by date descending (newest first). Relevance only used when dates identical. Simpler mental model for users. Less relevant results may appear before more relevant older ones. | |
| Custom ranking: sender importance + date + relevance | Weight frequent contacts higher, then date, then relevance. Requires tracking sender frequency. More personalized but complex to tune. | |

**User's choice:** MySQL FULLTEXT relevance + date weighting (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Instant search with debounce (recommended) | As user types (debounced 300ms), show results in dropdown below search bar. Click result to open message. Separate "Search results" page for full listing with filters. Matches Gmail/Outlook instant search. | ✓ |
| Traditional search page | Enter query → submit → dedicated results page with filters sidebar. No instant dropdown. Simpler implementation, works better on mobile. | |
| Command palette (Cmd+K) | Global search via command palette overlay. Keyboard-first, shows messages, contacts, folders, actions. Modern but adds significant UI complexity. | |

**User's choice:** Instant search with debounce (recommended)

---

## Contact Management

| Option | Description | Selected |
|--------|-------------|----------|
| Unified autocomplete: local contacts + cached IMAP recipients (recommended) | ContactAutocompleteCache already exists for IMAP recipients. Add local contacts table. Autocomplete searches both, merges results, deduplicates by email. Local contacts rank higher (user-curated). IMAP cache auto-expires. | ✓ |
| Separate sources with toggle | Two autocomplete modes: "My Contacts" (local only) and "Recent Recipients" (IMAP). User switches via tab. Cleaner separation but extra click to access IMAP recipients. | |
| Local contacts only, IMAP as import source | Users import from IMAP to local address book. Autocomplete only searches local contacts. IMAP scan becomes one-time import action. Simpler but loses auto-discovery of new recipients. | |

**User's choice:** Unified autocomplete: local contacts + cached IMAP recipients (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Minimal: name, email, phone, notes + groups (recommended) | Single contacts table with name, email, phone, notes, avatar. Groups as many-to-many (contact_groups pivot). Matches vCard 3.0 basics. Enough for autocomplete, display, import/export. | ✓ |
| Rich: multiple emails/phones, addresses, org, birthday, custom fields | Multiple email/phone entries per contact with type labels (work/home/mobile). Physical addresses, organization, title, birthday, anniversaries, custom key-value fields. Full vCard 4.0 support. More complete but complex UI. | |
| Extensible: core fields + JSON blob for extras | Core fields (name, primary_email, primary_phone) + JSON column for arbitrary extra data. Frontend renders known fields, ignores unknown. Easy to extend later without migrations. Balance of simplicity and flexibility. | |

**User's choice:** Minimal: name, email, phone, notes + groups (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Standard vCard 3.0 (recommended) | Export: Generate .vcf with FN, EMAIL, TEL, NOTE, CATEGORIES (for groups). Import: Parse vCard 3.0, map to contact fields, create/update by email match. Widely supported by Gmail, Outlook, Apple Contacts, Thunderbird. | ✓ |
| vCard 4.0 with fallback | Export vCard 4.0 (newer standard, more fields). Import supports both 3.0 and 4.0. Better future-proofing but more complex parser. Limited gain for minimal field set. | |
| CSV + vCard dual format | Support both CSV (columns: name,email,phone,notes,groups) and vCard. CSV easier for spreadsheet users. Export both formats. Import auto-detects format. More code but broader compatibility. | |

**User's choice:** Standard vCard 3.0 (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Contacts sidebar tab (recommended) | Add "Contacts" tab alongside "Folders" in left sidebar. Shows contact list with search, click to view/edit, "New Contact" button. Integrates with existing mailbox layout. Mobile: bottom sheet or drawer. | ✓ |
| Separate contacts page | Top-level navigation item (like Mailbox, Contacts, Settings). Full-page contact manager with list view, detail panel, bulk actions. More space but requires navigation away from mailbox. | |
| Composer-only autocomplete | No dedicated contacts UI. Contacts only appear in composer autocomplete. Manage via import/export only. Minimalist but users can't browse/edit contacts directly. | |

**User's choice:** Contacts sidebar tab (recommended)

---

## Labels System

| Option | Description | Selected |
|--------|-------------|----------|
| Local labels only (recommended) | Labels stored in DB only (labels table + message_labels pivot). Not synced to IMAP. Works on any IMAP server. Archive action = remove Inbox label + add Archive label + move to Archive folder (IMAP). Simple, portable, matches shared hosting constraint. | ✓ |
| IMAP KEYWORDS sync (RFC 3501) | Sync labels to IMAP keywords (user-defined flags). Messages can have multiple keywords. Requires server support (Dovecot yes, Exchange limited). Labels persist across clients. Complex: keyword length limits, charset issues, conflict resolution. | |
| Hybrid: Local labels + optional IMAP keyword sync | Local labels primary. Optional setting: "Sync labels to IMAP keywords" for servers that support it. When enabled, write both. Best of both worlds but doubles sync logic. | |

**User's choice:** Local labels only (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Move to Archive folder + remove Inbox label (recommended) | Archive button: IMAP MOVE message to Archive folder (\Archive SPECIAL-USE or heuristic). If message has "Inbox" label, remove it. If no Archive folder exists, create one. Matches user expectation: message leaves Inbox, goes to Archive. | ✓ |
| Label-only: Remove Inbox label, keep in current folder | Archive = just remove "Inbox" label. Message stays in whatever folder it's in (Inbox, Sent, etc). No IMAP move. Simpler but message still appears in folder views unless filtered by label. | |
| Configurable: User chooses Archive folder in settings | Settings: "Archive folder" dropdown (defaults to Archive). Archive action moves to chosen folder + removes Inbox label. Flexible for orgs with different folder structures. | |

**User's choice:** Move to Archive folder + remove Inbox label (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Color chips in message list, colored sidebar (recommended) | Each label has user-picked color (from palette). Message list: colored pills/chips showing labels. Sidebar: label list with color dot + name + unread count. Click label in sidebar filters message list to that label. Matches Gmail label UI. | ✓ |
| Text badges only, no colors | Labels shown as text badges (gray) in message list. Sidebar shows label names with counts. No color management. Simpler, accessible by default, less visual distinction. | |
| System colors only (no user picker) | Auto-assign colors from fixed palette (8-10 colors) based on label creation order. User cannot customize. Reduces decision fatigue, ensures contrast compliance. Less personalization. | |

**User's choice:** Color chips in message list, colored sidebar (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Inline in sidebar + manage modal (recommended) | Sidebar: hover label → edit/delete icons. "Create new label" button at bottom opens modal (name + color picker). Right-click label → context menu (rename, delete, change color). Familiar Gmail pattern. | ✓ |
| Dedicated labels settings page | Settings → Labels tab. Full table: name, color, message count, show/hide in sidebar, show/hide in message list. Bulk delete. More systematic but requires leaving mailbox view. | |
| Composer-driven: Create labels when applying | No separate label management. When applying label in composer or message viewer, type new label name → creates automatically. Delete unused labels via "clean up" button. Minimal UI but less discoverable. | |

**User's choice:** Inline in sidebar + manage modal (recommended)

---

## UI/UX Integration

| Option | Description | Selected |
|--------|-------------|----------|
| Top toolbar, above message list (recommended) | Persistent search bar in mailbox header (next to folder name, compose button). Always visible. Keyboard shortcut (/) focuses it. Matches Gmail/Outlook web layout. Works with wire:navigate for instant results. | ✓ |
| Sidebar top, above folders | Search at top of left sidebar. Collapses with sidebar on mobile. Less prominent but always accessible. Good if sidebar is primary navigation. | |
| Command palette (Cmd+K) only | No persistent search bar. Press Cmd+K (or /) to open search overlay. Cleaner UI, power-user friendly. Less discoverable for casual users. | |

**User's choice:** Top toolbar, above message list (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Toolbar toggle button (recommended) | Icon button in message list toolbar (next to sort/filter): "Threaded" ↔ "Flat". State persists per folder in localStorage. Click to switch instantly via Alpine.js (re-renders list). Familiar Gmail/Outlook pattern. | ✓ |
| Settings only | Threading preference in Settings → Mail Preferences. Applies globally. No per-folder toggle. Simpler but less flexible. | |
| Auto-detect: Thread when conversation-like | Auto-enable threading for folders with many replies (Inbox, Sent). Flat for others (Drafts, Trash). User can override. Smart but unpredictable. | |

**User's choice:** Toolbar toggle button (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Tabbed sidebar: Folders | Contacts | Labels (recommended) | Left sidebar has tabs at top: "Folders", "Contacts", "Labels". Click tab to switch panel. Folders panel shows folder tree. Contacts panel shows contact list with search. Labels panel shows label list. Mobile: tabs become bottom navigation or drawer sections. | ✓ |
| Separate drawers | Folder sidebar (left). Contacts opens as right drawer/side panel. Labels as filter chips above message list. More space for each but requires more screen width. | |
| Contacts as modal, Labels as filter bar | Contacts: click "To" in composer → contact picker modal. Labels: filter chips above message list. No persistent sidebar tabs. Minimalist but less discoverable. | |

**User's choice:** Tabbed sidebar: Folders | Contacts | Labels (recommended)

---

| Option | Description | Selected |
|--------|-------------|----------|
| Horizontal chip row with overflow (recommended) | Show up to 3 label chips horizontally. If more: show "+N" chip. Hover/tap "+N" → popover with all labels. Chips use label colors. Click chip → filters to that label. Matches Gmail label display. | ✓ |
| Vertical stack in message row | Labels stack vertically on left of subject. Takes more vertical space but shows all labels without overflow. Good for dense label usage. | |
| Single primary label + count | Show only first label (alphabetical or user-set primary) + badge with total label count. Click badge → expands all. Saves space but hides label info. | |

**User's choice:** Horizontal chip row with overflow (recommended)

---

## the agent's Discretion

- Database schema design for labels, message_labels, contacts, contact_groups tables (indexes, foreign keys, cascading)
- Thread tree data structure and rendering algorithm (Alpine.js component)
- Scout searchable configuration on MessageMetadata (which fields, weights, highlighting)
- Contact deduplication logic (exact email match vs fuzzy)
- vCard parser library choice (spatie/vcard-parser or similar)
- Label color palette (accessibility-compliant 10-12 colors)
- Mobile responsive behavior for tabbed sidebar (breakpoint, drawer vs tabs)
- Search results page layout (filters sidebar, results list, pagination)
- Keyboard shortcuts (/ for search, t for thread toggle, etc.)

## Deferred Ideas

None — discussion stayed within phase scope.