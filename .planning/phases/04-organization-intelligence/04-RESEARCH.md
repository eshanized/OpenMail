# Phase 4: Organization & Intelligence - Research

**Researched:** 2026-09-06
**Domain:** Email threading, full-text search, contact management, label organization
**Confidence:** HIGH

## Summary

Phase 4 adds intelligence and organization features to the OpenMail webmail application: conversation threading using JWZ algorithm, full-text search via Laravel Scout with MySQL FULLTEXT, local contacts with IMAP recipient integration and vCard import/export, and Gmail-style labels alongside folders. All features must work within shared hosting constraints (no Redis, no background workers, database-only infrastructure per ADR-003).

**Primary recommendation:** Extend existing MessageMetadata model with Scout Searchable trait for search, implement JWZ threading algorithm in a new ThreadBuilder service, use sabre/vobject for vCard 3.0 parsing, and add labels/contact tables with Alpine.js-driven UI components matching the approved UI-SPEC.

## User Constraints (from CONTEXT.md)

### Locked Decisions
- **D-01:** On-demand thread reconstruction — fetch headers for visible messages only, build thread tree when user opens folder. Lower memory, works with pagination.
- **D-02:** Subject normalization fallback for missing/corrupted headers — strip Re:/Fwd:, trim whitespace, group by normalized subject + ±2 day window.
- **D-03:** Alpine.js client-side state for thread expansion/collapse — managed entirely in browser, no server round-trip.
- **D-04:** Thread row with expandable children in message list — single thread row showing: sender of latest message, subject, timestamp of latest, unread count badge, attachment indicator.
- **D-05:** Index message_metadata on save/sync — MessageMetadata model is Scout-searchable. Index: subject, from_address, from_name, to_address, snippet, body_text. Real-time index updates on folder sync.
- **D-06:** Global search + folder filter — single search bar searches ALL folders by default. Folder dropdown to restrict scope. Additional filters: date range, has attachment, read/unread, flagged.
- **D-07:** MySQL FULLTEXT relevance + date weighting — use MySQL's built-in relevance score (MATCH...AGAINST) as primary sort. Apply slight date decay (newer messages rank higher for same relevance). Highlight matched terms via Scout.
- **D-08:** Instant search with debounce (300ms) — show results in dropdown below search bar. Click result to open message. Separate "Search results" page for full listing with filters.
- **D-09:** Unified autocomplete: local contacts + cached IMAP recipients — ContactAutocompleteCache (IMAP) + new contacts table. Autocomplete searches both, merges results, deduplicates by email. Local contacts rank higher. IMAP cache auto-expires.
- **D-10:** Minimal contact fields — name, email, phone, notes + groups (many-to-many contact_groups pivot). Matches vCard 3.0 basics.
- **D-11:** Standard vCard 3.0 import/export — export .vcf with FN, EMAIL, TEL, NOTE, CATEGORIES (groups). Import: parse vCard 3.0, map to fields, create/update by email match.
- **D-12:** Contacts sidebar tab — tabbed sidebar: "Folders" | "Contacts" | "Labels". Click tab to switch panel. Contacts panel shows contact list with search, click to view/edit, "New Contact" button.
- **D-13:** Local labels only — labels stored in DB only (labels table + message_labels pivot). Not synced to IMAP. Works on any IMAP server. Archive action = remove Inbox label + add Archive label + move to Archive folder (IMAP).
- **D-14:** Archive action = move to Archive folder + remove Inbox label — IMAP MOVE message to Archive folder (\Archive SPECIAL-USE or heuristic). If message has "Inbox" label, remove it. If no Archive folder exists, create one.
- **D-15:** Color chips in message list, colored sidebar — each label has user-picked color (from palette). Message list: colored pills/chips showing labels. Sidebar: label list with color dot + name + unread count. Click label in sidebar filters message list.
- **D-16:** Inline label management in sidebar + modal — hover label → edit/delete icons. "Create new label" button at bottom opens modal (name + color picker). Right-click → context menu (rename, delete, change color).
- **D-17:** Search bar in top toolbar above message list — persistent, always visible. Keyboard shortcut (/) focuses it. Works with wire:navigate for instant results.
- **D-18:** Thread view toggle button in message list toolbar — icon button next to sort/filter: "Threaded" ↔ "Flat". State persists per folder in localStorage. Click to switch instantly via Alpine.js.
- **D-19:** Tabbed sidebar: Folders | Contacts | Labels — left sidebar has tabs at top. Folders panel shows folder tree. Contacts panel shows contact list with search. Labels panel shows label list with counts. Mobile: tabs become bottom navigation or drawer sections.
- **D-20:** Horizontal label chip row with overflow — show up to 3 label chips horizontally. If more: show "+N" chip. Hover/tap "+N" → popover with all labels. Chips use label colors. Click chip → filters to that label.

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

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|--------------|----------------|-----------|
| Full-text search indexing | API / Backend | Database / Storage | Scout database engine runs on MySQL; indexing triggered on model save |
| Full-text search querying | API / Backend | Browser / Client | Livewire component handles search input, displays results via wire:navigate |
| Thread reconstruction | API / Backend | Browser / Client | Backend fetches headers; Alpine.js builds tree and manages expansion state |
| Contact management (CRUD) | API / Backend | Browser / Client | Laravel controllers + Livewire modals for create/edit/delete |
| Contact autocomplete | API / Backend | Browser / Client | Unified service merges local contacts + IMAP cache; Alpine.js dropdown |
| Labels CRUD | API / Backend | Browser / Client | Database-only labels; Livewire components for sidebar + modals |
| Label filtering | API / Backend | Browser / Client | Query scope on MessageMetadata; Alpine.js chip interactions |
| Archive action | API / Backend | — | IMAP MOVE + label updates; no client-side logic beyond optimistic UI |
| vCard import/export | API / Backend | Browser / Client | Backend parses/generates vCard; frontend handles file upload/download |

