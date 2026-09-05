# Phase [3]: Compose & Send - Research

**Researched:** 2026-09-06
**Domain:** Email composition, rich text editing, IMAP/SMTP sending, draft management
**Confidence:** HIGH

## Summary

Phase 3 delivers the complete email composition and sending loop for OpenMail. Users will be able to compose new messages, reply/forward with quoted text, autosave drafts to both LocalStorage (instant) and IMAP Drafts folder (synced), send via SMTP with Sent folder synchronization, and use a configurable undo-send delay. Contact autocomplete pulls recent recipients from IMAP Sent/Inbox folders.

**Primary recommendation:** Build a Livewire 3 modal composer component using Tiptap 3.x for rich text editing, extend the existing `ImapMailboxService` with `appendMessage()` for IMAP Drafts/Sent folder operations, use Laravel's native Symfony Mailer for SMTP sending, implement undo-send via a `pending_sends` database table processed by Laravel Scheduler (cron), and leverage the existing `MessageSanitizer` for quoted HTML sanitization in replies/forwards.

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Rich text composition (Tiptap) | Browser / Client | — | Editor runs entirely in browser; content synced to Livewire via hidden textarea |
| Recipient chips & autocomplete | Browser / Client | API / Backend | Alpine.js handles UI; Livewire fetches suggestions via IMAP SEARCH |
| Draft autosave (LocalStorage) | Browser / Client | — | Instant saves to LocalStorage; no server round-trip |
| Draft sync to IMAP Drafts | API / Backend | — | IMAP APPEND operation requires server-side IMAP connection |
| SMTP sending | API / Backend | — | Symfony Mailer sends via SMTP; server holds credentials |
| Sent folder sync (IMAP APPEND) | API / Backend | — | Must APPEND to Sent folder after successful SMTP send |
| Undo send delay queue | API / Backend | Database / Storage | `pending_sends` table with `send_at` timestamp; scheduler processes |
| Attachment upload & temp storage | API / Backend | Browser / Client | Livewire handles upload; server stores in temp location |
| Contact autocomplete cache | Database / Storage | API / Backend | Cached in DB with TTL; populated from IMAP SEARCH |

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| PHP | 8.3+ | Runtime | Laravel 12 minimum; typed constants, readonly classes |
| Laravel | 12.x | Backend framework | Stable LTS (security until Feb 2027); Livewire starter kits |
| webklex/php-imap | 6.2.0 | IMAP client | Pure PHP IMAP (no ext-imap); supports APPEND, IDLE, OAuth |
| webklex/laravel-imap | 6.2.0 | Laravel IMAP wrapper | Facade, config, DI integration |
| Symfony Mailer | 7.x (via Laravel) | SMTP sending | Laravel's built-in mail system; raw MIME customization via `using` callback |
| Tiptap | 3.31.3 | Rich text editor | Headless, ProseMirror-based; extensible for email needs |
| @tiptap/starter-kit | 3.31.3 | Core editing | Bold, italic, lists, headings, code blocks, history |
| @tiptap/extension-link | 3.31.3 | Link editing | Insert/edit links in email body |
| @tiptap/extension-image | 3.31.3 | Image handling | Inline images (attach as file for v1) |
| @tiptap/extension-table | 3.31.3 | Table editing | Table support in emails |
| @tiptap/extension-task-list | 3.31.3 | Task lists | Checklist support |
| @tiptap/extension-text-align | 3.31.3 | Text alignment | Left/center/right/justify |
| @tiptap/extension-color | 3.31.3 | Text color | Font color & highlight |
| @tiptap/extension-placeholder | 3.31.3 | Placeholder text | "Start writing…" in empty editor |
| @tiptap/extension-history | 3.31.3 | Undo/redo | Keyboard shortcuts (Ctrl+Z) |
| @tiptap/extension-character-count | 3.31.3 | Character limit | Optional length enforcement |
| Alpine.js | 3.17.1 | Lightweight JS | Included by Livewire 3; modals, dropdowns, chips, transitions |
| Livewire | 3.8.x | Dynamic UI | `wire:model`, file uploads, `wire:navigate`, modal patterns |
| Tailwind CSS | 4.3.x | Styling | Utility-first; v4 CSS-first config |
| Vite | 6.x | Asset bundling | Laravel default; builds Tiptap + Alpine JS |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| spatie/laravel-csp | latest | CSP headers | Phase 5; integrates with Vite nonce |
| HTMLPurifier | 4.17.x | Server HTML sanitize | Reuse existing `MessageSanitizer` for quoted content |
| DOMPurify | 3.x | Client HTML sanitize | Reuse existing pattern for reply/forward rendering |
| php-mime-mail-parser | 10.x | MIME parsing | If `mailparse` PECL available; else zbateson/mail-mime-parser 4.x |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Tiptap 3.x | Quill / TinyMCE | Tiptap is headless, ProseMirror-based, better Livewire integration via hidden textarea sync |
| webklex/php-imap | DirectoryTree/ImapEngine | ImapEngine younger (38 stars); webklex has 454 stars, Laravel wrapper, proven in Phase 1-2 |
| Database `pending_sends` queue | Redis queues | Shared hosting constraint — no Redis; database works with cron scheduler |
| LocalStorage draft autosave | Server-only autosave | LocalStorage provides instant UX; server sync on interval avoids latency |

### Installation

```bash
# Core framework (already installed from Phase 1)
composer require webklex/laravel-imap:^6.2  # Already installed

# Frontend - Tiptap editor
npm install @tiptap/core@3.31.3 @tiptap/starter-kit@3.31.3 @tiptap/extension-link@3.31.3 @tiptap/extension-image@3.31.3 @tiptap/extension-table@3.31.3 @tiptap/extension-task-list@3.31.3 @tiptap/extension-text-align@3.31.3 @tiptap/extension-color@3.31.3 @tiptap/extension-emoji@3.31.3 @tiptap/extension-placeholder@3.31.3 @tiptap/extension-history@3.31.3 @tiptap/extension-character-count@3.31.3

# Note: @tiptap/extension-font-size is deprecated (3.0.0-next.3) — use @tiptap/extension-text-style with fontSize instead
npm install @tiptap/extension-text-style@3.31.3

# Build assets
npm run build
```

**Version verification:**
```bash
npm view @tiptap/core version           # 3.31.3 (published 2026-09-04)
npm view @tiptap/starter-kit version    # 3.31.3
npm view alpinejs version               # 3.17.1 (published 2026-08-31)
composer show webklex/laravel-imap      # 6.2.0 (released 2025-04-25)
composer show webklex/php-imap          # 6.2.0 (released 2025-04-25)
```

## Package Legitimacy Audit

