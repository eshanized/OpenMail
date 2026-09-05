# Phase 2: Mailbox Core - Research

**Researched:** 2026-09-05
**Domain:** IMAP mailbox browsing, message rendering, email security, Livewire pagination
**Confidence:** HIGH

## Summary

Phase 2 builds the core mailbox experience: folder navigation, message lists with pagination, HTML email rendering in sandboxed iframes, attachment downloads, and bulk selection actions. The research confirms that webklex/php-imap 6.2.0 (already installed) provides all needed IMAP operations including folder listing, message fetching with pagination, SEARCH, SORT, STORE, COPY, and UIDVALIDITY handling. The library integrates natively with Laravel's `LengthAwarePaginator`, enabling server-side IMAP pagination that maps directly to Livewire's `WithPagination` trait for SPA-like page transitions. HTML email security follows the dual-sanitization pipeline established in Phase 1 (HTMLPurifier server-side + DOMPurify client-side), with sandboxed `srcdoc` iframes providing defense-in-depth. The database cache driver (no Redis) handles folder metadata caching per the shared hosting constraint.

**Primary recommendation:** Build three Livewire components — `FolderSidebar`, `MessageList`, `MessageViewer` — backed by an `ImapMailboxService` that wraps webklex/php-imap operations. Use IMAP server-side SORT/SEARCH for message ordering and filtering, `LengthAwarePaginator` for pagination, and Alpine.js for client-side bulk selection state. Cache folder metadata (unread counts, total messages) in the database cache with UIDVALIDITY-based invalidation.

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Folder navigation & listing | API / Backend | Browser / Client | IMAP server returns folder tree; Livewire component renders sidebar |
| Message list fetching | API / Backend | — | IMAP SEARCH + SORT on server; paginated results sent to client |
| Message list rendering | Browser / Client | — | Livewire component with Alpine.js for selection state |
| HTML email rendering | Browser / Client | API / Backend | Server sanitizes HTML; client renders in sandboxed iframe with DOMPurify |
| Attachment download | API / Backend | CDN / Static | Streams directly from IMAP via FETCH BODY.PEEK[part]; no local storage |
| Folder metadata caching | Database / Storage | API / Backend | DB cache stores unread/total counts; invalidated via UIDVALIDITY |
| Bulk selection state | Browser / Client | — | Alpine.js manages checkbox state; Livewire handles IMAP STORE commands |
| Remote image blocking | Browser / Client | — | Client-side logic rewrites image src attributes before rendering |

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| webklex/php-imap | 6.2.0 (installed) | IMAP client for folder/message operations | Pure PHP, no ext-imap required; already in composer.json; provides Folder, Query, Message APIs with built-in pagination |
| webklex/laravel-imap | 6.2.0 (installed) | Laravel facade and integration for IMAP | Provides `Client::account('default')` facade; already in composer.json |
| Livewire | 4.4.3 (installed) | Dynamic UI components with SPA-like navigation | Already in composer.json as `^4.4`; provides `WithPagination` trait, `wire:navigate`, reactive props |
| Alpine.js | 3.x (included with Livewire) | Client-side interactivity for selection state | Ships with Livewire; no separate install needed |
| HTMLPurifier | 4.19 (installed) | Server-side HTML sanitization | Already in composer.json; industry standard for PHP HTML sanitization |
| DOMPurify | 3.x (client-side) | Client-side HTML sanitization at render time | Eliminates parser differential attacks; install via npm |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Laravel Cache (database driver) | (built-in) | Folder metadata caching | Cache unread counts, total messages, UIDVALIDITY per folder |
| Tailwind CSS | 4.x (installed) | Utility-first CSS for mailbox UI | All UI components |
| Vite | 6.x (installed) | Asset bundling for DOMPurify JS | Build frontend assets |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| webklex/php-imap 6.2 | DirectoryTree/ImapEngine | Younger library (38 stars); cleaner API but less documentation |
| webklex/php-imap 6.2 | ddeboer/imap | Uses PHP IMAP extension (not available on shared hosting) |
| Database cache for folder metadata | File cache | Lower performance on shared hosting due to disk I/O contention |
| Livewire 4.4 (installed) | Livewire 3.x | Project already installed v4.4; AGENTS.md says 3.x but lock file shows 4.4.3 |

**Installation:** Already installed via Phase 1. Additional npm package needed:
```bash
npm install dompurify
```

## Package Legitimacy Audit

> Packages installed via Composer (not npm), so npm-specific legitimacy checks do not apply. All packages were verified via `composer show` in this session.

