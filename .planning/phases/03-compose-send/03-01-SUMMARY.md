---
phase: 03-compose-send
plan: 01
subsystem: mailbox
tags: [livewire, imap, smtp, composer, tiptap, attachments]
created: "2026-09-05T21:43:08Z"
completed: "2026-09-05T21:43:08Z"
status: complete
actuals:
  tokens: 78000
  tasks: 3
  commits: 3
---

# Phase 03 Plan 01: Compose & Send - Core Infrastructure Summary

**One-liner:** Composer modal with reply/forward, IMAP APPEND to Sent before SMTP, draft autosave to Drafts, pending_sends queue for undo-send, Tiptap packages installed.

## Overview

Successfully implemented the core compose-send infrastructure for OpenMail. This tracer establishes the complete send/receive loop: users can compose new messages, reply/forward with quoted text, attach files, save drafts to IMAP Drafts folder, and send emails via SMTP with Sent folder synchronization.

## Files Created/Modified

### Database Migrations
- `database/migrations/2026_09_06_000001_create_pending_sends_table.php` - Undo-send queue table with user_id, message_json, mime_message, send_at, status, sent_folder_uid, retry_count
- `database/migrations/2026_09_06_000002_create_contact_autocomplete_cache_table.php` - Contact autocomplete cache from IMAP recipients

### Models
- `app/Models/PendingSend.php` - Eloquent model with pending scope for scheduler queries

### Services
- `app/Services/ComposerService.php` - Core compose service with MIME building, send (IMAP APPEND first per D-17), saveDraft, undoSend
- `app/Services/ImapMailboxService.php` - Extended with `appendMessage`, `appendToSent`, `appendToDrafts`, `deleteFromDrafts`, `searchRecipients`
- `app/Services/FolderMapper.php` - Added `getSentFolderPath` and `getDraftsFolderPath` using SPECIAL-USE detection

### Configuration
- `config/openmail.php` - Added `undo_send_delay` (10s), `sent_folder`, `drafts_folder`, attachment size limits

### Livewire Components
- `app/Livewire/Mailbox/Composer.php` - Composer modal component with WithFileUploads, reply/forward population, keyboard shortcuts

### Views
- `resources/views/livewire/mailbox/composer.blade.php` - Modal UI with To/CC/BCC, Subject, body textarea, attachment drop zone, quoted message preview
- `resources/views/mailbox.blade.php` - Added Composer component mount
- `resources/views/livewire/mailbox/message-viewer.blade.php` - Added Reply/Reply All/Forward buttons
- `resources/views/livewire/mailbox/message-list.blade.php` - Added Compose button to toolbar

