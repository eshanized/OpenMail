# Phase 3: Compose & Send - Discussion Log

**Date:** 2026-09-06
**Phase:** 3 (Compose & Send)
**Status:** Completed

---

## Areas Discussed

### 1. Composer UI & Rich Text Editor

**Question 1:** How should the composer be presented in the UI?
- **Selected:** Modal overlay (like Gmail default compose)
- **Options presented:**
  - Full-page composer (like Gmail compose in new tab)
  - Modal overlay (like Gmail default compose) ✓
  - Split-pane inline (like Outlook Web)
  - Bottom drawer (mobile-friendly)

**Question 2:** Which rich text editor should power the HTML body composition?
- **Selected:** Tiptap (headless, ProseMirror-based)
- **Options presented:**
  - Tiptap (headless, ProseMirror-based) ✓
  - Quill.js (established, toolbar included)
  - Simple textarea + Markdown preview
  - ContentEditable div with custom toolbar

**Question 3:** What fields and layout should the composer modal include?
- **Selected:** Compact: To, Subject, Body with CC/BCC/Attachments in collapsible sections
- **Options presented:**
  - Standard: To, CC (expandable), BCC (expandable), Subject, Body, Attachments
  - Compact: To, Subject, Body with CC/BCC/Attachments in collapsible sections ✓
  - Full: All fields always visible
  - Two-column: Recipients left, Subject/Body right

**Question 4:** What formatting options should the Tiptap toolbar include?
- **Selected:** Full: Standard + Tables, Image upload, Emoji, Strikethrough, Sub/superscript, Font size/color
- **Options presented:**
  - Essential: Bold, Italic, Underline, Link, Lists, Quote
  - Standard: Essential + Heading levels, Code block, Horizontal rule, Text alignment
  - Full: Standard + Tables, Image upload, Emoji, Strikethrough, Sub/superscript, Font size/color ✓
  - Minimal: Bold, Italic, Link only

---

### 2. Draft Autosave Strategy

**Question 1:** When should draft autosave trigger?
- **Selected:** On every keystroke (debounced 1-2s)
- **Options presented:**
  - On every keystroke (debounced 1-2s) ✓
  - On field blur + periodic (every 30s)
  - Only on field blur + before unload
  - Manual save button + before unload

**Question 2:** Where should drafts be stored during composition?
- **Selected:** LocalStorage first, sync to server (Drafts folder) on interval
- **Options presented:**
  - LocalStorage first, sync to server (Drafts folder) on interval ✓
  - Direct to server (IMAP Drafts folder) on every autosave
  - Database (local app state) + IMAP sync on explicit save
  - LocalStorage only until explicit 'Save Draft' button

**Question 3:** What happens when user returns to a draft (clicks draft in Drafts folder)?
- **Selected:** Load from IMAP Drafts folder (server is source of truth)
- **Options presented:**
  - Load from IMAP Drafts folder (server is source of truth) ✓
  - Load from LocalStorage if newer, otherwise IMAP
  - Always load from IMAP, warn if LocalStorage has newer data
  - Merge strategy: combine IMAP + LocalStorage changes

**Question 4:** How should draft cleanup work when a draft is sent or discarded?
- **Selected:** Delete from IMAP Drafts folder on send, clear LocalStorage
- **Options presented:**
  - Delete from IMAP Drafts folder on send, clear LocalStorage ✓
  - Move to Sent folder on send, keep in Drafts until explicit delete
  - Archive draft (move to Archive folder) on send
  - Soft delete (mark \Deleted) on send, expunge on next sync

---

### 3. Reply/Forward Quoting Format

**Question 1:** How should quoted text be formatted in replies/forwards?
- **Selected:** Collapsible quoted sections (Gmail-style)
- **Options presented:**
  - Plain text with '>' prefix per line (traditional email style)
  - HTML blockquote with attribution header
  - Both: HTML blockquote for HTML compose, plain '>' for plain text
  - Collapsible quoted sections (Gmail-style) ✓

**Question 2:** What should the attribution header include ("On X, Y wrote:")?
- **Selected:** Full: Date, sender name, sender email
- **Options presented:**
  - Full: Date, sender name, sender email ✓
  - Minimal: Sender name only
  - Configurable: User chooses format in settings
  - None: Just the quoted content, no header