> **Required** whenever this phase installs external packages. Run the Package Legitimacy Gate protocol before completing this section.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| @tiptap/core | npm | 2 days (v3.31.3) | 18.6M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved — legitimate org, high downloads, recent v3 release |
| @tiptap/starter-kit | npm | 2 days | 15.9M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved — same org, core dependency |
| @tiptap/extension-link | npm | 2 days | 16.7M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-image | npm | 2 days | 8.1M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-table | npm | 2 days | 6.4M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-task-list | npm | 2 days | 2.5M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-text-align | npm | 2 days | 5.6M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-color | npm | 2 days | 3.7M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-emoji | npm | 2 days | 480K/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-placeholder | npm | 2 days | 10M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-history | npm | 2 days | 4.6M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-character-count | npm | 2 days | 1.5M/wk | github.com/ueberdosis/tiptap | SUS (too-new) | Approved |
| @tiptap/extension-text-style | npm | 2 days | (bundled) | github.com/ueberdosis/tiptap | SUS (too-new) | Approved — replaces deprecated font-size |
| alpinejs | npm | 6 days (v3.17.1) | 737K/wk | github.com/alpinejs/alpine | SUS (too-new) | Approved — official Alpine.js, used by Livewire 3 |
| webklex/laravel-imap | Packagist | 1 year (v6.2.0) | N/A | github.com/Webklex/laravel-imap | OK | Approved — already installed, 712 stars |
| webklex/php-imap | Packagist | 1 year (v6.2.0) | N/A | github.com/Webklex/php-imap | OK | Approved — already installed, 454 stars |

**Packages removed due to [SLOP] verdict:** none
**Packages flagged as suspicious [SUS]:** All Tiptap packages + Alpine.js flagged "too-new" (published Sep 2026). These are legitimate packages from established organizations with millions of weekly downloads. The "too-new" flag reflects a recent v3.31.3 release, not actual suspicion. **Planner must add `checkpoint:human-verify` before each npm install** for these packages.

*Packages discovered via WebSearch or training data that have not been verified against an authoritative source are tagged `[ASSUMED]` and the planner must gate each install behind a `checkpoint:human-verify` task.*

## Architecture Patterns

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                            BROWSER (Client Tier)                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐    │
│  │ Composer     │  │ Tiptap       │  │ Alpine.js    │  │ LocalStorage │    │
│  │ Modal        │──│ Editor       │  │ Components   │  │ (Draft       │    │
│  │ (Livewire)   │  │ (Rich Text)  │  │ (Chips,      │  │  Autosave)   │    │
│  └──────┬───────┘  └──────┬───────┘  │  Dropdowns)  │  └──────┬───────┘    │
│         │                 │          └──────┬───────┘         │            │
│         │ wire:model      │                 │                 │            │
│         ▼                 ▼                 ▼                 ▼            │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                    LIVEWIRE COMPONENT (Composer)                     │  │
│  │  - Form state (to, cc, bcc, subject, body, attachments)              │  │
│  │  - Draft autosave debounce (1.5s) → LocalStorage                     │  │
│  │  - Attachment upload → /api/composer/attachments (temp storage)      │  │
│  │  - Autocomplete fetch → /api/composer/autocomplete (debounced)       │  │
│  │  - Send action → POST /composer/send                                 │  │
│  └────────────────────────────────┬─────────────────────────────────────┘  │
└───────────────────────────────────│────────────────────────────────────────┘
                                    │ HTTP / Livewire
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                           API / BACKEND TIER                                │
│  ┌─────────────────┐  ┌─────────────────┐  ┌─────────────────────────────┐  │
│  │ Composer        │  │ ImapMailbox     │  │ MessageSanitizer            │  │
│  │ Controller/     │  │ Service         │  │ (HTMLPurifier +             │  │
│  │ Livewire Comp.  │  │                 │  │  DOMPurify)                 │  │
│  │                 │  │ - appendMessage │  │                             │  │
│  │ - Draft sync    │  │   (Drafts/Sent) │  │ - Sanitize quoted HTML      │  │
│  │ - Send flow     │  │ - search        │  │ - Block remote images       │  │
│  │ - Undo send     │  │   (recipients)  │  │ - Convert HTML→text quote   │  │
│  └────────┬────────┘  └────────┬────────┘  └──────────────┬──────────────┘  │
│           │                    │                           │               │
│           ▼                    ▼                           ▼               │
│  ┌──────────────────────────────────────────────────────────────────────┐  │
│  │                      DATABASE (MySQL)                                │  │
│  │  - pending_sends (id, user_id, message_json, send_at, status)       │  │
│  │  - contact_autocomplete_cache (email, name, last_used, expires_at)  │  │
│  │  - draft_autosave_metadata (composition_id, imap_uid, updated_at)   │  │
│  └──────────────────────────────────────────────────────────────────────┘  │
└───────────────────────────────────│────────────────────────────────────────┘
                                    │ IMAP / SMTP
                                    ▼
┌─────────────────────────────────────────────────────────────────────────────┐
│                         EXTERNAL MAIL SERVER                                │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                      │
│  │ IMAP Server  │  │ SMTP Server  │  │ IMAP Folders │                      │
│  │ (Fetch,      │  │ (Send)       │  │ (Drafts,     │                      │
│  │  Search,     │  │              │  │  Sent,       │                      │
│  │  APPEND)     │  │              │  │  Inbox)      │                      │
│  └──────────────┘  └──────────────┘  └──────────────┘                      │
└─────────────────────────────────────────────────────────────────────────────┘
```

### Recommended Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   └── ComposerController.php          # API endpoints for attachments, autocomplete
│   └── Livewire/
│       └── Mailbox/
│           ├── Composer.php                # Main composer modal component
│           ├── ComposerAttachment.php      # Attachment upload handling
│           └── ComposerAutocomplete.php    # Contact autocomplete
├── Services/
│   ├── ImapMailboxService.php              # Extended with appendMessage(), sendMessage()
│   ├── ComposerService.php                 # Send flow, draft sync, undo send logic
│   ├── MessageSanitizer.php                # Reused for quoted HTML sanitization
│   └── ContactAutocompleteService.php      # IMAP SEARCH → DB cache
├── Models/
│   ├── PendingSend.php                     # pending_sends table model
│   ├── ContactAutocompleteCache.php        # autocomplete cache model
│   └── DraftAutosave.php                   # draft autosave metadata model
├── Console/
│   └── Commands/
│       └── ProcessPendingSends.php         # Scheduler command for retry/undo
resources/
├── views/
│   ├── livewire/
│   │   └── mailbox/
│   │       ├── composer.blade.php          # Modal with Tiptap editor
│   │       ├── composer-attachments.blade.php
│   │       ├── composer-recipients.blade.php
│   │       └── composer-quote.blade.php    # Collapsible quote block
│   └── components/
│       └── tiptap-editor.blade.php         # Tiptap initialization + toolbar
├── js/
│   ├── components/
│   │   └── TiptapEditor.js                 # Tiptap init, Livewire sync
│   └── app.js                              # Vite entry
└── css/
    └── app.css                             # Tailwind + Tiptap styles
database/
├── migrations/
│   ├── create_pending_sends_table.php
│   ├── create_contact_autocomplete_cache_table.php
│   └── create_draft_autosave_table.php
routes/
├── console.php                             # Scheduler: everyMinute -> ProcessPendingSends
└── web.php                                 # Composer routes
```