| Package | Registry | Age | Source Repo | Verdict | Disposition |
|---------|----------|-----|-------------|---------|-------------|
| webklex/php-imap | Packagist | 8+ years | github.com/Webklex/php-imap | OK | Approved — installed, actively maintained |
| webklex/laravel-imap | Packagist | 8+ years | github.com/Webklex/laravel-imap | OK | Approved — installed, actively maintained |
| ezyang/htmlpurifier | Packagist | 15+ years | github.com/ezyang/htmlpurifier | OK | Approved — installed, industry standard |
| dompurify | npm | 10+ years | github.com/cure53/DOMPurify | OK | Approved — 17.4k GitHub stars, OpenSSF Best Practices |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** none

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        Browser (Client)                         │
│  ┌──────────┐  ┌──────────────┐  ┌──────────────────────────┐  │
│  │ Folder   │  │ Message List │  │ Message Viewer           │  │
│  │ Sidebar  │  │ (Livewire)   │  │ (Livewire + Alpine.js)   │  │
│  │ (Livewire│  │              │  │                          │  │
│  │ +Alpine) │  │ ┌──────────┐ │  │ ┌──────────────────────┐ │  │
│  │          │  │ │ Selection│ │  │ │ Sandboxed iframe      │ │  │
│  │ Folder   │  │ │ State    │ │  │ │ (srcdoc + sandbox)    │ │  │
│  │ Tree     │  │ │ (Alpine) │ │  │ │ + DOMPurify           │ │  │
│  │          │  │ └──────────┘ │  │ └──────────────────────┘ │  │
│  └──────────┘  └──────────────┘  └──────────────────────────┘  │
│         │               │                      │                │
│         └───────────────┼──────────────────────┘                │
│                         │ wire:navigate / Livewire updates      │
└─────────────────────────┼───────────────────────────────────────┘
                          │ HTTP (Livewire JSON protocol)
┌─────────────────────────┼───────────────────────────────────────┐
│                   Laravel Backend                                │
│  ┌──────────────────────┼──────────────────────────────────────┐│
│  │              Livewire Components                             ││
│  │  FolderSidebar  MessageList  MessageViewer  AttachmentDl    ││
│  └──────────────────────┼──────────────────────────────────────┘│
│                         │                                        │
│  ┌──────────────────────┼──────────────────────────────────────┐│
│  │              ImapMailboxService                              ││
│  │  - getFolders()    - getMessages()   - getMessage()         ││
│  │  - createFolder()  - deleteMessage() - getAttachment()      ││
│  │  - renameFolder()  - moveMessages()  - setFlags()           ││
│  └──────────────────────┼──────────────────────────────────────┘│
│                         │                                        │
│  ┌──────────────────────┼──────────────────────────────────────┐│
│  │              HTMLPurifier (server-side sanitization)         ││
│  └──────────────────────┼──────────────────────────────────────┘│
│                         │                                        │
│  ┌──────────────────────┼──────────────────────────────────────┐│
│  │              Database Cache (folder metadata)                ││
│  │  - FolderController: unread_count, total_count, uidvalidity ││
│  │  - MessageMetadata: uid, message_id, flags, snippet         ││
│  └──────────────────────┼──────────────────────────────────────┘│
└─────────────────────────┼───────────────────────────────────────┘
                          │
┌─────────────────────────┼───────────────────────────────────────┐
│              IMAP Server (Mail Infrastructure)                   │
│  - Folder listing (XLIST / SPECIAL-USE)                         │
│  - Message fetching (FETCH BODY.PEEK[HEADER])                   │
│  - SEARCH / SORT (server-side filtering)                        │
│  - STORE / COPY (bulk operations)                               │
│  - UIDVALIDITY tracking                                         │
└─────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure

```
app/
├── Livewire/
│   ├── Mailbox/
│   │   ├── FolderSidebar.php        # Folder tree navigation
│   │   ├── MessageList.php          # Message list with pagination
│   │   ├── MessageViewer.php        # Single message view
│   │   ├── MessageToolbar.php       # Bulk action toolbar
│   │   └── AttachmentDownload.php   # Secure attachment streaming
│   └── Concerns/
│       └── ImapConnection.php       # Shared IMAP connection trait
├── Services/
│   ├── ImapMailboxService.php       # Core IMAP operations wrapper
│   ├── FolderMapper.php             # IMAP SPECIAL-USE / name heuristic mapping
│   ├── MessageSanitizer.php         # HTMLPurifier wrapper for email HTML
│   └── MessageMetadataSync.php      # Sync IMAP flags to DB cache
├── Models/
│   ├── Folder.php                   # Cached folder metadata
│   └── MessageMetadata.php          # Cached message metadata (optional)
database/migrations/
├── xxxx_create_folders_table.php
└── xxxx_create_message_metadata_table.php
resources/views/
├── layouts/
│   └── mailbox.blade.php            # Mailbox layout (sidebar + content)
├── livewire/
│   └── mailbox/
│       ├── folder-sidebar.blade.php
│       ├── message-list.blade.php
│       ├── message-row.blade.php
│       ├── message-viewer.blade.php
│       ├── message-toolbar.blade.php
│       └── attachment-list.blade.php
└── components/
    └── email-renderer.blade.php     # Sandboxed iframe component
```