**Question 3:** How to handle quoting when original message is HTML vs plain text?
- **Selected:** Convert HTML to plain text for quoting, wrap in blockquote
- **Options presented:**
  - Convert HTML to plain text for quoting, wrap in blockquote ✓
  - Preserve HTML formatting in quote (sanitized)
  - Match compose mode: HTML quote for HTML compose, text for text
  - Always quote as plain text with '>' prefix

**Question 4:** Should forward quoting differ from reply quoting?
- **Selected:** Same format: collapsible blockquote with attribution
- **Options presented:**
  - Same format: collapsible blockquote with attribution ✓
  - Forward includes full headers + original attachments
  - Forward as EML attachment (RFC 822)
  - Forward: inline full message (no collapse), Reply: collapsible

---

### 4. Attachment Handling in Composer

**Question 1:** How should attachment upload work in the composer?
- **Selected:** Drag-drop zone + click to browse, immediate upload to server temp storage
- **Options presented:**
  - Drag-drop zone + click to browse, immediate upload to server temp storage ✓
  - Drag-drop zone, files stored in LocalStorage until send
  - Chunked upload for large files (>10MB), direct for small
  - Traditional: click browse, upload on form submit with send

**Question 2:** What file size limits and validation should apply?
- **Selected:** 25MB per file, 50MB total (Gmail standard), MIME type allowlist
- **Options presented:**
  - 25MB per file, 50MB total (Gmail standard), MIME type allowlist ✓
  - Configurable per deployment (default 25MB), MIME type blocklist
  - No hard limit (server PHP upload_max_filesize applies), virus scan on upload
  - 10MB per file, 25MB total, strict allowlist (images, PDF, Office docs only)

**Question 3:** How should attachments be displayed and managed in the composer?
- **Selected:** List with filename, size, remove button, image preview thumbnails
- **Options presented:**
  - List with filename, size, remove button, image preview thumbnails ✓
  - Compact chips with filename + remove, hover for size/preview
  - Thumbnail grid for images, list for others
  - Minimal: just count + total size, expand to see details

**Question 4:** What happens to uploaded attachments when draft is autosaved vs sent?
- **Selected:** Temp storage → move to IMAP Drafts on draft save, move to Sent on send
- **Options presented:**
  - Temp storage → move to IMAP Drafts on draft save, move to Sent on send ✓
  - Temp storage → only move to IMAP on send, draft save references temp IDs
  - Upload directly to IMAP Drafts on attach, update on each autosave
  - Base64 encode in LocalStorage draft, upload to IMAP only on send

---

### 5. SMTP Sending & Sent Folder Sync

**Question 1:** How should sending and Sent folder sync work?
- **Selected:** IMAP APPEND to Sent first → SMTP send (reverse order)
- **Options presented:**
  - SMTP send → IMAP APPEND to Sent folder (2 steps, best effort)
  - SMTP send with BCC to self → rule/catchall moves to Sent
  - IMAP APPEND to Sent first → SMTP send (reverse order) ✓
  - Transactional: queue send job, on success APPEND to Sent, on failure retry

**Question 2:** What if IMAP APPEND to Sent succeeds but SMTP send fails?
- **Selected:** Queue for background retry, keep in Sent with 'pending' flag
- **Options presented:**
  - Leave in Sent folder, show error, user can retry send manually
  - Delete from Sent folder (IMAP STORE +Deleted + EXPUNGE), show error
  - Move to Drafts folder instead, show error
  - Queue for background retry, keep in Sent with 'pending' flag ✓

**Question 3:** Shared hosting can't run permanent queue workers. How to handle background retry?
- **Selected:** Laravel scheduler (cron) runs retry job every minute
- **Options presented:**
  - Laravel scheduler (cron) runs retry job every minute ✓
  - Client-side retry: JS polls / retries on page load
  - Database 'pending_sends' table, user clicks 'Retry' in UI
  - Abandon reverse-order: do SMTP first, then APPEND (standard)

**Question 4:** How to determine the Sent folder path for IMAP APPEND?
- **Selected:** Configurable in setup wizard, default to 'Sent'
- **Options presented:**
  - Use FolderMapper SPECIAL-USE (\Sent) detection (from Phase 2)
  - Configurable in setup wizard, default to 'Sent' ✓
  - Try SPECIAL-USE first, fall back to common names (Sent, Sent Items, Sent Messages)
  - Store Sent folder path in user settings after first detection

---

### 6. Undo Send Implementation