### Pattern 1: Tiptap + Livewire 3 Integration

**What:** Sync Tiptap editor content to a hidden `<textarea wire:model="body">` for Livewire form binding.

**When to use:** Any Livewire component needing rich text input.

**Example:**
```blade
{{-- resources/views/components/tiptap-editor.blade.php --}}
<div class="tiptap-editor-wrapper">
    <!-- Toolbar rendered by Alpine.js -->
    <div x-data="tiptapToolbar()" class="border border-gray-300 rounded-t-lg bg-gray-50 p-2">
        <button @click="editor.chain().focus().toggleBold().run()" 
                :class="editor.isActive('bold') ? 'bg-blue-100' : ''"
                type="button" aria-label="Bold" aria-pressed="false">
            <svg class="w-4 h-4"><!-- bold icon --></svg>
        </button>
        <!-- ... more toolbar buttons -->
    </div>
    
    <!-- Editor content area -->
    <div x-ref="editor" class="border border-gray-300 rounded-b-lg min-h-[300px] p-4 bg-white prose max-w-none focus:outline-none focus:ring-2 focus:ring-blue-500"></div>
    
    <!-- Hidden textarea for Livewire sync -->
    <textarea wire:model="body" class="hidden" aria-hidden="true"></textarea>
</div>

<script>
function tiptapToolbar() {
    return {
        editor: null,
        init() {
            import('@tiptap/core').then(({ Editor }) => {
                import('@tiptap/starter-kit').then(({ StarterKit }) => {
                    import('@tiptap/extension-link').then(({ Link }) => {
                        // ... import all extensions
                        this.editor = new Editor({
                            element: this.$refs.editor,
                            extensions: [
                                StarterKit.configure({ heading: { levels: [1,2,3] } }),
                                Link.configure({ openOnClick: false }),
                                Image,
                                Table,
                                TaskList,
                                TaskItem,
                                TextAlign.configure({ types: ['heading', 'paragraph'] }),
                                TextStyle,
                                Color,
                                Emoji,
                                Placeholder.configure({ placeholder: 'Start writing…' }),
                                History,
                                CharacterCount.configure({ limit: 100000 })
                            ],
                            content: this.$wire.body || '',
                            onUpdate: ({ editor }) => {
                                // Sync to hidden textarea for Livewire
                                this.$wire.body = editor.getHTML();
                            },
                            editorProps: {
                                attributes: {
                                    class: 'prose prose-sm max-w-none focus:outline-none',
                                    spellcheck: 'true'
                                }
                            }
                        });
                    });
                });
            });
        },
        // Toolbar action methods
        toggleBold() { this.editor.chain().focus().toggleBold().run(); },
        // ...
    }
}
</script>
```