## Standard Stack

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| Laravel Scout | ^10.x (built-in) | Full-text search abstraction | Database engine uses MySQL FULLTEXT; no external service required [VERIFIED: laravel.com/docs/12.x/scout] |
| webklex/php-imap | 6.2.x | IMAP client | Already in use; fetches Message-ID, In-Reply-To, References headers for threading [VERIFIED: packagist] |
| sabre/vobject | ^4.5 | vCard 3.0 parsing & generation | Industry standard (26M+ downloads, 603 GitHub stars, actively maintained) [VERIFIED: packagist, github.com/sabre-io/vobject] |
| astrotomic/laravel-vcard | ^0.8 | Laravel wrapper for vCard | Fluent builder for vCard export; supports Laravel 12 [VERIFIED: packagist, github.com/Astrotomic/laravel-vcard] |
| spatie/laravel-csp | latest | CSP headers | Already in stack for Phase 5; Phase 4 can reuse [VERIFIED: AGENTS.md] |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Alpine.js | 3.17.x (via Livewire) | Thread expansion, search dropdown, tabs, modals | All client-side interactivity [VERIFIED: AGENTS.md] |
| Livewire | 3.8.x | Search, contacts, labels components | Server-driven UI with wire:navigate [VERIFIED: AGENTS.md] |
| Tailwind CSS | 4.3.x | Styling for chips, modals, dropdowns | Utility-first; color palette from UI-SPEC [VERIFIED: AGENTS.md] |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| sabre/vobject | jeroendesloovere/vcard | Simpler but only generates, doesn't parse; 4M downloads vs 26M |
| sabre/vobject | rumenx/php-vcard | Framework-agnostic but less maintained (4K downloads) |
| Laravel Scout (database) | Meilisearch | Better relevance/typo tolerance but requires separate service (violates shared hosting constraint) |
| Custom threading | webklex/php-imap threading | webklex doesn't expose threading; would need custom implementation anyway |

**Installation:**
```bash
composer require laravel/scout sabre/vobject astrotomic/laravel-vcard
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

**Version verification:**
- `composer show laravel/scout` → 10.x (Laravel 12 compatible)
- `composer show sabre/vobject` → 4.5.x (PHP 8.1+)
- `composer show astrotomic/laravel-vcard` → 0.8.x (Laravel 9-13 compatible)

## Package Legitimacy Audit

> **Required** whenever this phase installs external packages. Run the Package Legitimacy Gate protocol before completing this section.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| laravel/scout | Packagist | 8+ yrs | 50M+/wk | github.com/laravel/scout | OK | Approved |
| sabre/vobject | Packagist | 14+ yrs | 733K+/wk | github.com/sabre-io/vobject | OK | Approved |
| astrotomic/laravel-vcard | Packagist | 5+ yrs | 1.6K+/wk | github.com/Astrotomic/laravel-vcard | OK | Approved |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

*All packages verified against Packagist and GitHub — authoritative sources confirmed.*

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER (Client)                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ Search Bar  │  │ Thread View │  │ Sidebar     │             │
│  │ (Alpine.js) │  │ (Alpine.js) │  │ Tabs/Chips  │             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
│         │                │                │                    │
│         ▼                ▼                ▼                    │
│  ┌─────────────────────────────────────────────────────┐       │
│  │              Livewire Components                     │       │
│  │  MessageList • FolderSidebar • Composer • Search    │       │
│  └─────────────────────────────────────────────────────┘       │
└────────────────────────────┬────────────────────────────────────┘
                             │ wire:navigate / AJAX
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      API / BACKEND (Laravel)                    │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐             │
│  │ ThreadService│  │ SearchService│  │ ContactService│         │
│  │ (JWZ algo)  │  │ (Scout)     │  │ (vCard)     │             │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘             │
│         │                │                │                    │
│         ▼                ▼                ▼                    │
│  ┌─────────────────────────────────────────────────────┐       │
│  │              ImapMailboxService                      │       │
│  │  Folder ops • Message fetch • Header fetch • MOVE   │       │
│  └─────────────────────────────────────────────────────┘       │
└────────────────────────────┬────────────────────────────────────┘
                             │ IMAP/SMTP
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                    EXTERNAL MAIL SERVER                         │
│  (IMAP for read/threading • SMTP for send • SPECIAL-USE folders)│
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                    DATABASE (MySQL/MariaDB)                     │
│  ┌──────────┐ ┌────────────┐ ┌─────────┐ ┌────────────┐        │
│  │ messages │ │ labels     │ │ contacts│ │ contact_   │        │
│  │ _meta-   │ │ + pivots   │ │ + groups│ │ groups     │        │
│  │ data     │ │            │ │         │ │            │        │
│  └──────────┘ └────────────┘ └─────────┘ └────────────┘        │
│         ▲                                                        │
│         │ Scout Database Engine (FULLTEXT indexes)              │
│         └──────────────────────────────────────────────────────┘
```

### Recommended Project Structure
```
app/
├── Services/
│   ├── ThreadBuilder.php           # JWZ threading algorithm
│   ├── SearchService.php           # Scout search + filters
│   ├── ContactService.php          # Contact CRUD + autocomplete merge
│   ├── LabelService.php            # Label CRUD + message labeling
│   └── VCardService.php            # Import/export using sabre/vobject
├── Models/
│   ├── MessageMetadata.php         # + Searchable trait (extend)
│   ├── Label.php                   # NEW
│   ├── Contact.php                 # NEW
│   ├── ContactGroup.php            # NEW
│   └── ContactAutocompleteCache.php # existing
├── Livewire/Mailbox/
│   ├── MessageList.php             # extend for threading + search
│   ├── FolderSidebar.php           # extend for tabbed sidebar
│   ├── SearchBar.php               # NEW - top toolbar
│   ├── SearchResults.php           # NEW - dropdown + page
│   ├── ContactSidebar.php          # NEW - contacts tab panel
│   ├── ContactModal.php            # NEW - create/edit modal
│   ├── LabelSidebar.php            # NEW - labels tab panel
│   ├── LabelModal.php              # NEW - create/edit modal
│   └── ThreadRow.php               # NEW - expandable thread component
├── Http/Controllers/
│   ├── SearchController.php        # NEW - /search page
│   ├── ContactController.php       # NEW - CRUD + import/export
│   └── LabelController.php         # NEW - CRUD
database/migrations/
├── create_labels_table.php              # NEW
├── create_message_labels_table.php      # NEW
├── create_contacts_table.php            # NEW
├── create_contact_groups_table.php      # NEW
├── create_contact_group_contact_table.php # NEW
└── add_scout_columns_to_message_metadata.php # NEW
resources/views/
├── livewire/mailbox/
│   ├── message-list.blade.php      # extend for thread rows
│   ├── folder-sidebar.blade.php    # extend for tabs
│   ├── search-bar.blade.php        # NEW
│   ├── search-results-dropdown.blade.php # NEW
│   ├── search-results-page.blade.php # NEW
│   ├── contact-sidebar.blade.php   # NEW
│   ├── contact-modal.blade.php     # NEW
│   ├── label-sidebar.blade.php     # NEW
│   ├── label-modal.blade.php       # NEW
│   ├── thread-row.blade.php        # NEW
│   └── label-chips.blade.php       # NEW
```