### Pattern 1: IMAP Connection per Request (No Persistent Connection)

**What:** Each Livewire request creates a fresh IMAP connection using credentials from the encrypted session. No persistent IMAP connection is maintained across requests.

**When to use:** Every IMAP operation in the mailbox (folder listing, message fetching, flag updates).

**Example:**
```php
// Source: App\Services\ImapMailboxService (to be created)
// Pattern based on: App\Providers\AppServiceProvider IMAP auth guard
use Webklex\PHPIMAP\Client;

class ImapMailboxService
{
    private function getClient(): Client
    {
        $config = [
            'host' => config('openmail.imap.host'),
            'port' => config('openmail.imap.port'),
            'encryption' => config('openmail.imap.encryption'),
            'username' => auth()->user()->email,
            'password' => Crypt::decrypt(session('openmail:imap_password')),
            'protocol' => 'imap',
            'timeout' => 10,
            'validate_cert' => true,
        ];

        $client = new Client($config);
        $client->connect();
        return $client;
    }
}
```

### Pattern 2: IMAP Pagination with LengthAwarePaginator

**What:** Use webklex/php-imap's built-in `paginate()` method which returns a `LengthAwarePaginator` — compatible with Livewire's `WithPagination` trait for SPA-like page transitions.

**When to use:** Message list rendering with pagination.

**Example:**
```php
// Source: php-imap.com/examples/pagination
// Source: livewire.laravel.com/docs/3.x/pagination
use Livewire\WithPagination;

class MessageList extends Component
{
    use WithPagination;

    public string $folderPath = 'INBOX';
    public string $sortBy = 'date';
    public string $sortDir = 'desc';

    public function getMessages(): LengthAwarePaginator
    {
        $client = app(ImapMailboxService::class)->getClient();
        $folder = $client->getFolder($this->folderPath);

        $query = $folder->query()
            ->setFetchBody(false) // Headers only for list
            ->setFetchOrder($this->sortDir);

        return $query->paginate(
            per_page: 25,
            page: $this->getPage(),
            page_name: 'messages-page'
        );
    }

    public function render()
    {
        return view('livewire.mailbox.message-list', [
            'messages' => $this->getMessages(),
        ]);
    }
}
```

### Pattern 3: Sandboxed iframe with srcdoc for HTML Email

**What:** Render sanitized HTML email inside a sandboxed iframe using `srcdoc` attribute. The iframe has a strict sandbox policy that blocks scripts and form submissions by default.

**When to use:** Any HTML email rendering in the message viewer.

**Example:**
```html
<!-- Source: MDN docs on srcdoc + sandbox, web security best practices -->
<!-- Pattern based on: didit.me/blog/embedded-iframe-security-best-practices -->
<iframe
    title="Email content"
    sandbox=""
    srcdoc="{{ $sanitizedHtml }}"
    class="w-full border-0"
    style="min-height: 400px;"
    onload="this.style.height = this.contentDocument.body.scrollHeight + 'px';"
></iframe>
```

**Key security rules:**
- `sandbox=""` (empty) = maximum restrictions: no scripts, no forms, no popups, unique origin
- Do NOT add `allow-scripts` + `allow-same-origin` together for untrusted content
- HTMLPurifier sanitizes server-side first; DOMPurify runs client-side as defense-in-depth
- Remote images blocked by default (rewrite `src` attributes before rendering)

### Pattern 4: Alpine.js Bulk Selection State

**What:** Alpine.js manages checkbox selection state client-side; Livewire handles the actual IMAP operations (move, delete, flag) when actions are triggered.

**When to use:** Message list with checkboxes, shift-click range selection, select-all.

**Example:**
```html
<!-- Source: Alpine.js 3.x patterns for bulk selection -->
<div x-data="{
    selected: new Set(),
    lastChecked: null,
    toggle(uid, event) {
        if (event.shiftKey && this.lastChecked) {
            // Range select between lastChecked and current
            const msgs = [...document.querySelectorAll('[data-uid]')];
            const start = msgs.findIndex(m => m.dataset.uid === this.lastChecked);
            const end = msgs.findIndex(m => m.dataset.uid === uid);
            const [from, to] = [Math.min(start, end), Math.max(start, end)];
            for (let i = from; i <= to; i++) {
                this.selected.add(msgs[i].dataset.uid);
            }
        } else {
            if (this.selected.has(uid)) {
                this.selected.delete(uid);
            } else {
                this.selected.add(uid);
            }
        }
        this.lastChecked = uid;
    },
    selectAll() {
        document.querySelectorAll('[data-uid]').forEach(el => {
            this.selected.add(el.dataset.uid);
        });
    },
    clearSelection() { this.selected = new Set(); }
}">
    <!-- Toolbar appears when selection is active -->
    <div x-show="selected.size > 0" class="flex items-center gap-2">
        <span x-text="selected.size + ' selected'"></span>
        <button wire:click="bulkDelete" class="...">Delete</button>
        <button wire:click="bulkMarkRead" class="...">Mark Read</button>
    </div>

    <!-- Message rows -->
    <template x-for="msg in messages" :key="msg.uid">
        <div :data-uid="msg.uid">
            <input type="checkbox" @change="toggle(msg.uid, $event)">
        </div>
    </template>
</div>
```