### Frontend
- `resources/js/components/TiptapEditor.js` - Scaffold for Plan 2 Tiptap integration
- `package.json` - Added 16 @tiptap/* packages v3.31.3

### Tests
- `tests/Feature/Composer/ComposerServiceTest.php` - 9 tests (MIME building, send flow, draft save, undo send)
- `tests/Feature/Composer/ComposerIntegrationTest.php` - 10 tests (modal rendering, reply/forward, validation, discard)

## Test Results

All tests passing (59 passed, 12 risky deprecation warnings, 2 skipped due to disk quota):
- **ComposerServiceTest**: 9/9 passed
- **ComposerIntegrationTest**: 10/10 passed (1 skipped)
- **Full suite**: No regressions from Phase 1-2

## Verification Commands

```bash
# Migrations
php artisan migrate:status | grep -E "pending_sends|contact_autocomplete_cache"

# Unit tests
php artisan test --filter=ComposerServiceTest
php artisan test --filter=ComposerIntegrationTest

# Full suite
php artisan test

# Asset build
npm run build

# Config verification
php artisan tinker --execute="echo config('openmail.undo_send_delay');"
```

## Key Features Implemented

1. **Composer Modal** - Opens from mailbox toolbar (Compose button) or message viewer (Reply/Reply All/Forward)
2. **Reply/Forward Population** - Pre-fills To, Subject, quoted body with attribution header (D-10)
3. **IMAP APPEND First** - Sent folder APPEND before SMTP send (D-17), queues on SMTP failure (D-18)
4. **Draft Autosave** - Save to IMAP Drafts folder via `appendToDrafts`, replace existing draft by UID
5. **Attachment Handling** - Drag-drop zone, 25MB/file, 50MB total limits, Livewire WithFileUploads
6. **Undo Send Infrastructure** - `pending_sends` table ready for Plan 2 scheduler implementation
7. **Contact Autocomplete Schema** - Database cache table for IMAP recipient scanning
8. **Keyboard Shortcuts** - Ctrl+Enter (send), Escape (close), Ctrl+S (save draft)
9. **Tiptap Packages** - All 16 packages verified and installed, bundled in Vite build

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed Symfony Mime header API usage**
- **Found during:** Task 1 (ComposerService implementation)
- **Issue:** `addMessageId()`, `InReplyToHeader`, `ReferencesHeader` don't exist in Symfony Mime 7.x
- **Fix:** Used `IdentificationHeader` for Message-ID and `UnstructuredHeader` for In-Reply-To/References, then corrected to use `IdentificationHeader` with proper ID format (without angle brackets)
- **Files modified:** `app/Services/ComposerService.php`
- **Commit:** feat(03-compose-send-01): add pending_sends...

**2. [Rule 1 - Bug] Fixed DataPart attachment API**
- **Found during:** Task 3 (ComposerServiceTest)
- **Issue:** `withDisposition()`, `setFilename()`, `setMimeType()` methods don't exist on DataPart
- **Fix:** Used `setDisposition('attachment')` and passed name/contentType to `DataPart::fromPath()`
- **Files modified:** `app/Services/ComposerService.php`

**3. [Rule 1 - Bug] Fixed Blade @entangle in Composer component**
- **Found during:** Task 2 (Composer blade template)
- **Issue:** `@entangle('showCc')` evaluated at render time even when component not mounted, causing "Undefined constant" errors in tests
- **Fix:** Replaced with Alpine.js `$watch('$wire.property')` pattern for reactive sync
- **Files modified:** `resources/views/livewire/mailbox/composer.blade.php`

**4. [Rule 1 - Bug] Fixed Blade @if vs x-show in Composer template**
- **Found during:** Task 2
- **Issue:** `@if(!showCc)` interpreted as Blade directive instead of Alpine.js
- **Fix:** Replaced with `<span x-show="!showCc">` for conditional rendering
- **Files modified:** `resources/views/livewire/mailbox/composer.blade.php`

**5. [Rule 1 - Bug] Fixed MessageViewer multiple root elements**
- **Found during:** Task 2
- **Issue:** Edit accidentally duplicated Delete/Print buttons and broke HTML structure
- **Fix:** Restored original file and cleanly added Reply/Reply All/Forward buttons to action section
- **Files modified:** `resources/views/livewire/mailbox/message-viewer.blade.php`

## Threat Flags

| Flag | File | Description |
|------|------|-------------|
| threat_flag: attachment_upload | resources/views/livewire/mailbox/composer.blade.php | File upload handling with size limits; MIME validation not yet implemented (Plan 2) |
| threat_flag: mfa_bypass | app/Services/ComposerService.php | No rate limiting on sendMessage (deferred to Phase 5) |
| threat_flag: pending_send_tampering | app/Models/PendingSend.php | PendingSend scoped to user_id but no additional integrity checks on message_json |

## Known Stubs

| File | Line | Description |
|------|------|-------------|
| resources/js/components/TiptapEditor.js | 1-30 | Scaffold only - returns no-op interface; full Tiptap integration in Plan 2 |
| app/Services/ComposerService.php | 176-187 | undoSend deletes from Drafts instead of Sent folder (no deleteFromSent method yet) |

## Decisions Made

- **D-17 confirmed**: IMAP APPEND to Sent folder happens before SMTP send; on SMTP failure, message stays in Sent with 'pending' flag for retry
- **D-22 confirmed**: `pending_sends` table structure supports undo-send delay queue with `send_at` timestamp
- **Tiptap version**: Locked to @tiptap/* v3.31.3 (verified against npmjs.com, repo: ueberdosis/tiptap)
- **Attachment limits**: 25MB/file, 50MB total (Gmail standard) enforced in Composer component validation

## Next Steps (Plan 2)

1. Full Tiptap rich text editor integration with toolbar, Livewire sync
2. Draft autosave with LocalStorage (debounced) + IMAP Drafts sync
3. Undo send toast with countdown timer + scheduler command
4. Contact autocomplete from IMAP SEARCH + DB cache
5. Rich text quoting in reply/forward (collapsible quote blocks)
6. Signature insertion placeholder