### Pattern 1: JWZ Threading Algorithm (ThreadBuilder Service)
**What:** Reconstruct conversation threads from Message-ID, In-Reply-To, References headers using the JWZ algorithm (used by Mozilla Thunderbird, Gmail, mutt).

**When to use:** Building conversation view for message list — on-demand per folder page.

**Algorithm Steps (per JWZ spec):**
1. **Collect messages** — Fetch headers (Message-ID, In-Reply-To, References, Subject, Date) for visible page + thread roots
2. **Build reference tree** — Create nodes keyed by Message-ID; link children via In-Reply-To/References
3. **Prune empty containers** — Remove nodes with no corresponding message
4. **Group by subject** — For remaining rootless nodes, normalize subject (strip Re:/Fwd:, trim) and group by subject + ±2 day window
5. **Sort threads** — By date of latest message (descending)
6. **Render** — Return flat array of root threads with nested children for Alpine.js

**Example:**
```php
// app/Services/ThreadBuilder.php
namespace App\Services;

use App\Models\MessageMetadata;
use Illuminate\Support\Collection;

class ThreadBuilder
{
    public function buildThreads(Collection $messages): array
    {
        // 1. Index by Message-ID
        $byMessageId = $messages->keyBy('message_id')->filter(fn($m) => $m->message_id);
        
        // 2. Build parent-child relationships
        $children = [];
        $roots = [];
        
        foreach ($byMessageId as $message) {
            $parentId = $this->extractParentId($message);
            
            if ($parentId && isset($byMessageId[$parentId])) {
                $children[$parentId][] = $message;
            } else {
                $roots[] = $message;
            }
        }
        
        // 3. Subject-based grouping for orphans (D-02 fallback)
        $roots = $this->groupBySubject($roots);
        
        // 4. Sort by latest message date desc
        usort($roots, fn($a, $b) => $b->latestDate <=> $a->latestDate);
        
        // 5. Attach children recursively
        return $this->attachChildren($roots, $children);
    }
    
    private function extractParentId($message): ?string
    {
        // Prefer In-Reply-To, fallback to last References entry
        if ($message->in_reply_to) return $this->cleanMessageId($message->in_reply_to);
        if ($message->references) {
            $refs = array_filter(array_map('trim', explode(' ', $message->references)));
            return $refs ? $this->cleanMessageId(end($refs)) : null;
        }
        return null;
    }
    
    private function cleanMessageId(string $id): string
    {
        return trim($id, '<> ');
    }
    
    private function groupBySubject(array $messages): array
    {
        // Strip Re:/Fwd:, normalize whitespace, group by subject + 2-day window
        $groups = collect($messages)->groupBy(function ($m) {
            $subject = preg_replace('/^(Re|Fwd):\s*/i', '', $m->subject ?? '');
            $subject = trim(preg_replace('/\s+/', ' ', $subject));
            $dateKey = $m->date?->format('Y-m-d') ?? 'unknown';
            return strtolower($subject) . '|' . $dateKey;
        });
        
        return $groups->map(fn($g) => $g->sortByDesc('date')->first())->values()->all();
    }
    
    private function attachChildren(array $roots, array $children): array
    {
        foreach ($roots as &$root) {
            $root->children = $children[$root->message_id] ?? [];
            $root->children = $this->attachChildren($root->children, $children);
            $root->latestDate = $this->getLatestDate($root);
            $root->unreadCount = $this->countUnread($root);
        }
        return $roots;
    }
}
```

### Pattern 2: Scout Searchable MessageMetadata (SearchService)
**What:** Configure MessageMetadata for database-engine full-text search with field weights and highlighting.

**When to use:** All search operations — instant dropdown, results page, filtered search.

**Example:**
```php
// app/Models/MessageMetadata.php (extended)
use Laravel\Scout\Searchable;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Attributes\SearchUsingPrefix;

class MessageMetadata extends Model
{
    use HasFactory, Searchable;
    
    // ... existing code ...
    
    #[SearchUsingFullText(['subject', 'from_address', 'from_name', 'to_address', 'snippet', 'body_text'])]
    #[SearchUsingPrefix(['message_id'])]
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'folder_path' => $this->folder_path,
            'uid' => $this->uid,
            'message_id' => $this->message_id,
            'subject' => $this->subject,
            'from_address' => $this->from_address,
            'from_name' => $this->from_name,
            'to_address' => $this->to_address,
            'date' => $this->date?->timestamp,
            'snippet' => $this->snippet,
            'body_text' => $this->body_text, // NEW: populate during sync
            'has_attachments' => $this->has_attachments,
            'is_seen' => $this->is_seen,
            'is_flagged' => $this->is_flagged,
        ];
    }
    
    public function searchableAs(): string
    {
        return 'message_metadata_' . $this->user_id; // per-user index isolation
    }
    
    public function shouldBeSearchable(): bool
    {
        return true; // All messages searchable; folder filtering via where clauses
    }
}
```