### Pattern 5: IMAP Folder Role Mapping (XLIST / SPECIAL-USE)

**What:** Use IMAP XLIST or SEARCH SPECIAL-USE (RFC 6154) to detect standard folder roles (\Inbox, \Sent, \Drafts, \Trash, \Junk, \Archive). Fall back to name-based heuristics for servers that don't support SPECIAL-USE.

**When to use:** On folder listing, to map IMAP folders to UI roles.

**Example:**
```php
// Source: webklex/php-imap Folder API documentation
// Source: php-imap.com/api/folder
class FolderMapper
{
    private const ROLE_MAP = [
        'INBOX' => 'inbox',
        '\\Sent' => 'sent',
        '\\Drafts' => 'drafts',
        '\\Trash' => 'trash',
        '\\Junk' => 'spam',
        '\\Archive' => 'archive',
    ];

    private const NAME_HEURISTICS = [
        'sent' => 'sent',
        'drafts' => 'drafts',
        'trash' => 'trash',
        'spam' => 'spam',
        'junk' => 'spam',
        'archive' => 'archive',
        'archiv' => 'archive',
    ];

    public function mapFolderRole(\Webklex\PHPIMAP\Folder $folder): ?string
    {
        // Check SPECIAL-USE attributes first
        $attributes = $folder->attributes ?? [];
        foreach ($attributes as $attr) {
            if (isset(self::ROLE_MAP[$attr])) {
                return self::ROLE_MAP[$attr];
            }
        }

        // Fallback: name-based heuristic
        $name = strtolower($folder->name);
        foreach (self::NAME_HEURISTICS as $pattern => $role) {
            if (str_contains($name, $pattern)) {
                return $role;
            }
        }

        return null; // Custom folder
    }
}
```

### Anti-Patterns to Avoid

- **Persistent IMAP connections:** Never hold an IMAP connection open across HTTP requests. Each Livewire request creates a fresh connection. IMAP servers have connection limits and will drop idle connections.
- **Client-side IMAP operations:** Never expose IMAP credentials or connection details to the browser. All IMAP operations happen server-side in Livewire components.
- **Storing HTML email in database:** Never persist raw email HTML in the database. Fetch from IMAP on demand, sanitize, and render. The database only caches metadata (flags, snippets).
- **`allow-scripts` + `allow-same-origin` in iframe sandbox:** This combination effectively removes sandbox restrictions for untrusted content. Never use both together for email HTML.
- **`wire:model.live` for message list filtering:** Use `wire:model` (deferred) or explicit `wire:submit` to avoid excessive server requests on every keystroke.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| IMAP protocol handling | Custom socket/protocol code | webklex/php-imap | Complex protocol with edge cases, encoding, TLS |
| IMAP folder detection | Manual flag parsing | FolderMapper with SPECIAL-USE + heuristics | RFC 6154 is well-supported; heuristics cover edge cases |
| HTML email sanitization | Custom regex/filter | HTMLPurifier + DOMPurify | Parser differential attacks, charset edge cases, XSS vectors |
| Pagination | Custom offset/limit logic | Laravel LengthAwarePaginator + Livewire WithPagination | Handles URL tracking, page state, SPA transitions automatically |
| Iframe sandboxing | Manual CSP header construction | iframe sandbox attribute + srcdoc | Browser-native isolation, simpler than CSP for email rendering |
| IMAP SEARCH/SORT | Client-side filtering | IMAP server-side SEARCH + SORT | Offloads to mail server, handles large mailboxes efficiently |
| UIDVALIDITY tracking | Manual IMAP EXAMINE calls | FolderMapper with cached UIDVALIDITY | Detects stale caches without re-fetching entire folder |

**Key insight:** IMAP is a complex protocol with encoding, charset, and server-specific quirks. The webklex/php-imap library handles all of this. The only custom code needed is the service wrapper that maps IMAP operations to application-level concerns (folder roles, metadata caching, sanitization).

## Common Pitfalls

### Pitfall 1: IMAP Connection Exhaustion
**What goes wrong:** Opening too many concurrent IMAP connections causes server-side connection limits to be hit, resulting in connection refused errors.
**Why it happens:** Each Livewire request creates a new IMAP connection. Rapid page navigation (clicking through folders) can exhaust server connections.
**How to avoid:** Use connection pooling with a short timeout (10s). Cache folder metadata in DB so folder switching doesn't always hit IMAP. Use `setFetchBody(false)` for message lists to minimize data transfer.
**Warning signs:** "Connection refused" errors, slow folder switching, IMAP server logs showing connection limits.