**Question 1:** How should Undo Send be implemented?
- **Selected:** Delay SMTP send by configurable 5-30s, hold in memory/DB, cancel on undo
- **Options presented:**
  - Delay SMTP send by configurable 5-30s, hold in memory/DB, cancel on undo ✓
  - Send immediately, show undo toast, if clicked: send recall request (RFC 8474)
  - Queue in 'Outbox' folder, delay APPEND to Sent, move on timeout
  - SMTP send with future DATE header, server delivers later

**Question 2:** Where to store the pending message during undo delay?
- **Selected:** Database 'pending_sends' table with serialized message + send_at timestamp
- **Options presented:**
  - Database 'pending_sends' table with serialized message + send_at timestamp ✓
  - In-memory queue (Redis) — but not available on shared hosting
  - File-based queue in storage/pending-sends/
  - IMAP Drafts folder with special flag, move to Sent on timeout

**Question 3:** What should the undo UI look like?
- **Selected:** Toast notification with 'Undo' button, countdown timer (Gmail style)
- **Options presented:**
  - Toast notification with 'Undo' button, countdown timer (Gmail style) ✓
  - Persistent banner at top of mailbox with progress bar
  - Modal dialog: 'Message will be sent in X seconds. Undo?'
  - Small inline indicator near composer, expands on hover

**Question 4:** When user clicks Undo, what happens to the message?
- **Selected:** Cancel send, move to Drafts folder (IMAP APPEND to Drafts)
- **Options presented:**
  - Cancel send, move to Drafts folder (IMAP APPEND to Drafts) ✓
  - Cancel send, keep in pending_sends table, reopen composer with message
  - Cancel send, offer 'Save as Draft' or 'Discard'
  - Cancel send, auto-save as draft in background, reopen composer

---

### 7. Contact Autocomplete Source

**Question 1:** Where should contact autocomplete suggestions come from for Phase 3?
- **Selected:** Recent recipients from IMAP (scan Sent/Inbox for To/CC addresses)
- **Options presented:**
  - Recent recipients from IMAP (scan Sent/Inbox for To/CC addresses) ✓
  - Local address book (DB table) — but Phase 4 builds this
  - Both: merge recent IMAP recipients + local contacts (when available)
  - User's email contacts via CardDAV (future provider integration)

**Question 2:** How to scan and cache recent recipients for autocomplete?
- **Selected:** On-demand IMAP SEARCH for recent messages, extract addresses, cache in DB
- **Options presented:**
  - On-demand IMAP SEARCH for recent messages, extract addresses, cache in DB ✓
  - Background sync: periodic IMAP scan of Sent folder, store in DB with recency score
  - Extract from local MessageMetadata table (already cached from Phase 2)
  - Client-side: fetch recent messages via API, extract in browser

**Question 3:** How should the autocomplete dropdown behave?
- **Selected:** Support multiple selection (comma-separated), add as chips
- **Options presented:**
  - Type-ahead: filter suggestions as user types, show name + email
  - Show recent first, then alphabetical, group by domain
  - Support multiple selection (comma-separated), add as chips ✓
  - Allow creating new contact from autocomplete ('Add [email]')

**Question 4:** Should CC and BCC fields share the same autocomplete source?
- **Selected:** All fields share, but BCC hides recipient chips from other fields
- **Options presented:**
  - Yes, same recent recipients for To/CC/BCC
  - CC: recent recipients; BCC: rarely used, maybe empty or same
  - All fields share, but BCC hides recipient chips from other fields ✓
  - To: recent + contacts; CC/BCC: recent only

---

## Deferred Ideas

None — discussion stayed within phase scope.

---

## Agent's Discretion Items

- Exact Tiptap extension configuration and custom node/views for email-specific needs (e.g., signature insertion placeholder)
- Database schema for `pending_sends` table (indexes, foreign keys, serialization format)
- IMAP SEARCH query optimization for recent recipient extraction (date ranges, folder selection)
- Scheduler frequency and retry backoff strategy for pending sends
- LocalStorage key structure and conflict resolution for draft autosave
- Exact collapsible quote component implementation (Alpine.js + CSS)
- Temp file storage location and cleanup strategy for composer attachments

---

## Next Steps

1. Run `/gsd-plan-phase 3` to create detailed implementation plans
2. Researcher will investigate Tiptap Livewire integration, IMAP APPEND patterns, scheduler retry logic
3. Planner will create task breakdown with dependencies
4. Executor will implement the composer, draft autosave, send pipeline, undo send, and autocomplete