```php
// app/Services/SearchService.php
namespace App\Services;

use App\Models\MessageMetadata;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SearchService
{
    public function search(int $userId, string $query, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $search = MessageMetadata::search($query)
            ->where('user_id', $userId)
            ->query(fn($q) => $this->applyFilters($q, $filters))
            ->orderByRaw("MATCH(subject, from_address, from_name, to_address, snippet, body_text) 
                AGAINST(? IN BOOLEAN MODE) DESC, date DESC", [$query]);
        
        if ($filters['folder'] ?? false) {
            $search->where('folder_path', $filters['folder']);
        }
        
        return $search->paginate($perPage);
    }
    
    public function instantSearch(int $userId, string $query, int $limit = 8): Collection
    {
        return MessageMetadata::search($query)
            ->where('user_id', $userId)
            ->take($limit)
            ->get(['id', 'subject', 'from_address', 'from_name', 'snippet', 'folder_path', 'date']);
    }
    
    private function applyFilters($query, array $filters)
    {
        if (!empty($filters['date_from'])) $query->where('date', '>=', $filters['date_from']);
        if (!empty($filters['date_to'])) $query->where('date', '<=', $filters['date_to']);
        if ($filters['has_attachment'] ?? false) $query->where('has_attachments', true);
        if (isset($filters['is_seen'])) $query->where('is_seen', $filters['is_seen']);
        if (isset($filters['is_flagged'])) $query->where('is_flagged', $filters['is_flagged']);
        if (!empty($filters['labels'])) $query->whereHas('labels', fn($q) => $q->whereIn('labels.id', $filters['labels']));
        
        return $query;
    }
}
```

### Pattern 3: vCard Import/Export (VCardService)
**What:** Parse vCard 3.0 files on import, generate vCard 3.0 on export using sabre/vobject.

**When to use:** Contacts import/export modal actions.

**Example:**
```php
// app/Services/VCardService.php
namespace App\Services;

use Sabre\VObject\Reader;
use Sabre\VObject\Writer;
use Sabre\VObject\Component\VCard;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Support\Collection;

class VCardService
{
    public function import(string $content, int $userId, string $conflictStrategy = 'skip'): array
    {
        $vcard = Reader::read($content);
        $results = ['imported' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];
        
        foreach ($vcard->children() as $card) {
            if (!$card instanceof VCard) continue;
            
            $email = (string) ($card->EMAIL ?? '');
            if (!$email) { $results['errors'][] = 'Missing email'; continue; }
            
            $existing = Contact::where('user_id', $userId)->where('email', $email)->first();
            
            if ($existing && $conflictStrategy === 'skip') {
                $results['skipped']++;
                continue;
            }
            
            $data = [
                'user_id' => $userId,
                'name' => (string) ($card->FN ?? ''),
                'email' => $email,
                'phone' => (string) ($card->TEL ?? ''),
                'notes' => (string) ($card->NOTE ?? ''),
            ];
            
            if ($existing && $conflictStrategy === 'update') {
                $existing->update($data);
                $this->syncGroups($existing, $card);
                $results['updated']++;
            } else {
                $contact = Contact::create($data);
                $this->syncGroups($contact, $card);
                $results['imported']++;
            }
        }
        
        return $results;
    }
    
    public function export(int $userId): string
    {
        $contacts = Contact::with('groups')->where('user_id', $userId)->get();
        $vcard = new \Sabre\VObject\Component\VCard();
        
        foreach ($contacts as $contact) {
            $card = new VCard();
            $card->add('FN', $contact->name);
            $card->add('EMAIL', $contact->email);
            if ($contact->phone) $card->add('TEL', $contact->phone);
            if ($contact->notes) $card->add('NOTE', $contact->notes);
            if ($contact->groups->isNotEmpty()) {
                $card->add('CATEGORIES', $contact->groups->pluck('name')->implode(','));
            }
            $vcard->add($card);
        }
        
        return Writer::write($vcard);
    }
    
    private function syncGroups(Contact $contact, VCard $card): void
    {
        $categories = (string) ($card->CATEGORIES ?? '');
        if (!$categories) return;
        
        $groupNames = array_map('trim', explode(',', $categories));
        $groups = ContactGroup::firstOrCreateForUser($contact->user_id, $groupNames);
        $contact->groups()->sync($groups->pluck('id'));
    }
}
```

### Anti-Patterns to Avoid
- **Fetching all message headers for threading:** Don't fetch entire folder — only visible page + thread roots (D-01). Full folder fetch kills performance on large mailboxes.
- **Storing threads in database:** Threads are reconstructed on-demand; storing them creates sync complexity with IMAP.
- **Using Redis for search:** Violates ADR-003 (database-only infrastructure). Scout database engine is purpose-built for this.
- **Hand-rolling vCard parsing:** vCard 3.0 has complex folding, escaping, and encoding rules. sabre/vobject handles all edge cases.
- **Syncing labels to IMAP:** IMAP has no standard label support (D-13). Labels are local-only; Archive is the only IMAP-synced action.
- **Using `wire:model.live` for search input:** Sends request on every keystroke. Use debounced Alpine.js input → Livewire method call (D-08).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Email thread reconstruction | Custom threading logic | JWZ algorithm implementation | Handles missing headers, subject changes, cross-folder threads, circular refs — battle-tested for 20+ years |
| Full-text search | Custom MySQL queries with MATCH...AGAINST | Laravel Scout database engine | Automatic index sync, highlighting, pagination, where clauses, relevance + date weighting |
| vCard 3.0 parsing/generation | Regex string manipulation | sabre/vobject | RFC 6350 compliant; handles line folding, escaping, charsets, multiple values, nested properties |
| HTML sanitization for search snippets | Custom strip_tags | HTMLPurifier + DOMPurify (existing) | Phase 2 already established dual sanitization pipeline; reuse MessageSanitizer |
| Contact deduplication | Custom email comparison | Exact case-insensitive email match | Email is unique identifier per RFC; fuzzy matching causes false merges |
| Accessible color palette | Random color picker | Predefined 10-color palette (UI-SPEC) | WCAG 4.5:1 contrast on white verified; color-blind safe |