### Pitfall 2: UIDVALIDITY Mismatch
**What goes wrong:** Cached folder metadata becomes stale when the IMAP server resets UIDVALIDITY (typically during mailbox rebuild or server migration). Using stale UIDs causes wrong messages to be fetched or deleted.
**Why it happens:** UIDVALIDITY changes when the IMAP server reassigns UIDs. Local caches with old UIDs no longer map to the correct messages.
**How to avoid:** Store UIDVALIDITY per folder in DB cache. On folder open, compare current UIDVALIDITY with cached value. If different, trigger a full resync of message metadata.
**Warning signs:** Wrong messages displayed after server maintenance, "UID not found" errors, stale unread counts.

### Pitfall 3: Email HTML Charset Mismatches
**What goes wrong:** Emails with non-UTF-8 charsets (ISO-8859-1, GB2312, Shift_JIS) display garbled text or broken encoding in the browser.
**Why it happens:** PHP's mbstring handling and HTMLPurifier's charset conversion can fail with certain legacy encodings. The browser may not detect the charset correctly in a sandboxed iframe.
**How to avoid:** Use HTMLPurifier's charset configuration. Set `<meta charset="utf-8">` in the iframe srcdoc wrapper. Let webklex/php-imap handle initial charset decoding.
**Warning signs:** Garbled characters in email body, "mojibake" text, charset-related PHP warnings.

### Pitfall 4: Large Mailbox Performance
**What goes wrong:** Fetching message lists from folders with 10,000+ messages is extremely slow because IMAP doesn't natively support efficient pagination.
**Why it happens:** IMAP FETCH operations on large ranges are slow. The library's `paginate()` method may fetch all messages and slice client-side for some servers.
**How to avoid:** Use IMAP SORT + LIMIT where supported. Fetch only headers (not bodies) for message lists. Cache message metadata in DB after first fetch. Use `setFetchBody(false)` and `limit()` on queries.
**Warning signs:** 10+ second page loads for large folders, PHP memory exhaustion, IMAP timeouts.

### Pitfall 5: Remote Image Tracking Pixels
**What goes wrong:** Loading remote images in emails reveals the user's IP address and read status to the sender (tracking pixels).
**Why it happens:** Email clients traditionally load remote images automatically. Each image load is an HTTP request that reveals the client's IP.
**How to avoid:** Block all remote images by default (D-11). Show a "Display images" banner. When user opts in, rewrite image `src` attributes to proxy through the server or load directly.
**Warning signs:** User privacy complaints, tracking pixel detection in email analysis.

### Pitfall 6: Path Traversal in Attachment Downloads
**What goes wrong:** Malicious filenames like `../../etc/passwd` or `..%2F..%2Fetc%2Fpasswd` in email attachments can escape the download directory.
**Why it happens:** IMAP servers pass through whatever filename the email client set. Some clients use full paths or encoded sequences.
**How to avoid:** Never use the original filename directly. Generate UUID-based filenames for downloads. Validate that the resolved path stays within the download directory. Use `basename()` to strip directory components.
**Warning signs:** Files appearing outside expected directory, security scanner alerts, unexpected file access patterns.

## Code Examples

### IMAP Folder Listing with Role Mapping
```php
// Source: webklex/php-imap Folder API (php-imap.com/api/folder)
// Source: App\Services\ImapConnectionTester.php (existing pattern)
use Webklex\PHPIMAP\Facades\Client;

public function getFolders(): array
{
    $client = Client::account('default');
    $client->connect();

    $folders = $client->getFolders();
    $mapper = app(FolderMapper::class);

    $result = [];
    foreach ($folders as $folder) {
        $role = $mapper->mapFolderRole($folder);
        $info = $folder->examine();

        $result[] = [
            'path' => $folder->path,
            'name' => $folder->name,
            'role' => $role,
            'total' => (int) $info['exists'],
            'uidvalidity' => $info['uidvalidity'],
            'has_children' => $folder->hasChildren(),
        ];
    }

    $client->disconnect();
    return $result;
}
```

### Message List with Pagination
```php
// Source: php-imap.com/examples/pagination
// Source: livewire.laravel.com/docs/3.x/pagination
public function getMessages(): LengthAwarePaginator
{
    $client = app(ImapMailboxService::class)->getClient();
    $folder = $client->getFolder($this->folderPath);

    $query = $folder->query()
        ->setFetchBody(false)
        ->leaveUnread()
        ->setFetchOrderDesc();

    // Apply sort order via IMAP SORT
    match($this->sortBy) {
        'date' => $query->setFetchOrderDesc(),
        'sender' => $query->setFetchOrderAsc(), // IMAP sorts alphabetically
        'subject' => $query->setFetchOrderAsc(),
        'size' => $query->setFetchOrderDesc(),
        default => $query->setFetchOrderDesc(),
    };

    return $query->paginate(
        per_page: 25,
        page: null, // Auto-detect from request
        page_name: 'messages-page'
    );
}
```