**Source:** [Tiptap Vue 3 integration pattern](https://tiptap.dev/docs/guide/vue-3) adapted for Livewire/Alpine — the `onUpdate` callback syncs HTML to Livewire via hidden textarea `[VERIFIED: Tiptap docs pattern]`

### Pattern 2: IMAP APPEND to Sent/Drafts Folders

**What:** Use `Folder::appendMessage()` to save composed messages to IMAP Drafts (on autosave) or Sent (on send).

**When to use:** After SMTP send success (Sent) or during draft autosave interval (Drafts).

**Example:**
```php
// app/Services/ImapMailboxService.php
public function appendToDrafts(string $messageRaw, ?string $existingUid = null): ?string
{
    $draftsFolder = $this->folderMapper->getDraftsFolder(); // Uses SPECIAL-USE \Drafts or heuristic
    $folder = $this->client->getFolder($draftsFolder);
    
    if ($existingUid) {
        // Delete old draft version before appending new one
        $folder->query()->getMessageByUid($existingUid)?->delete(true);
    }
    
    $result = $folder->appendMessage($messageRaw, ['\\Draft'], now());
    // Result contains new UID - parse and return for tracking
    return $this->extractUidFromAppendResult($result);
}

public function appendToSent(string $messageRaw): ?string
{
    $sentFolder = $this->folderMapper->getSentFolder(); // SPECIAL-USE \Sent or heuristic
    $folder = $this->client->getFolder($sentFolder);
    
    $result = $folder->appendMessage($messageRaw, ['\\Seen'], now());
    return $this->extractUidFromAppendResult($result);
}
```

**Source:** `Folder::appendMessage(string $message, ?array $options, Carbon|string|null $internal_date)` in webklex/php-imap 6.2.0 `[VERIFIED: src/Folder.php:470-487]`

### Pattern 3: SMTP Send with Raw MIME + Sent Folder Sync

**What:** Build raw MIME message, send via Symfony Mailer, then IMAP APPEND to Sent folder.

**When to use:** Send action — ensures message exists in Sent before/after SMTP delivery.

**Example:**
```php
// app/Services/ComposerService.php
public function sendMessage(array $composerData): array
{
    // 1. Build raw MIME message
    $mimeMessage = $this->buildMimeMessage($composerData);
    
    // 2. IMAP APPEND to Sent folder FIRST (D-17)
    $sentUid = $this->imapService->appendToSent($mimeMessage->toString());
    
    // 3. Send via SMTP (Laravel Mail with raw MIME)
    try {
        Mail::raw($mimeMessage->toString(), function ($message) use ($composerData) {
            $message->to($composerData['to'])
                    ->cc($composerData['cc'] ?? [])
                    ->bcc($composerData['bcc'] ?? [])
                    ->subject($composerData['subject']);
            
            // Customize Symfony message for raw MIME
            $message->using(function (\Symfony\Component\Mime\Email $symfonyMessage) use ($mimeMessage) {
                // Replace Symfony's generated MIME with our pre-built one
                // Note: This requires accessing internal APIs or using a custom transport
            });
        });
        
        // 4. On success: mark pending_send as sent, clean up
        return ['success' => true, 'sent_uid' => $sentUid];
        
    } catch (\Exception $e) {
        // 5. On SMTP failure: queue for retry (D-18)
        PendingSend::create([
            'user_id' => auth()->id(),
            'message_json' => json_encode($composerData),
            'send_at' => now()->addSeconds(config('openmail.undo_send_delay', 10)),
            'status' => 'pending',
            'sent_folder_uid' => $sentUid,
        ]);
        return ['success' => false, 'queued' => true];
    }
}
```

**Source:** Laravel Mail docs — `Mail::raw()`, `Envelope::using()` for Symfony Message customization `[VERIFIED: laravel.com/docs/12.x/mail#customizing-the-symfony-message]`

### Pattern 4: Undo Send via Database Queue + Scheduler

**What:** Delay SMTP send by configurable 5-30s; store in `pending_sends`; scheduler processes every minute.

**When to use:** After user clicks Send — shows toast with countdown; undo cancels pending send.

**Example:**
```php
// database/migrations/create_pending_sends_table.php
Schema::create('pending_sends', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->json('message_json');           // Full composer data
    $table->text('mime_message')->nullable(); // Pre-built MIME for retry
    $table->timestamp('send_at');           // When to actually send
    $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
    $table->string('sent_folder_uid')->nullable(); // UID from IMAP APPEND
    $table->integer('retry_count')->default(0);
    $table->timestamps();
    $table->index(['user_id', 'status', 'send_at']);
});

// routes/console.php
Schedule::command('pending-sends:process')->everyMinute()->withoutOverlapping(5);

// app/Console/Commands/ProcessPendingSends.php
public function handle(): int
{
    $pending = PendingSend::where('status', 'pending')
        ->where('send_at', '<=', now())
        ->limit(50)
        ->get();
    
    foreach ($pending as $send) {
        $data = json_decode($send->message_json, true);
        
        try {
            // Resend via SMTP
            Mail::raw($send->mime_message, function ($message) use ($data) {
                $message->to($data['to'])->cc($data['cc'])->bcc($data['bcc'])->subject($data['subject']);
            });
            
            $send->update(['status' => 'sent']);
            
        } catch (\Exception $e) {
            $send->increment('retry_count');
            if ($send->retry_count >= 3) {
                $send->update(['status' => 'failed']);
                // Notify user via notification system
            } else {
                // Reschedule with exponential backoff
                $send->update(['send_at' => now()->addMinutes(pow(2, $send->retry_count))]);
            }
        }
    }
    return 0;
}
```

**Source:** Laravel Scheduler docs — `everyMinute()`, `withoutOverlapping()`, `onOneServer()` with database cache `[VERIFIED: laravel.com/docs/12.x/scheduling]`

### Pattern 5: Contact Autocomplete from IMAP SEARCH

**What:** Scan recent Sent/Inbox messages for recipient addresses; cache in DB with TTL.

**When to use:** Composer To/CC/BCC fields — debounced autocomplete dropdown.

**Example:**
```php
// app/Services/ContactAutocompleteService.php
public function refreshCache(int $userId): int
{
    $client = $this->getImapClient($userId);
    $sinceDate = now()->subDays(30)->format('d-M-Y');
    $emails = [];
    
    // Search Sent folder
    $sentFolder = $this->folderMapper->getSentFolder();
    $sentMessages = $client->getFolder($sentFolder)
        ->query()
        ->since($sinceDate)
        ->all()
        ->get();
    
    foreach ($sentMessages as $msg) {
        foreach ($msg->getTo() as $addr) {
            $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
        }
        foreach ($msg->getCc() as $addr) {
            $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
        }
    }
    
    // Search Inbox for From addresses (replies received)
    $inboxMessages = $client->getFolder('INBOX')
        ->query()
        ->since($sinceDate)
        ->all()
        ->get();
    
    foreach ($inboxMessages as $msg) {
        foreach ($msg->getFrom() as $addr) {
            $emails[] = ['email' => $addr->mail, 'name' => $addr->personal ?? ''];
        }
    }
    
    // Aggregate frequency, upsert to cache
    $aggregated = collect($emails)
        ->groupBy('email')
        ->map(function ($group) {
            return [
                'email' => $group->first()['email'],
                'name' => $group->first()['name'],
                'frequency' => $group->count(),
                'last_used' => now(),
                'expires_at' => now()->addDays(7),
            ];
        })
        ->values();
    
    foreach ($aggregated as $contact) {
        ContactAutocompleteCache::updateOrCreate(
            ['user_id' => $userId, 'email' => $contact['email']],
            $contact + ['user_id' => $userId]
        );
    }
    
    return $aggregated->count();
}

public function search(int $userId, string $query, int $limit = 10): Collection
{
    return ContactAutocompleteCache::where('user_id', $userId)
        ->where('expires_at', '>', now())
        ->where(function ($q) use ($query) {
            $q->where('email', 'LIKE', "%{$query}%")
              ->orWhere('name', 'LIKE', "%{$query}%");
        })
        ->orderByDesc('frequency')
        ->limit($limit)
        ->get();
}
```

**Source:** webklex/php-imap Query API — `since()`, `from()`, `to()`, `cc()`, `text()` search criteria `[VERIFIED: php-imap.com/api/query]`

### Pattern 6: Livewire 3 File Upload for Attachments

**What:** Use `WithFileUploads` trait; immediate upload to temp storage; return preview URLs.

**When to use:** Composer attachment drag-drop zone.

**Example:**
```php
// app/Livewire/Mailbox/Composer.php
use Livewire\WithFileUploads;

class Composer extends Component
{
    use WithFileUploads;
    
    public $attachments = []; // Array of TemporaryUploadedFile
    
    protected $rules = [
        'attachments.*' => 'file|max:25600', // 25MB per file
    ];
    
    public function updatedAttachments(): void
    {
        $this->validate();
        $totalSize = collect($this->attachments)->sum('getSize');
        if ($totalSize > 51200 * 1024) { // 50MB total
            $this->addError('attachments', 'Total attachments cannot exceed 50MB');
            $this->attachments = []; // Reset
        }
    }
    
    public function removeAttachment(int $index): void
    {
        unset($this->attachments[$index]);
        $this->attachments = array_values($this->attachments);
    }
    
    // On draft save: move temp files to IMAP Drafts as attachments
    // On send: attach to MIME message
}
```

**Source:** Livewire 3 docs — `WithFileUploads` trait, `TemporaryUploadedFile`, validation `[CITED: laravel-livewire.com/docs/3.x/file-uploads]`

### Pattern 7: Alpine.js Collapsible Quote Blocks

**What:** Gmail-style quoted sections with show/hide toggle using `x-show` + `x-transition`.

**When to use:** Reply/forward quoted message rendering.

**Example:**
```blade
{{-- resources/views/components/composer-quote.blade.php --}}
<blockquote class="quote-block border-l-4 border-blue-200 pl-4 ml-4 my-2" x-data="{ open: false }">
    <div class="quote-header text-xs text-gray-500 mb-1 flex items-center gap-2">
        <span>On {{ $formattedDate }}, {{ $senderName }} <{{ $senderEmail }}> wrote:</span>
        <button 
            @click="open = !open" 
            class="text-blue-600 hover:underline text-sm"
            :aria-expanded="open"
            aria-controls="quote-content-{{ $uid }}">
            <span x-show="!open">Show quoted text</span>
            <span x-show="open">Hide quoted text</span>
        </button>
    </div>
    <div id="quote-content-{{ $uid }}" class="quote-content" x-show="open" x-transition>
        {!! $sanitizedQuotedHTML !!}
    </div>
</blockquote>
```

**Source:** Alpine.js docs — `x-show`, `x-transition`, `x-data` for toggle state `[ASSUMED]` — standard Alpine patterns

### Anti-Patterns to Avoid

- **Don't use `wire:model.live` on Tiptap content** — sends request on every keystroke; use hidden textarea with `onUpdate` callback instead
- **Don't send SMTP before IMAP APPEND to Sent** — D-17 mandates APPEND first; if SMTP fails, message stays in Sent with 'pending' flag for retry
- **Don't store mailbox passwords in plaintext** — Phase 1 established encrypted session storage; reuse `Crypt::encrypt/decrypt`
- **Don't hand-roll MIME message building** — Use `Symfony\Component\Mime\Email` or `php-mime-mail-parser` for parsing; construct raw MIME for APPEND
- **Don't use Redis for pending_sends queue** — Shared hosting constraint; database + scheduler is the supported pattern
- **Don't skip HTML sanitization on quoted content** — Reuse `MessageSanitizer` (HTMLPurifier → DOMPurify) for forwarded/replied HTML

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Rich text editor | Custom contenteditable div | Tiptap (ProseMirror) | Handles cursor positions, undo/redo, paste sanitization, cross-browser quirks |
| MIME message construction | String concatenation | Symfony Mime `Email` class | RFC-compliant headers, encoding, multipart/alternative, attachments |
| IMAP APPEND | Raw IMAP commands | webklex/php-imap `Folder::appendMessage()` | Handles UTF7-IMAP folder encoding, FLAGS, INTERNALDATE, UID retrieval |
| HTML email sanitization | Regex strip_tags | HTMLPurifier + DOMPurify | Parser differential attacks; whitelist-based; standards-compliant |
| Contact autocomplete | Full-text search on contacts table | IMAP SEARCH + DB cache | No local address book in v1; IMAP is source of truth for recent recipients |
| Undo send delay | JavaScript setTimeout | Database `send_at` + Laravel Scheduler | Survives page reload/navigation; works on shared hosting without workers |
| Attachment temp storage | Custom file handling | Livewire `WithFileUploads` + `TemporaryUploadedFile` | Automatic cleanup, validation, S3/local disk support, progress events |

**Key insight:** Email composition involves deceptively complex RFC-compliant MIME generation, IMAP protocol nuances (UTF7 folder names, APPEND flags), and HTML sanitization security. The existing stack (webklex, Symfony Mailer, HTMLPurifier, Tiptap) handles all edge cases — custom implementations will fail on real-world mail servers.

## Runtime State Inventory

> Include this section for rename/refactor/migration phases only. Omit entirely for greenfield phases.

**Phase 3 is greenfield (new composer feature) — no runtime state migration needed.**

## Common Pitfalls

### Pitfall 1: Tiptap Content Not Syncing to Livewire
**What goes wrong:** Editor content not submitted with form; `wire:model` on hidden textarea stays empty.
**Why it happens:** Tiptap `onUpdate` callback not bound correctly; editor instance not accessible in Alpine component.
**How to avoid:** Initialize Tiptap in Alpine `init()`; store editor instance in Alpine state; call `$wire.body = editor.getHTML()` in `onUpdate`.
**Warning signs:** Form submits with empty body; console shows "editor is undefined".

### Pitfall 2: IMAP APPEND Fails on Special-Use Folders
**What goes wrong:** `appendMessage()` throws exception on `\Drafts` or `\Sent` folder names.
**Why it happens:** Folder path encoding (UTF7-IMAP) mismatch; SPECIAL-USE detection returns localized name (e.g., "Gesendet" not "Sent").
**How to avoid:** Use `FolderMapper::getDraftsFolder()` / `getSentFolder()` which return correct UTF7-encoded path; test with Gmail, Outlook, Dovecot, cPanel servers.
**Warning signs:** "Folder not found" or "Invalid mailbox" errors on APPEND.

### Pitfall 3: SMTP Send Succeeds but Sent Folder APPEND Fails
**What goes wrong:** Email delivered but not saved to Sent folder (or vice versa).
**Why it happens:** Network interruption between SMTP and IMAP operations; wrong send order.
**How to avoid:** Follow D-17: APPEND to Sent FIRST, then SMTP. On SMTP failure, keep in Sent with 'pending' flag; scheduler retries SMTP. On APPEND failure, don't send.
**Warning signs:** User reports "email sent but not in Sent folder".

### Pitfall 4: Undo Send Doesn't Cancel in Time
**What goes wrong:** User clicks Undo but email already sent.
**Why it happens:** Scheduler not running every minute; `send_at` timestamp too aggressive; race condition.
**How to avoid:** Use `withoutOverlapping(5)` on scheduler; default 10s delay (configurable 5-30s); `send_at = now()->addSeconds(delay)`; test with `schedule:work` locally.
**Warning signs:** Toast countdown reaches 0 but Undo still shows; scheduler log shows gaps.

### Pitfall 5: Attachment Upload Exceeds Shared Hosting Limits
**What goes wrong:** Large attachments fail with 413/500 errors; temp files not cleaned up.
**Why it happens:** PHP `upload_max_filesize` / `post_max_size` too low; Livewire chunked upload not configured.
**How to avoid:** Enforce 25MB/file, 50MB total client-side; configure Livewire `livewire.uploads.temporary_file_upload.cleanup` in config; use `php.ini` detection in setup wizard.
**Warning signs:** Uploads stall at 100%; temp directory fills up.

### Pitfall 6: Quoted HTML Breaks Composer Layout
**What goes wrong:** Forwarded/replied HTML has conflicting styles, breaks modal, executes scripts.
**Why it happens:** Raw HTML from message inserted without sanitization; CSS leaks into composer.
**How to avoid:** Always pass quoted HTML through `MessageSanitizer::sanitize($html)` (HTMLPurifier server-side) → render in DOMPurify-sanitized iframe or `x-html` with trusted content only.
**Warning signs:** Composer modal width expands; styles bleed; console CSP errors.

## Code Examples

### Composer Modal Mount (Livewire)

```php
// app/Livewire/Mailbox/Composer.php
namespace App\Livewire\Mailbox;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\ComposerService;

class Composer extends Component
{
    use WithFileUploads;
    
    public $mode = 'compose'; // compose, reply, forward
    public $replyToMessage = null; // Message data for reply/forward
    public $to = '', $cc = '', $bcc = '', $subject = '', $body = '';
    public $attachments = [];
    public $compositionId; // UUID for LocalStorage key
    public $draftUid = null; // IMAP UID if synced
    
    protected $listeners = ['openComposer', 'loadDraft'];
    
    public function mount(): void
    {
        $this->compositionId = (string) \Illuminate\Support\Str::uuid();
        // Load LocalStorage draft via JS on mount
    }
    
    public function openComposer(array $params = []): void
    {
        $this->resetForm();
        $this->mode = $params['mode'] ?? 'compose';
        
        if (($this->mode === 'reply' || $this->mode === 'forward') && isset($params['message'])) {
            $this->replyToMessage = $params['message'];
            $this->populateFromMessage($params['message']);
        }
        
        $this->dispatch('show-modal', 'composer');
    }
    
    public function populateFromMessage(array $message): void
    {
        $this->to = $this->mode === 'forward' ? '' : $message['from_email'];
        $this->cc = $this->mode === 'forward' ? '' : $message['cc'];
        $this->subject = $this->buildSubject($message);
        $this->body = $this->buildQuotedBody($message);
    }
    
    public function buildQuotedBody(array $message): string
    {
        $attribution = "On {$message['date_formatted']}, {$message['from_name']} <{$message['from_email']}> wrote:";
        $quotedHtml = $this->messageSanitizer->sanitize($message['html_body'] ?? $message['text_body']);
        return "<blockquote class='quote-block'>{$attribution}<div>{$quotedHtml}</div></blockquote><br>";
    }
    
    public function saveDraft(): void
    {
        $data = $this->getComposerData();
        $this->composerService->saveDraft(auth()->id(), $data, $this->draftUid);
        $this->dispatch('toast', 'Draft saved');
    }
    
    public function send(): void
    {
        $this->validate([
            'to' => 'required|email',
            'subject' => 'required|max:255',
            'body' => 'required',
        ]);
        
        $result = $this->composerService->sendMessage(auth()->id(), $this->getComposerData());
        
        if ($result['success']) {
            $this->dispatch('toast', 'Message sent', 'success');
            $this->dispatch('undo-send', ['delay' => config('openmail.undo_send_delay', 10)]);
            $this->resetForm();
        } else {
            $this->dispatch('toast', 'Message queued for sending', 'warning');
        }
    }
}
```

### IMAP APPEND Implementation

```php
// app/Services/ImapMailboxService.php (extended)
public function appendMessage(string $folderPath, string $mimeMessage, array $flags = [], \Carbon\Carbon $internalDate = null): ?string
{
    $folder = $this->client->getFolder($folderPath);
    if (!$folder) {
        throw new \Exception("Folder not found: {$folderPath}");
    }
    
    $options = array_merge(['\\Seen'], $flags);
    $date = $internalDate?->format('d-M-Y H:i:s O');
    
    $result = $folder->appendMessage($mimeMessage, $options, $date);
    
    // Parse UID from result (webklex returns array with UID info)
    return $this->parseAppendUid($result);
}

private function parseAppendUid(array $result): ?string
{
    // webklex returns: ['uid' => 123, 'uidvalidity' => 456] or similar
    return $result['uid'] ?? null;
}
```

### MIME Message Building for Send + APPEND

```php
// app/Services/ComposerService.php
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Header\Headers;

private function buildMimeMessage(array $data): Email
{
    $email = (new Email())
        ->from(new Address($data['from_email'], $data['from_name']))
        ->to(array_map(fn($e) => new Address($e), explode(',', $data['to'])))
        ->cc(array_map(fn($e) => new Address(trim($e)), explode(',', $data['cc'] ?? '')))
        ->bcc(array_map(fn($e) => new Address(trim($e)), explode(',', $data['bcc'] ?? '')))
        ->subject($data['subject'])
        ->text($this->htmlToText($data['body']))
        ->html($data['body']);
    
    // Attachments
    foreach ($data['attachments'] ?? [] as $att) {
        $email->attach(DataPart::fromPath($att['path'])
            ->withDisposition('attachment')
            ->setFilename($att['name'])
            ->withMimeType($att['mime']));
    }
    
    // Generate Message-ID for threading
    $messageId = '<' . \Illuminate\Support\Str::uuid() . '@' . config('app.domain') . '>';
    $email->getHeaders()->addMessageId($messageId);
    
    // For replies/forwards: set In-Reply-To and References
    if (!empty($data['in_reply_to'])) {
        $email->getHeaders()->addHeader(new \Symfony\Component\Mime\Header\InReplyToHeader($data['in_reply_to']));
    }
    if (!empty($data['references'])) {
        $email->getHeaders()->addHeader(new \Symfony\Component\Mime\Header\ReferencesHeader($data['references']));
    }
    
    return $email;
}
```

### Undo Send Toast (Alpine.js)

```blade
{{-- resources/views/components/undo-send-toast.blade.php --}}
<div x-data="undoSendToast()" 
     x-show="visible" 
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 transform translate-y-2"
     x-transition:leave="transition ease-in duration-200"
     class="fixed bottom-4 right-4 z-50 flex items-center gap-3 bg-white border border-gray-200 rounded-lg shadow-lg p-4 min-w-[300px] max-w-md"
     role="alert" aria-live="polite">
    
    <div class="flex-1">
        <p class="font-medium text-gray-900">Message sent</p>
        <div class="mt-1 h-1.5 bg-gray-200 rounded-full overflow-hidden">
            <div class="h-full bg-blue-600" 
                 x-ref="progress" 
                 style="width: 100%; transition: width 1s linear;"></div>
        </div>
    </div>
    
    <button @click="undo()" 
            class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50 rounded-md transition-colors"
            :disabled="!visible">
        Undo
    </button>
    
    <button @click="dismiss()" 
            class="text-gray-400 hover:text-gray-600"
            aria-label="Dismiss">
        <svg class="w-5 h-5"><!-- X icon --></svg>
    </button>
</div>

<script>
function undoSendToast() {
    return {
        visible: false,
        timer: null,
        progressEl: null,
        duration: 10000, // ms from config
        
        init() {
            this.$watch('visible', (v) => {
                if (v) this.startCountdown();
            });
        },
        
        show(delay = 10000) {
            this.duration = delay;
            this.visible = true;
        },
        
        startCountdown() {
            this.progressEl = this.$refs.progress;
            const start = Date.now();
            const animate = () => {
                const elapsed = Date.now() - start;
                const progress = Math.max(0, 1 - elapsed / this.duration);
                this.progressEl.style.width = (progress * 100) + '%';
                
                if (progress > 0) {
                    this.timer = requestAnimationFrame(animate);
                } else {
                    this.hide();
                }
            };
            animate();
        },
        
        undo() {
            cancelAnimationFrame(this.timer);
            this.visible = false;
            this.$wire.undoSend(); // Calls Livewire method
        },
        
        dismiss() {
            cancelAnimationFrame(this.timer);
            this.visible = false;
        },
        
        hide() {
            this.visible = false;
        }
    }
}
</script>
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| TinyMCE/CKEditor (heavy, iframe-based) | Tiptap (headless, ProseMirror) | 2020+ | Smaller bundle, framework-agnostic, better Livewire integration |
| PHP `imap_mail_compose()` + `imap_append()` | webklex/php-imap `Folder::appendMessage()` | 2018+ | Pure PHP, no ext-imap needed, works on shared hosting |
| Custom MIME string building | Symfony Mime `Email` class | 2019+ (Symfony 4.3) | RFC-compliant, encoding, attachments, headers handled |
| Redis queue for delayed jobs | Database + Laravel Scheduler | 2020+ (Laravel 8) | Works on shared hosting; no extra infrastructure |
| Server-only draft autosave | LocalStorage + background IMAP sync | 2020+ (Gmail pattern) | Instant UX, survives tab close, no network latency |
| Inline quoted HTML (no sanitization) | Dual sanitization (HTMLPurifier + DOMPurify) | 2023+ (OWASP research) | Prevents parser differential attacks, XSS in quotes |

**Deprecated/outdated:**
- `@tiptap/extension-font-size` (deprecated Dec 2024) → Use `@tiptap/extension-text-style` with `fontSize` option
- `ext-imap` PHP extension → Not available on shared hosting; webklex/php-imap is pure PHP
- Redis-based queues → Violates shared hosting constraint

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | Tiptap 3.31.3 is stable and compatible with Livewire 3.8.x | Standard Stack | Editor bugs, build failures — mitigate with version pinning |
| A2 | webklex/php-imap `appendMessage()` returns UID reliably | Pattern 2 | Cannot track drafted/sent messages for updates/deletes |
| A3 | Laravel Scheduler runs reliably every minute on shared hosting via cron | Pattern 4 | Undo send delay inaccurate; pending sends not retried |
| A4 | IMAP SEARCH `SINCE` criteria works consistently across providers | Pattern 5 | Autocomplete cache incomplete; missed recent contacts |
| A5 | Livewire `WithFileUploads` handles 50MB total uploads on shared hosting | Pattern 6 | PHP.ini limits may block; need setup wizard validation |
| A6 | `MessageSanitizer` (HTMLPurifier + DOMPurify) handles quoted HTML edge cases | Pitfall 6 | XSS or layout breakage in composer |
| A7 | Symfony Mailer `using()` callback can inject raw MIME for sending | Pattern 3 | Cannot send pre-built MIME; must use Mailable class instead |

## Open Questions

1. **Tiptap build size**: Tiptap + extensions adds ~150KB gzipped. Confirm acceptable for shared hosting (assets built locally, deployed via FTP).
   - *What we know:* Laravel Vite builds locally; only compiled assets deployed.
   - *Recommendation:* Proceed; monitor bundle size; defer `@tiptap/extension-image` upload to "attach as file only" if needed.

2. **Image upload in editor**: D-04 lists "Image upload" in toolbar. Requires separate upload endpoint + temp storage.
   - *What we know:* Attachment handling already covers file uploads.
   - *Recommendation:* Defer inline image upload to "attach as file only" for v1; use `@tiptap/extension-image` for remote URLs only.

3. **Signature insertion**: D-04 mentions signature placeholder. Phase 5 implements signature management.
   - *What we know:* Phase 5 requirements SET-02 cover signatures.
   - *Recommendation:* Add Tiptap placeholder node `{{signature}}` replaced at send time if default signature exists.

4. **Mobile composer UX**: Full-screen bottom sheet vs. modal.
   - *What we know:* UI-SPEC recommends bottom sheet for mobile (<768px).
   - *Recommendation:* Implement responsive modal: centered on desktop, bottom sheet on mobile.

5. **Undo send default delay**: 10s (Gmail default). Confirm or adjust default in setup wizard.
   - *What we know:* D-21 says configurable 5-30s; UI-SPEC suggests 10s default.
   - *Recommendation:* Default 10s in config; expose in Phase 5 settings.

## Environment Availability

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| PHP | Runtime | ✓ | 8.3+ | — |
| Composer | PHP deps | ✓ | 2.x | — |
| Node.js | Asset build | ✓ | 18+ | — |
| npm | Frontend deps | ✓ | 9+ | — |
| MySQL/MariaDB | Database | ✓ | 8.0+/10.6+ | — |
| `mailparse` PECL | MIME parsing | ? | — | Use zbateson/mail-mime-parser (pure PHP) |
| Cron | Scheduler | ✓ | — | `php artisan schedule:work` (dev only) |

**Missing dependencies with no fallback:**
- None — all core deps available or have pure-PHP alternatives

**Missing dependencies with fallback:**
- `mailparse` PECL extension → zbateson/mail-mime-parser 4.x (pure PHP, slower but portable)

## Validation Architecture

> Required when `workflow.nyquist_validation` is enabled (absent = enabled).

### Test Framework

| Property | Value |
|----------|-------|
| Framework | Pest (Laravel default) / PHPUnit |
| Config file | `phpunit.xml` / `pest.php` |
| Quick run command | `php artisan test --filter=Composer` |
| Full suite command | `php artisan test` |

### Phase Requirements → Test Map

| Req ID | Behavior | Test Type | Automated Command | File Exists? |
|--------|----------|-----------|-------------------|-------------|
| COMP-01 | To/CC/BCC with autocomplete | Feature | `php artisan test tests/Feature/Composer/RecipientAutocompleteTest.php` | ❌ Wave 0 |
| COMP-02 | Subject field | Feature | `php artisan test tests/Feature/Composer/SubjectTest.php` | ❌ Wave 0 |
| COMP-03 | Plain text body | Feature | `php artisan test tests/Feature/Composer/PlainTextBodyTest.php` | ❌ Wave 0 |
| COMP-04 | HTML body with Tiptap | Feature | `php artisan test tests/Feature/Composer/RichTextTest.php` | ❌ Wave 0 |
| COMP-05 | Attachment upload (drag-drop) | Feature | `php artisan test tests/Feature/Composer/AttachmentUploadTest.php` | ❌ Wave 0 |
| COMP-06 | Draft autosave (LocalStorage + IMAP) | Feature | `php artisan test tests/Feature/Composer/DraftAutosaveTest.php` | ❌ Wave 0 |
| COMP-07 | Signature insertion | Feature | `php artisan test tests/Feature/Composer/SignatureTest.php` | ❌ Wave 0 |
| COMP-08 | Send via SMTP + Sent folder sync | Feature | `php artisan test tests/Feature/Composer/SendTest.php` | ❌ Wave 0 |
| COMP-09 | Cancel/discard draft | Feature | `php artisan test tests/Feature/Composer/DiscardDraftTest.php` | ❌ Wave 0 |
| COMP-10 | Reply with inline quoted text | Feature | `php artisan test tests/Feature/Composer/ReplyTest.php` | ❌ Wave 0 |
| COMP-11 | Reply All | Feature | `php artisan test tests/Feature/Composer/ReplyAllTest.php` | ❌ Wave 0 |
| COMP-12 | Forward with attachments | Feature | `php artisan test tests/Feature/Composer/ForwardTest.php` | ❌ Wave 0 |
| COMP-13 | Undo send (configurable delay) | Feature | `php artisan test tests/Feature/Composer/UndoSendTest.php` | ❌ Wave 0 |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=Composer --stop-on-failure`
- **Per wave merge:** `php artisan test`
- **Phase gate:** Full suite green before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Composer/ComposerTest.php` — base composer functionality
- [ ] `tests/Feature/Composer/RecipientAutocompleteTest.php` — covers COMP-01, COMP-03
- [ ] `tests/Feature/Composer/RichTextTest.php` — covers COMP-04
- [ ] `tests/Feature/Composer/AttachmentUploadTest.php` — covers COMP-05
- [ ] `tests/Feature/Composer/DraftAutosaveTest.php` — covers COMP-06
- [ ] `tests/Feature/Composer/SendTest.php` — covers COMP-08, COMP-13
- [ ] `tests/Feature/Composer/ReplyForwardTest.php` — covers COMP-10, COMP-11, COMP-12
- [ ] `tests/Unit/Services/ComposerServiceTest.php` — unit tests for send/draft/undo logic
- [ ] `tests/Unit/Services/ContactAutocompleteServiceTest.php` — unit tests for IMAP SEARCH caching
- [ ] Framework install: `npm install @tiptap/*` — if not detected in `package.json`

*(If no gaps: "None — existing test infrastructure covers all phase requirements")*

## Security Domain

> Required when `security_enforcement` is enabled (absent = enabled).

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | Yes | Laravel session auth (Phase 1) |
| V3 Session Management | Yes | Database sessions, rotation (Phase 1) |
| V4 Access Control | Yes | User owns their drafts/sent messages; policy gates |
| V5 Input Validation | Yes | **zod** (JS) + **Laravel validation** (server) for all composer inputs |
| V6 Cryptography | Yes | TLS for IMAP/SMTP (Phase 1); encrypted IMAP password in session |
| V7 Error Handling | Yes | Generic error messages; no stack traces in production |
| V8 Data Protection | Yes | No plaintext passwords; attachment MIME validation; UUID filenames |
| V9 Communications | Yes | STARTTLS/SSL for IMAP/SMTP; CSP headers (Phase 5) |
| V10 Malicious Code | Yes | HTMLPurifier + DOMPurify dual sanitization on quoted content |
| V11 Business Logic | Yes | Undo send prevents accidental delivery; rate limiting on send |
| V12 File Upload | Yes | MIME allowlist, 25MB/file, 50MB total, UUID names, temp storage |
| V13 API Security | N/A | No public API in v1 |
| V14 Configuration | Yes | Setup wizard validates IMAP/SMTP; secure defaults |

### Known Threat Patterns for Laravel + Livewire + Tiptap Stack

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| XSS via rich text editor content | Tampering | HTMLPurifier server-side on save; DOMPurify client-side on render; Tiptap paste sanitization |
| XSS via quoted HTML in reply/forward | Tampering | Reuse `MessageSanitizer` pipeline for all quoted content |
| Attachment filename path traversal | Tampering | UUID-prefixed stored filenames; MIME type validation; `Attachment::fromData()` for send |
| IMAP injection via folder names | Injection | `FolderMapper` uses SPECIAL-USE detection; no user input in folder paths |
| SMTP header injection | Injection | Symfony Mime `Email` class handles encoding; validate headers |
| CSRF on composer actions | Spoofing | Livewire CSRF protection; `wire:csrf` on forms |
| Rate limiting bypass on send | DoS | Database-backed rate limiter on send endpoint (Phase 5: SEC-04) |
| SSRF via image URLs in HTML | Tampering | DOMPurify strips `javascript:`/`data:`; CSP `img-src` restrictions |
| Draft autosave DoS (rapid saves) | DoS | Debounce 1.5s; rate limit autosave endpoint per user |
| Contact autocomplete enumeration | Information Disclosure | Cache only recent 30 days; TTL 7 days; no bulk export |

## Sources

### Primary (HIGH confidence)
- **webklex/php-imap 6.2.0 source** — `Folder::appendMessage()` implementation, Query API, Client/Folder classes `[VERIFIED: github.com/Webklex/php-imap src/Folder.php:470-487, src/Query/WhereQuery.php]`
- **Laravel 12.x Mail docs** — Mailable, raw MIME, Symfony Message customization, queueing `[VERIFIED: laravel.com/docs/12.x/mail]`
- **Laravel 12.x Scheduling docs** — `everyMinute()`, `withoutOverlapping()`, database cache, sub-minute tasks `[VERIFIED: laravel.com/docs/12.x/scheduling]`
- **Tiptap 3.x docs** — Extensions list, Vue 3 integration pattern (`onUpdate` callback), Editor API `[VERIFIED: tiptap.dev/docs]`
- **Alpine.js 3.x docs** — `x-data`, `x-show`, `x-transition`, `x-ref` patterns `[CITED: alpinejs.dev]`

### Secondary (MEDIUM confidence)
- **Livewire 3.x File Uploads docs** — `WithFileUploads`, `TemporaryUploadedFile`, validation `[CITED: laravel-livewire.com/docs/3.x/file-uploads]`
- **Symfony Mime component docs** — `Email` class, `DataPart`, headers, Message-ID `[CITED: symfony.com/doc/current/components/mime.html]`

### Tertiary (LOW confidence)
- **Tiptap + Livewire integration blogs** — Community patterns for hidden textarea sync `[ASSUMED]`
- **IMAP APPEND UID retrieval consistency** — Varies by server; webklex parses response `[ASSUMED]`

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH — Versions verified via npm/Composer; packages installed and used in Phase 1-2
- Architecture: HIGH — Patterns derived from existing codebase (Phase 1-2 services, Livewire components)
- Pitfalls: HIGH — Based on documented gotchas in webklex, Livewire, Tiptap, and Phase 1-2 experience
- Package legitimacy: MEDIUM — Tiptap/Alpine flagged SUS (too-new) but legitimate orgs with high downloads

**Research date:** 2026-09-06
**Valid until:** 2026-10-06 (30 days for stable stack; Tiptap v3 recent release may have patches)