**Key insight:** Email threading, vCard parsing, and full-text search are deceptively complex domains with decades of edge cases. The standard libraries have solved problems you haven't encountered yet (e.g., JWZ handles "dummy" messages for missing parents; sabre/vobject handles vCard line folding at 75 chars; Scout handles MySQL FULLTEXT stopword thresholds).

## Common Pitfalls

### Pitfall 1: Thread Reconstruction Misses Cross-Folder Replies
**What goes wrong:** User replies to a message in Sent folder; thread doesn't appear in Inbox.
**Why it happens:** Threading only runs on current folder's messages.
**How to avoid:** When building threads for a folder, also fetch headers for messages in other folders that share Message-ID/References (use IMAP SEARCH HEADER Message-ID). Cache cross-folder thread roots.
**Warning signs:** Thread view shows fragmented conversations; "Reply" appears as separate thread.

### Pitfall 2: MySQL FULLTEXT Stopword Threshold
**What goes wrong:** Common words like "the", "and", "meeting" return no results.
**Why it happens:** MySQL default stopword list + 50% threshold (word must appear in <50% of rows).
**How to avoid:** Set `ft_min_word_len=3`, `innodb_ft_enable_stopword=OFF` in MySQL config; or use Scout's `SearchUsingFullText` which handles this. For shared hosting where config can't change, supplement with `LIKE` fallback (Scout does this automatically).
**Warning signs:** Short/common queries return empty results.

### Pitfall 3: Scout Index Out of Sync
**What goes wrong:** New messages not searchable; deleted messages still appear.
**Why it happens:** Model observers not firing (bulk inserts, direct DB writes) or `withoutSyncingToSearch` misuse.
**How to avoid:** Always use Eloquent `save()`/`delete()`; after IMAP sync, call `$messages->searchable()`; use `SearchIndexShouldBeUpdated` to limit reindexing.
**Warning signs:** Search results stale after folder sync.

### Pitfall 4: vCard Import Creates Duplicates
**What goes wrong:** Same contact imported multiple times with slight variations.
**Why it happens:** Case-sensitive email comparison; whitespace differences; multiple EMAIL properties.
**How to avoid:** Normalize email to lowercase + trim; use `Contact::firstOrCreate(['user_id', 'email' => strtolower(trim($email))])`; handle multiple EMAIL values (prefer TYPE=HOME/WORK).
**Warning signs:** Contacts list grows unexpectedly after import.

### Pitfall 5: Label Filter Performance on Large Mailboxes
**What goes wrong:** Filtering by label takes 5+ seconds.
**Why it happens:** `message_labels` pivot table missing composite index; N+1 queries loading labels per message.
**How to avoid:** Add index `['user_id', 'label_id', 'message_metadata_id']`; eager load labels via `with('labels')` in MessageList; use Scout `whereHas('labels')` for filtered search.
**Warning signs:** Slow message list render when label filter active.

### Pitfall 6: Alpine.js Memory Leaks in Thread Expansion
**What goes wrong:** Browser slows down after expanding/collapsing many threads.
**Why it happens:** Event listeners not cleaned up; large `x-data` objects retained.
**How to avoid:** Use `x-show` (not `x-if`) for thread children — keeps DOM but hides; limit expanded threads to 1 at a time; destroy component on folder change.
**Warning signs:** DevTools shows growing detached DOM nodes.

## Code Examples

Verified patterns from official sources:

### Thread Row Component (Alpine.js + Livewire)
```blade
{{-- resources/views/livewire/mailbox/thread-row.blade.php --}}
@props(['thread', 'depth' => 0, 'isExpanded' => false])

<div x-data="{ expanded: @js($isExpanded) }" 
     class="group @if($depth > 0) pl-3 border-l border-gray-200 @endif"
     x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 max-h-0"
     x-transition:enter-end="opacity-100 max-h-96"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 max-h-96"
     x-transition:leave-end="opacity-0 max-h-0">
    
    {{-- Parent thread row --}}
    <div class="flex items-center py-3 px-4 hover:bg-gray-50 cursor-pointer"
         @click="expanded = !expanded"
         @keydown.space.prevent="expanded = !expanded"
         @keydown.enter.prevent="expanded = !expanded"
         tabindex="0"
         role="button"
         aria-expanded="{{ $isExpanded ? 'true' : 'false' }}">
        
        <input type="checkbox" wire:model="selectedUids.{{ $thread->uid }}" class="w-4 h-4 mr-3">
        
        <button wire:click="toggleStar({{ $thread->uid }})" class="mr-3">
            @if($thread->is_flagged)
                <svg class="w-5 h-5 text-amber-400 fill-current" stroke="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            @else
                <svg class="w-5 h-5 text-gray-400" stroke="currentColor"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
            @endif
        </button>
        
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
                <span class="font-medium truncate">{{ $thread->from_display }}</span>
                @if($thread->unread_count > 1)
                    <span class="inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-blue-600 rounded-full">
                        {{ $thread->unread_count }}
                    </span>
                @elseif($thread->is_unread)
                    <span class="w-2 h-2 bg-blue-600 rounded-full"></span>
                @endif
            </div>
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span class="truncate">{{ $thread->subject }}</span>
                {{-- Label chips (max 3 + overflow) --}}
                @foreach($thread->labels->take(3) as $label)
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full text-white"
                          style="background-color: {{ $label->color }}">
                        {{ $label->name }}
                    </span>
                @endforeach
                @if($thread->labels->count() > 3)
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600"
                          @click.stop="$dispatch('show-label-popover', { labels: @js($thread->labels->slice(3)) })">
                        +{{ $thread->labels->count() - 3 }}
                    </span>
                @endif
            </div>
        </div>
        
        <span class="text-sm text-gray-400 mr-3">{{ $thread->formatted_date }}</span>
        
        @if($thread->has_attachments)
            <svg class="w-5 h-5 text-gray-400 mr-3" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        @endif
        
        <svg class="w-5 h-5 text-gray-500 transition-transform duration-150 {{ $isExpanded ? 'rotate-90' : '' }}"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
        </svg>
    </div>
    
    {{-- Children (expanded inline) --}}
    <div x-show="expanded" x-cloak>
        @foreach($thread->children as $child)
            <x-mailbox.thread-row :thread="$child" :depth="{{ $depth + 1 }}" :isExpanded="false" />
        @endforeach
        
        @if($thread->children->count() >= 10)
            <div class="px-4 py-2 text-center text-sm text-blue-600 hover:text-blue-800 cursor-pointer"
                 wire:click="loadMoreThreads({{ $thread->id }})">
                Load 10 more messages...
            </div>
        @endif
    </div>
</div>
```