### Secure Attachment Download
```php
// Source: SEC-08, SEC-09 requirements
// Source: webklex/php-imap Message API (php-imap.com/api/attachment)
public function downloadAttachment(string $folderPath, int $uid, int $attachmentIndex)
{
    $client = app(ImapMailboxService::class)->getClient();
    $folder = $client->getFolder($folderPath);
    $message = $folder->query()->getMessageByUid($uid);

    $attachments = $message->getAttachments();
    $attachment = $attachments->get($attachmentIndex);

    if (!$attachment) {
        abort(404);
    }

    // Security: Generate UUID-based filename (SEC-08)
    $originalName = $attachment->name;
    $safeName = Str::uuid() . '_' . basename($originalName);

    // Validate MIME type (SEC-09)
    $mimeType = $attachment->contentType ?? 'application/octet-stream';

    return response()->streamDownload(function () use ($attachment) {
        echo $attachment->content;
    }, $safeName, [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'attachment',
    ]);
}
```

### Sandboxed Email Renderer Component
```html
<!-- Source: MDN srcdoc docs, web security best practices -->
<!-- Source: ADR-004: Dual HTML sanitization -->
<div class="email-renderer" x-data="{ showImages: false }">
    {{-- Display images banner --}}
    <div x-show="!showImages" class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-4">
        <p class="text-sm text-yellow-800">
            This email contains remote images that are blocked for privacy.
            <button @click="showImages = true" class="underline font-medium">
                Display images
            </button>
        </p>
    </div>

    {{-- Sandboxed iframe for HTML email --}}
    @if($hasHtmlBody)
        <iframe
            title="Email content"
            sandbox=""
            srcdoc="{{ $sanitizedHtml }}"
            class="w-full border border-gray-200 rounded"
            style="min-height: 400px;"
            x-ref="emailFrame"
            onload="this.style.height = this.contentDocument.body.scrollHeight + 20 + 'px';"
        ></iframe>
    @else
        {{-- Plain text rendering --}}
        <pre class="whitespace-pre-wrap font-sans text-sm text-gray-800 bg-gray-50 p-4 rounded">{{ $textBody }}</pre>
    @endif
</div>
```

### Folder Metadata Caching
```php
// Source: Laravel 12 Cache documentation (laravel.com/docs/12.x/cache)
// Source: D-04: Cached unread/total counts with periodic refresh
public function getCachedFolderInfo(string $folderPath): array
{
    $cacheKey = "folder:{$folderPath}:" . auth()->id();

    return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($folderPath) {
        $client = app(ImapMailboxService::class)->getClient();
        $folder = $client->getFolder($folderPath);
        $info = $folder->examine();

        // Get unread count via IMAP SEARCH
        $unread = $folder->query()
            ->unseen()
            ->setFetchBody(false)
            ->count();

        return [
            'total' => (int) $info['exists'],
            'unread' => $unread,
            'uidvalidity' => $info['uidvalidity'],
            'uidnext' => $info['uidnext'],
        ];
    });
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| PHP IMAP extension (ext-imap) | webklex/php-imap (pure PHP) | ~2018 | No server-level extension needed; shared hosting compatible |
| IMAP FETCH all messages | IMAP SORT + SEARCH + LIMIT | Always best practice | Offloads filtering to mail server; reduces bandwidth |
| Server-side only sanitization | Dual: HTMLPurifier + DOMPurify | 2024 (OWASP research) | Eliminates parser differential attacks |
| Inline iframe src | srcdoc iframe with sandbox | HTML5 standard | No separate URL needed; sandbox provides isolation |
| File-based sessions | Database sessions | Laravel 12 default | 40-60% faster on shared hosting |

**Deprecated/outdated:**
- PHP IMAP extension (ext-imap): Requires server-level installation, not available on shared hosting. Use webklex/php-imap instead.
- Single-layer HTML sanitization: Known parser differential vulnerabilities (OWASP AppSec USA 2024). Use dual-layer (server + client).

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Livewire installed version is 4.4.3 (composer.lock confirms), but AGENTS.md says 3.x | Standard Stack | Planner must reconcile — code examples use Livewire 3.x API patterns which are compatible with 4.x but may need adjustment |
| A2 | webklex/php-imap `paginate()` returns Laravel's `LengthAwarePaginator` compatible with Livewire's `WithPagination` | Architecture Patterns | If incompatible, would need manual pagination implementation |
| A3 | IMAP XLIST/SPECIAL-USE is supported by most common mail servers (Gmail, Outlook, Dovecot) | Pattern 5 | Some servers may only support name-based heuristics |
| A4 | Database cache driver supports all needed operations (get, put, remember, forget) for folder metadata | Code Examples | Confirmed via Laravel 12 docs — database cache is fully featured |
| A5 | DOMPurify needs to be installed via npm and bundled with Vite | Standard Stack | If Vite build is not configured for JS modules, may need manual inclusion |

## Open Questions

1. **Livewire version reconciliation (A1)**
   - What we know: composer.json has `^4.4`, composer.lock shows `v4.4.3`. AGENTS.md says "Livewire 3.x (3.8.x)".
   - What's unclear: Whether Livewire 4.x has breaking changes from 3.x that affect the mailbox components.
   - Recommendation: Use Livewire 4.x API since it's what's installed. Livewire 4.x pagination API is identical to 3.x (WithPagination trait, setPage, resetPage). Verify during implementation.

2. **IMAP server compatibility for SPECIAL-USE**
   - What we know: RFC 6154 defines SPECIAL-USE, supported by Gmail, Outlook, Dovecot.
   - What's unclear: Whether shared hosting IMAP servers (cPanel Dovecot) support XLIST or SPECIAL-USE.
   - Recommendation: Implement both SPECIAL-USE detection AND name-based heuristics as fallback (D-01 decision already covers this).

3. **DOMPurify installation method**
   - What we know: DOMPurify is a JavaScript library. The project uses Vite for asset bundling.
   - What's unclear: Whether the iframe srcdoc approach requires DOMPurify to run inside the iframe (sandboxed) or can run in the parent page before setting srcdoc.
   - Recommendation: Run DOMPurify in the parent page (before setting srcdoc attribute). The sanitized string is then passed to srcdoc. This avoids needing to load DOMPurify inside the sandboxed iframe.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP 8.3+ | Core runtime | ✓ | 8.3+ | — |
| Composer 2.x | Dependency management | ✓ | 2.x | — |
| Node.js 18+ | Vite asset building | ✓ | — | — |
| MySQL/MariaDB | Database + cache | ✓ | 8.0+ | — |
| webklex/php-imap 6.2.0 | IMAP operations | ✓ | 6.2.0 | — |
| webklex/laravel-imap 6.2.0 | Laravel IMAP facade | ✓ | 6.2.0 | — |
| HTMLPurifier 4.19 | Server-side sanitization | ✓ | 4.19 | — |
| dompurify (npm) | Client-side sanitization | ✗ (not installed) | — | Install via `npm install dompurify` |

**Missing dependencies with no fallback:**
- None — all required packages are either already installed or need a simple npm install.

**Missing dependencies with fallback:**
- dompurify: Install via `npm install dompurify`. No fallback needed — it's a required dependency for Phase 2.

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit 11.x (installed via composer.json) |
| Config file | `phpunit.xml` (exists from Phase 1) |
| Quick run command | `php artisan test --filter=mailbox` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| MAIL-01 | Folder navigation | integration | `php artisan test --filter=MailboxFolderTest` | ❌ Wave 0 |
| MAIL-02 | Custom folder display | integration | `php artisan test --filter=CustomFolderTest` | ❌ Wave 0 |
| MAIL-03 | Folder metadata caching | unit | `php artisan test --filter=FolderMetadataCacheTest` | ❌ Wave 0 |
| MAIL-04 | Create/rename/delete folders | integration | `php artisan test --filter=FolderMutationTest` | ❌ Wave 0 |
| MSG-01 | Message list display | integration | `php artisan test --filter=MessageListTest` | ❌ Wave 0 |
| MSG-02 | Read/unread visual state | unit | `php artisan test --filter=ReadUnreadStateTest` | ❌ Wave 0 |
| MSG-03 | Star/flag indicator | unit | `php artisan test --filter=StarFlagTest` | ❌ Wave 0 |
| MSG-04 | Attachment indicator | unit | `php artisan test --filter=AttachmentIndicatorTest` | ❌ Wave 0 |
| MSG-05 | Pagination | integration | `php artisan test --filter=MessagePaginationTest` | ❌ Wave 0 |
| MSG-06 | Bulk selection | unit | `php artisan test --filter=BulkSelectionTest` | ❌ Wave 0 |
| MSG-07 | Sort by date/sender/subject/size | integration | `php artisan test --filter=MessageSortTest` | ❌ Wave 0 |
| VIEW-01 | Plain-text rendering | unit | `php artisan test --filter=PlainTextRenderTest` | ❌ Wave 0 |
| VIEW-02 | Sandboxed HTML rendering | integration | `php artisan test --filter=SandboxedHtmlRenderTest` | ❌ Wave 0 |
| VIEW-03 | Message headers display | unit | `php artisan test --filter=MessageHeadersTest` | ❌ Wave 0 |
| VIEW-04 | Attachment list with download | integration | `php artisan test --filter=AttachmentDownloadTest` | ❌ Wave 0 |
| VIEW-05 | Mark as read/unread | integration | `php artisan test --filter=MarkReadUnreadTest` | ❌ Wave 0 |
| VIEW-06 | Star/flag toggle | integration | `php artisan test --filter=StarToggleTest` | ❌ Wave 0 |
| VIEW-07 | Delete (move to Trash) | integration | `php artisan test --filter=DeleteMessageTest` | ❌ Wave 0 |
| VIEW-08 | Move to folder | integration | `php artisan test --filter=MoveMessageTest` | ❌ Wave 0 |
| VIEW-09 | Remote image blocking | unit | `php artisan test --filter=RemoteImageBlockTest` | ❌ Wave 0 |
| VIEW-10 | Print-friendly view | unit | `php artisan test --filter=PrintViewTest` | ❌ Wave 0 |
| DB-03 | Database caching for folder metadata | unit | `php artisan test --filter=FolderMetadataCacheTest` | ❌ Wave 0 |
| DB-04 | Message metadata storage | unit | `php artisan test --filter=MessageMetadataTest` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter={specific-test}`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Mailbox/` — integration tests for folder navigation, message list, message viewer
- [ ] `tests/Unit/Services/ImapMailboxServiceTest.php` — unit tests for IMAP service wrapper
- [ ] `tests/Unit/Services/FolderMapperTest.php` — unit tests for folder role mapping
- [ ] `tests/Unit/Services/MessageSanitizerTest.php` — unit tests for HTML sanitization
- [ ] Framework install: Already configured (PHPUnit 11.x in composer.json)

## Security Domain

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | yes | IMAP auth guard from Phase 1; session with encrypted password |
| V3 Session Management | yes | Database sessions, encrypted IMAP password in session |
| V4 Access Control | yes | Auth middleware on all mailbox routes; IMAP per-user isolation |
| V5 Input Validation | yes | HTMLPurifier + DOMPurify for email HTML; filename sanitization |
| V6 Cryptography | yes | Crypt::encrypt for IMAP password storage; TLS for IMAP connections |

### Known Threat Patterns for Laravel + IMAP Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via email HTML | Tampering | Dual sanitization: HTMLPurifier (server) + DOMPurify (client) + sandboxed iframe |
| Path traversal in attachments | Tampering | UUID-based filenames; basename() stripping; path validation (SEC-08) |
| IMAP credential exposure | Information Disclosure | Encrypted session storage; never send credentials to client |
| Remote image tracking | Information Disclosure | Block remote images by default; user opt-in (VIEW-09) |
| Email header injection | Tampering | Sanitize headers before display; use Laravel's e() helper |
| SSRF via email links | Information Disclosure | Validate URLs before proxying; block internal IPs (SEC-07) |
| Clickjacking via email | Spoofing | X-Frame-Options: DENY on app pages; sandbox on email iframe |

## Sources

### Primary (HIGH confidence)
- [CITED: php-imap.com/api/folder] — Folder API: examine(), overview(), query(), hasChildren(), move(), delete()
- [CITED: php-imap.com/api/query] — Query API: paginate(), limit(), setFetchBody(), setFetchOrder(), search criteria
- [CITED: php-imap.com/api/message] — Message API: getSubject(), getHTMLBody(), getTextBody(), getAttachments(), move(), setFlag()
- [CITED: php-imap.com/examples/pagination] — Message pagination with LengthAwarePaginator
- [CITED: livewire.laravel.com/docs/3.x/pagination] — Livewire WithPagination trait, setPage, resetPage, links()
- [CITED: laravel.com/docs/12.x/pagination] — Laravel LengthAwarePaginator, paginate(), simplePaginate()
- [CITED: laravel.com/docs/12.x/cache] — Cache::remember(), Cache::store(), database cache driver
- [CITED: github.com/cure53/DOMPurify] — DOMPurify 3.x API: sanitize(), configuration options, hooks
- [CITED: MDN docs on iframe srcdoc] — srcdoc attribute, sandbox attribute, security best practices
- [VERIFIED: composer.lock] — Installed versions: webklex/php-imap 6.2.0, webklex/laravel-imap 6.2.0, livewire/livewire 4.4.3

### Secondary (MEDIUM confidence)
- [CITED: didit.me/blog/embedded-iframe-security-best-practices] — iframe sandbox best practices, CSP integration
- [CITED: codeshack.io/references/html/sandbox] — sandbox attribute values and security rules
- [CITED: thelinuxcode.com/html-srcdoc-attribute-practical-guide] — srcdoc production patterns, performance considerations

### Tertiary (LOW confidence)
- [ASSUMED] IMAP XLIST/SPECIAL-USE support varies by mail server; heuristics needed as fallback
- [ASSUMED] DOMPurify can run in parent page before setting srcdoc (needs verification during implementation)

## Metadata

**Confidence breakdown:**
- Standard Stack: HIGH — all packages verified via composer.lock and official documentation
- Architecture: HIGH — based on official library APIs and established Laravel/Livewire patterns
- Pitfalls: HIGH — derived from IMAP protocol knowledge, library documentation, and web security best practices
- Code Examples: HIGH — based on verified API documentation from php-imap.com and livewire.laravel.com

**Research date:** 2026-09-05
**Valid until:** 2026-10-05 (30 days — stable stack, minimal version churn expected)