### Search Bar with Instant Dropdown
```blade
{{-- resources/views/livewire/mailbox/search-bar.blade.php --}}
<div x-data="{
    query: '',
    results: [],
    loading: false,
    open: false,
    debounce: null
}"
     class="relative">
    
    <label for="search-input" class="sr-only">Search all mail</label>
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               id="search-input"
               x-model="query"
               @input="debouncedSearch()"
               @keydown.escape="open = false"
               @keydown.arrow-down.prevent="focusFirstResult()"
               @focus="open = query.length >= 2"
               @blur="setTimeout(() => open = false, 200)"
               class="w-full pl-10 pr-10 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
               placeholder="Search all mail (shortcut: /)"
               autocomplete="off">
        <span x-show="loading" class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
    </div>
    
    <div x-show="open && results.length > 0"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 transform -translate-y-1"
         x-transition:enter-end="opacity-100 transform translate-y-0"
         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-96 overflow-auto">
        
        <template x-for="(result, index) in results" :key="result.id">
            <a href="#" 
               @click.prevent="openResult(result)"
               class="block px-4 py-3 hover:bg-gray-50 border-t border-gray-100 first:border-t-0"
               :class="{ 'bg-blue-50': index === highlightedIndex }"
               x-ref="resultItem">
                <div class="flex items-start gap-3">
                    <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-medium text-sm">
                        {{ result.from_name?.charAt(0) || result.from_address?.charAt(0) || '?' }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium truncate">{{ result.from_display }}</span>
                            <span class="text-gray-400">{{ result.folder_badge }}</span>
                            <span class="text-gray-400">{{ result.date }}</span>
                        </div>
                        <div class="text-sm text-gray-500 truncate" x-html="highlight(result.snippet, query)"></div>
                    </div>
                </div>
            </a>
        </template>
        
        <div class="px-4 py-2 border-t border-gray-100">
            <a wire:navigate href="/search?q={{ urlencode(query) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                View all results →
            </a>
        </div>
    </div>
    
    <div x-show="open && query.length >= 2 && results.length === 0 && !loading"
         class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl p-4 text-center text-gray-500 text-sm">
        No messages found
    </div>
</div>

<script>
    function highlight(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
        return text.replace(regex, '<mark class="bg-yellow-100 text-yellow-900 px-0.5 rounded">$1</mark>');
    }
</script>
```

### Contact Autocomplete Merge (D-09)
```php
// app/Services/ContactAutocompleteService.php (extended)
public function searchUnified(int $userId, string $query, int $limit = 10): Collection
{
    // Local contacts (exact + prefix match, ranked by usage)
    $local = Contact::where('user_id', $userId)
        ->where(function ($q) use ($query) {
            $q->where('email', 'LIKE', "{$query}%")
              ->orWhere('name', 'LIKE', "{$query}%");
        })
        ->orderByDesc('usage_count')
        ->limit($limit)
        ->get(['id', 'name', 'email', 'phone', 'avatar_color'])
        ->map(fn($c) => ['source' => 'local', 'name' => $c->name, 'email' => $c->email, 'phone' => $c->phone, 'avatar' => $c->avatar_color, 'frequency' => $c->usage_count]);
    
    // IMAP cache (frequency ranked)
    $imap = $this->search($userId, $query, $limit)
        ->map(fn($c) => ['source' => 'imap', 'name' => $c->name, 'email' => $c->email, 'phone' => null, 'avatar' => null, 'frequency' => $c->frequency]);
    
    // Merge + deduplicate (local wins on exact email match)
    $merged = $local->concat($imap)
        ->unique('email', true) // strict comparison
        ->sortByDesc('frequency')
        ->take($limit)
        ->values();
    
    return $merged;
}
```

## Runtime State Inventory

> Not applicable — this is a greenfield feature phase (no rename/refactor/migration).

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| IMAP server-side THREAD command | Client-side JWZ reconstruction | ~2010 (Gmail web) | Works on any IMAP server; no server support needed |
| Separate search service (Elasticsearch) | Laravel Scout database engine | Laravel 8+ (2020) | Zero infrastructure; shared hosting compatible |
| Server-side HTML sanitization only | Dual sanitization (HTMLPurifier + DOMPurify) | 2024 (OWASP research) | Eliminates parser differential attacks |
| jQuery/vanilla JS for UI | Alpine.js + Livewire 3 | 2021+ | Reactive, declarative, no build step for shared hosting |
| Global label namespace | Per-user labels (D-13) | Gmail model | Multi-user isolation; no IMAP sync complexity |

**Deprecated/outdated:**
- **PHP IMAP extension (ext-imap):** Requires server install; not on shared hosting. Use webklex/php-imap (pure PHP).
- **Roundcube/IMAP native threading:** Not portable; requires server support.
- **Meilisearch/Elasticsearch for search:** Infrastructure violation per ADR-003.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | MySQL FULLTEXT supports relevance scoring with MATCH...AGAINST on InnoDB | Standard Stack | Search relevance degrades to LIKE-only; mitigate with Scout's automatic fallback |
| A2 | sabre/vobject parses all vCard 3.0 variants in the wild | vCard Service | Import fails for malformed vCards; mitigate with try/catch + error reporting |
| A3 | JWZ algorithm implementation handles all edge cases (missing parents, subject changes) | ThreadBuilder | Broken thread trees; mitigate with subject fallback (D-02) |
| A4 | 10-color palette in UI-SPEC meets WCAG 4.5:1 on white | UI-SPEC / Label System | Accessibility audit failure; verify with WebAIM contrast checker before ship |
| A5 | Scout database engine handles 100K+ messages per user | Search Architecture | Performance degradation; mitigate with pagination + simplePaginate |
| A6 | IMAP servers support \Archive SPECIAL-USE or "Archive" heuristic | Archive Action (D-14) | Archive fails silently; mitigate with FolderMapper fallback + user config |

## Open Questions (RESOLVED)

1. **Thread header fetching optimization** ✅ RESOLVED in Plan 01 (Task 1 + Task 2)
   - What we know: D-01 says "fetch headers for visible messages only"
   - What was unclear: How to fetch headers for thread roots that aren't on current page?
   - Resolution: Added `thread_header_cache` table (migration 000007) with TTL (24 hours). ThreadBuilder (Plan 01 Task 2) queries cache for missing parent Message-IDs. If not cached, caller fetches from IMAP and populates cache. Implemented in `ThreadBuilder::resolveMissingParentsFromCache()`.

2. **Search snippet generation** ✅ RESOLVED in Plan 02 (Task 1)
   - What we know: Scout highlights matches in `toSearchableArray` fields
   - What was unclear: How to generate contextual snippets from body_text?
   - Resolution: Added `body_text` column to message_metadata (migration 000006). `MessageMetadata::extractBodyText()` extracts first 500 chars of plain text during sync. Scout highlights on `body_text` field for contextual snippets.

3. **Contact avatar generation** ✅ RESOLVED in Plan 03 (Task 1)
   - What we know: UI-SPEC shows "Avatar (initial, colored bg from hash)"
   - What was unclear: Deterministic color-from-email algorithm?
   - Resolution: Contact model `colorFromEmail()` static method uses CRC32 modulo 10 against 10-color palette: `['bg-blue-500','bg-green-600','bg-red-600','bg-yellow-600','bg-purple-600','bg-pink-600','bg-orange-600','bg-teal-600','bg-indigo-600','bg-gray-500']`. Same email always produces same color.

4. **Mobile sidebar behavior (D-19)** ✅ RESOLVED in Plan 09 (Task 1)
   - What we know: "Tabs become bottom navigation or drawer sections"
   - What was unclear: Which approach? Bottom nav (fixed) vs drawer (slide-over)?
   - Resolution: Bottom navigation for <768px (fixed bottom, safe-area-inset-bottom, z-40). 3 equal-width buttons. Panels become full-screen drawers sliding up from bottom (x-transition, fixed inset-0 z-50). Backdrop click closes. Matches UI-SPEC exactly.

## Environment Availability

> Skip this section if the phase has no external dependencies (code/config-only changes).

| Dependency | Required By | Available | Version | Fallback |
|------------|-------------|-----------|---------|----------|
| MySQL/MariaDB | Scout FULLTEXT indexes | ✓ | 8.0+ / 10.6+ | — |
| PHP mbstring | sabre/vobject | ✓ | 8.3+ | — |
| Composer | Package installation | ✓ | 2.x | — |
| IMAP server | Thread headers, contact sync | ✓ | Any | — |

**Missing dependencies with no fallback:** None
**Missing dependencies with fallback:** None

## Validation Architecture

### Test Framework
| Property | Value |
|----------|-------|
| Framework | Pest (Laravel default) + PHPUnit |
| Config file | `phpunit.xml` (existing) |
| Quick run command | `./vendor/bin/pest --filter=Phase4` |
| Full suite command | `./vendor/bin/pest` |

### Phase Requirements → Test Map
| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| THR-01 | Conversation grouping renders | feature | `pest tests/Feature/ThreadingTest.php` | ❌ Wave 0 |
| THR-02 | Message-ID/In-Reply-To/References threading | unit | `pest tests/Unit/ThreadBuilderTest.php` | ❌ Wave 0 |
| THR-03 | Subject normalization fallback | unit | `pest tests/Unit/ThreadBuilderTest.php::testSubjectFallback` | ❌ Wave 0 |
| THR-04 | Thread expand/collapse | feature | `pest tests/Feature/ThreadUITest.php` | ❌ Wave 0 |
| THR-05 | Stable rendering across sources | unit | `pest tests/Unit/ThreadBuilderTest.php::testStableOrder` | ❌ Wave 0 |
| SRCH-01 | Full-text search across fields | feature | `pest tests/Feature/SearchTest.php::testFullTextSearch` | ❌ Wave 0 |
| SRCH-02 | Folder filter | feature | `pest tests/Feature/SearchTest.php::testFolderFilter` | ❌ Wave 0 |
| SRCH-03 | Date range filter | feature | `pest tests/Feature/SearchTest.php::testDateRangeFilter` | ❌ Wave 0 |
| SRCH-04 | Has attachment filter | feature | `pest tests/Feature/SearchTest.php::testAttachmentFilter` | ❌ Wave 0 |
| SRCH-05 | Read/unread filter | feature | `pest tests/Feature/SearchTest.php::testReadUnreadFilter` | ❌ Wave 0 |
| SRCH-06 | Search result highlighting | unit | `pest tests/Unit/SearchHighlightTest.php` | ❌ Wave 0 |
| SRCH-07 | Application-level indexing | feature | `pest tests/Feature/SearchIndexingTest.php` | ❌ Wave 0 |
| CONT-01 | Recent recipients auto-populated | feature | `pest tests/Feature/ContactTest.php::testRecentRecipients` | ❌ Wave 0 |
| CONT-02 | Contact CRUD | feature | `pest tests/Feature/ContactTest.php::testCrud` | ❌ Wave 0 |
| CONT-03 | Composer autocomplete | feature | `pest tests/Feature/ContactAutocompleteTest.php` | ❌ Wave 0 |
| CONT-04 | Contact editing | feature | `pest tests/Feature/ContactTest.php::testEdit` | ❌ Wave 0 |
| CONT-05 | Contact groups | feature | `pest tests/Feature/ContactTest.php::testGroups` | ❌ Wave 0 |
| CONT-06 | vCard import/export | feature | `pest tests/Feature/VCardTest.php` | ❌ Wave 0 |
| LBL-01 | Labels alongside folders | feature | `pest tests/Feature/LabelTest.php::testSidebar` | ❌ Wave 0 |
| LBL-02 | Apply/remove labels | feature | `pest tests/Feature/LabelTest.php::testApplyRemove` | ❌ Wave 0 |
| LBL-03 | Label sidebar with counts | feature | `pest tests/Feature/LabelTest.php::testCounts` | ❌ Wave 0 |
| LBL-04 | Color-coded labels | feature | `pest tests/Feature/LabelTest.php::testColors` | ❌ Wave 0 |
| LBL-05 | Archive action | feature | `pest tests/Feature/ArchiveTest.php` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `./vendor/bin/pest --filter=Phase4`
- **Per wave merge:** `./vendor/bin/pest`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Unit/ThreadBuilderTest.php` — covers THR-01 through THR-05
- [ ] `tests/Feature/ThreadingTest.php` — covers THR-01, THR-04
- [ ] `tests/Feature/SearchTest.php` — covers SRCH-01 through SRCH-05
- [ ] `tests/Unit/SearchHighlightTest.php` — covers SRCH-06
- [ ] `tests/Feature/SearchIndexingTest.php` — covers SRCH-07
- [ ] `tests/Feature/ContactTest.php` — covers CONT-01 through CONT-05
- [ ] `tests/Feature/ContactAutocompleteTest.php` — covers CONT-03
- [ ] `tests/Feature/VCardTest.php` — covers CONT-06
- [ ] `tests/Feature/LabelTest.php` — covers LBL-01 through LBL-04
- [ ] `tests/Feature/ArchiveTest.php` — covers LBL-05
- [ ] `tests/Unit/VCardServiceTest.php` — vCard import/export edge cases
- [ ] Framework install: `composer require laravel/scout sabre/vobject astrotomic/laravel-vcard --dev` (if not in prod)

## Security Domain

> Required when `security_enforcement` is enabled (absent = enabled).

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | — |
| V3 Session Management | no | — |
| V4 Access Control | yes | Per-user scoping on all queries (user_id FK) |
| V5 Input Validation | yes | Scout query sanitization; vCard parse validation; label name length/unique |
| V6 Cryptography | no | — |
| V7 Error Handling | yes | Graceful degradation on IMAP errors; no stack traces in UI |
| V8 Data Protection | yes | No plaintext passwords; IMAP password encrypted in session only |
| V9 Communication Security | yes | TLS enforcement for IMAP/SMTP (AUTH-08, existing) |
| V10 Malicious Code | yes | DOMPurify on search snippets; HTMLPurifier on message body |
| V11 Business Logic | yes | Archive moves to IMAP folder + label sync; no data loss |
| V12 File Upload | yes | vCard upload: validate MIME, size limit, parse safely |
| V13 API Security | yes | CSRF on all forms; rate limiting on search/contact/label endpoints |
| V14 Configuration | yes | Scout driver=database; no external search service config |

### Known Threat Patterns for {Laravel + Livewire + IMAP}

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| Search injection (SQLi via Scout) | Tampering | Scout escapes queries; use `where` clauses not raw SQL |
| XSS via search highlight | XSS | Highlight applied AFTER DOMPurify; `mark` tag only |
| vCard parsing XXE/DoS | DoS | sabre/vobject has built-in limits; set `Reader::read($content, ['maxDepth' => 10])` |
| Label/Contact IDOR | Information Disclosure | All queries scoped to `user_id`; policies on models |
| IMAP credential leakage | Information Disclosure | Password only in encrypted session; never logged |
| Thread reconstruction DoS (deep nesting) | DoS | Limit thread depth to 50; max 500 messages per thread |

## Sources

### Primary (HIGH confidence)
- [Laravel Scout Database Engine](https://laravel.com/docs/12.x/scout#database-engine) — Configuration, SearchUsingFullText attributes, highlighting, pagination [VERIFIED: laravel.com/docs/12.x/scout]
- [Livewire Navigate](https://livewire.laravel.com/docs/navigate) — wire:navigate, prefetching, persist, data-current [VERIFIED: livewire.laravel.com/docs/navigate]
- [Alpine.js x-transition](https://alpinejs.dev/directives/transition) — enter/leave classes, duration, opacity, scale, origin modifiers [VERIFIED: alpinejs.dev/directives/transition]
- [sabre/vobject](https://github.com/sabre-io/vobject) — vCard 3.0 RFC 6350 parsing/generation, 26M+ downloads [VERIFIED: packagist, github.com/sabre-io/vobject]
- [astrotomic/laravel-vcard](https://github.com/Astrotomic/laravel-vcard) — Laravel wrapper, fluent builder, Laravel 12 support [VERIFIED: packagist, github.com/Astrotomic/laravel-vcard]

### Secondary (MEDIUM confidence)
- JWZ Threading Algorithm — Standard algorithm described in jwz.org/doc/threading.html; implemented in Thunderbird, mutt, Gmail [CITED: Wikipedia "Email threading", various OSS implementations]
- WebAIM Contrast Checker — WCAG 4.5:1 requirements for normal text on white [CITED: webaim.org/resources/contrastchecker/]

### Tertiary (LOW confidence)
- MySQL FULLTEXT stopword/threshold behavior on shared hosting — varies by host config [ASSUMED]

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — All packages verified on Packagist/GitHub with version compatibility confirmed
- Architecture: HIGH — Patterns follow established Phase 1-3 codebase conventions
- Pitfalls: HIGH — Based on documented MySQL/Scout/JWZ/vCard edge cases
- Search highlighting: MEDIUM — Scout docs confirm highlighting but database engine specifics less documented
- JWZ implementation details: MEDIUM — Algorithm well-known but PHP implementation requires custom code

**Research date:** 2026-09-06
**Valid until:** 2026-12-06 (90 days — stable stack, slow-moving libraries)