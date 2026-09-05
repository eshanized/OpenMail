# Phase 2: Mailbox Core — Plan Verification Verdict

**Verified:** 2026-09-05
**Plans checked:** 2 (02-01-PLAN.md, 02-02-PLAN.md)
**Status:** ISSUES FOUND — 2 blocker(s), 1 warning(s), 1 info

---

## Dimension Results

### Dimension 1: Requirement Coverage — PASS

All required requirement IDs are covered across the two plans:

| Requirement | Plan | Status |
|-------------|------|--------|
| MAIL-01 | 02-01 | Covered (FolderSidebar, ImapMailboxService) |
| MAIL-02 | 02-01 | Covered (FolderMapper with name heuristics for custom folders) |
| MAIL-03 | 02-01 | Covered (DB cache with UIDVALIDITY) |
| MAIL-04 | 02-01 | Covered (createFolder, renameFolder, deleteFolder) |
| MSG-01 | 02-01 | Covered (MessageList component) |
| MSG-02 | 02-01 | Covered (read/unread indicator in message-row) |
| MSG-03 | 02-01 | Covered (star indicator in message-row) |
| MSG-04 | 02-01 | Covered (attachment indicator in message-row) |
| MSG-05 | 02-01 | Covered (LengthAwarePaginator, 25/page) |
| MSG-06 | 02-02 | Covered (bulk selection toolbar, Alpine.js state) |
| MSG-07 | 02-01 | Covered (sort controls for date/sender/subject/size) |
| VIEW-01 | 02-02 | Covered (plain text rendering) |
| VIEW-02 | 02-02 | Covered (sandboxed iframe with srcdoc) |
| VIEW-03 | 02-02 | Covered (message headers display) |
| VIEW-04 | 02-02 | Covered (attachment list with download) |
| VIEW-05 | 02-02 | Covered (mark read/unread toggle) |
| VIEW-06 | 02-02 | Covered (star/flag toggle) |
| VIEW-07 | 02-02 | Covered (delete → Trash) |
| VIEW-08 | 02-02 | Covered (move to folder) |
| VIEW-09 | 02-02 | Covered (remote image blocking) |
| VIEW-10 | 02-02 | Covered (print-friendly view) |
| DB-03 | 02-01 | Covered (folder metadata in DB) |
| DB-04 | 02-01 | Covered (message_metadata table) |

### Dimension 2: Task Completeness — PASS

All tasks have the required structural elements:

| Plan | Task | Type | Files | Action | Verify | Done | read_first |
|------|------|------|-------|--------|--------|------|------------|
| 02-01 | Tracer | tracer | ✓ | ✓ | ✓ | ✓ | ✓ |
| 02-01 | Message list | auto | ✓ | ✓ | ✓ | ✓ | ✓ |
| 02-02 | Message viewer | auto | ✓ | ✓ | ✓ | ✓ | ✓ |
| 02-02 | Actions + toolbar | auto | ✓ | ✓ | ✓ | ✓ | ✓ |

### Dimension 3: Dependency Correctness — PASS

- 02-01: wave=1, depends_on=[] (Wave 1, no dependencies — correct)
- 02-02: wave=2, depends_on=["02-01"] (Wave 2, depends on Plan 01 — correct)
- No cycles detected. Wave assignment is consistent with dependencies.

### Dimension 4: Key Links Planned — PASS

Key links from must_haves are traced through task actions:
- FolderSidebar ← ImapMailboxService via FolderMapper ✓
- MessageList ← ImapMailboxService ✓
- Folder metadata caching with UIDVALIDITY ✓
- MessageViewer ← ImapMailboxService → MessageSanitizer ✓
- Attachment download ← IMAP streaming (D-12) ✓
- Bulk actions ← IMAP STORE/COPY (D-17) ✓

### Dimension 5: Scope Sanity — WARNING

| Plan | Tasks | Files | Tokens (est) | Status |
|------|-------|-------|-------------|--------|
| 02-01 | 2 | 21 | 55,000 | Borderline — 21 files exceeds 8-file target |
| 02-02 | 2 | 12 | 50,000 | Borderline — 12 files exceeds 8-file target |

Plan 01 has 21 files_modified. While this includes tests and config files, the core implementation spans migrations, models, services, Livewire components, and views. The plan is structured as two large tasks (tracer + message list) rather than more granular work. This is acceptable given the tracer pattern requires end-to-end wiring, but execution quality should be monitored.

### Dimension 6: Verification Derivation — PASS

Truths in must_haves are user-observable:
- "User sees folder sidebar with Inbox, Sent, Drafts, Trash, Spam, Archive plus any custom folders" ✓
- "Message list displays sender, subject, timestamp, read/unread state, star indicator, attachment indicator" ✓
- "User can open a message and see rendered HTML in a sandboxed iframe or plain text" ✓
- "Attachments list with download works without path traversal issues" ✓

Artifacts map to truths. Key links connect them. Derivation is sound.

### Dimension 7: Context Compliance — PASS

All locked decisions (D-01 through D-18) are implemented:
- D-01: SPECIAL-USE + heuristics ✓ (FolderMapper in Task 1)
- D-02: Hierarchical folder tree ✓ (FolderSidebar with expand/collapse)
- D-03: Direct IMAP mutations ✓ (createFolder, renameFolder, deleteFolder)
- D-04: DB cache with UIDVALIDITY ✓ (Folder model + Cache::remember)
- D-05: IMAP SEARCH + SORT ✓ (MessageList server-side sorting)
- D-06: Headers-only fetch ✓ (setFetchBody(false))
- D-07: IMAP \Seen as source of truth ✓ (setFlag method)
- D-08: Sort criteria mapping ✓ (date/sender/subject/size)
- D-09: Sandboxed iframe srcdoc ✓ (email-renderer component)
- D-10: Dual sanitization pipeline ✓ (HTMLPurifier + DOMPurify)
- D-11: Block remote images ✓ (blockRemoteImages + opt-in banner)
- D-12: Stream attachments ✓ (no local storage)
- D-13: Core metadata per message ✓ (message_metadata table)
- D-14: UIDVALIDITY invalidation ✓ (cache invalidation logic)
- D-15: Sync on folder open ✓ (FolderSidebar mount)
- D-16: Alpine.js selection state ✓ (bulk selection)
- D-17: IMAP STORE/COPY ✓ (bulk operations)
- D-18: Gmail-style toolbar ✓ (MessageToolbar component)

No deferred ideas included in plans.

### Dimension 7b: Scope Reduction Detection — PASS

No scope reduction detected. All decisions are delivered as specified — no "v1", "static for now", "placeholder", or "future enhancement" language found.

### Dimension 8: Nyquist Compliance — WARNING

Verify blocks are present and runnable. However:

**Issue:** No explicit `<fails_when>` statements in verify blocks. The verification commands are:
```bash
php artisan test --filter=FolderMapperTest 2>&1 | tail -5
npm run build 2>&1 | tail -3
php artisan migrate:status 2>/dev/null | grep -c "Yes"
```

These commands will exit non-zero on test failure or build failure, but the verify block does not declare what output constitutes failure. The `2>/dev/null` on `migrate:status` suppresses errors that could feed false passes.

### Dimension 9: Cross-Plan Data Contracts — PASS

Plan 01 creates ImapMailboxService and models that Plan 02 consumes. The data contracts are compatible:
- ImapMailboxService.getMessage() returns Message object ✓
- MessageMetadata model provides list data ✓
- MessageSanitizer.sanitizeHtml() returns sanitized string ✓
- Folder model provides folder list for move-to-folder dropdown ✓

No conflicting transforms on shared data.

### Dimension 10: AGENTS.md Compliance — PASS

Plans respect project constraints:
- Shared hosting compatible (no Redis, no workers) ✓
- Laravel monolith architecture ✓
- Database-only sessions/cache ✓
- webklex/php-imap (pure PHP, no ext-imap) ✓
- HTMLPurifier + DOMPurify dual sanitization (ADR-004) ✓
- No premature multi-tenancy ✓

### Dimension 11: Research Resolution — PASS

RESEARCH.md has `## Open Questions` section with 3 questions:
1. Livewire version reconciliation — RESOLVED (use v4.x since installed)
2. IMAP server compatibility — RESOLVED (implement both SPECIAL-USE + heuristics)
3. DOMPurify installation — RESOLVED (run in parent page before srcdoc)

All questions have resolution markers. Section does not have `(RESOLVED)` suffix on the heading, but individual questions are resolved. Acceptable.

### Dimension 12: Pattern Compliance — PASS (skipped)

No PATTERNS.md found for this phase. Dimension skipped.

### Dimension: Verify Command Format Sanity — WARNING

Plan 01 verify block uses `2>/dev/null` on `migrate:status`:
```bash
php artisan migrate:status 2>/dev/null | grep -c "Yes"
```

This suppresses error output. If `migrate:status` fails, the error is swallowed, `grep -c` returns 0, and the verify continues without flagging the issue. This is a soft failure — the command is checking migration status, and a failure here would be caught by the "full test suite passes" check anyway.

### Dimension: Verify Command Path Resolvability — PASS

All file paths in `<files>` blocks are standard Laravel paths. The verify commands target the correct working directory (`cd /home/snigdha/Desktop/OpenMail`).

### Dimension: Numeric/Factual Claim Authority — PASS

No numeric claims in plans that conflict with RESEARCH.md.

---

## Issues Found

### Blockers

1. **[task_completeness] Missing `acceptance_criteria` field in all tasks**
   - Plan: 02-01, 02-02
   - Tasks: All 4 tasks
   - Description: Verification criteria explicitly require "Every task has read_first and acceptance_criteria fields." All tasks have `read_first` but none have `acceptance_criteria`. The `<done>` section serves a similar purpose but is not the formally specified `acceptance_criteria` field.
   - Fix: Add `<acceptance_criteria>` element to each task, or confirm that `<done>` is accepted as equivalent and update the criteria.

2. **[verify_command_format] `2>/dev/null` suppresses errors in verify block**
   - Plan: 02-01
   - Task: Tracer (Step 9)
   - Description: `php artisan migrate:status 2>/dev/null | grep -c "Yes"` suppresses stderr. If migrate:status fails (e.g., DB connection issue), the error is swallowed and `grep -c` returns 0, which could incorrectly indicate no migrations applied without surfacing the root cause.
   - Fix: Remove `2>/dev/null` or redirect stderr to a visible output. The command should fail visibly if the database is unreachable.

### Warnings

1. **[scope_sanity] Plan 01 has 21 files_modified**
   - Plan: 02-01
   - Description: 21 files exceeds the 8-file warning threshold. This includes migrations, models, services, Livewire components, views, config, JS, package.json, vite.config, and tests. The tracer pattern requires end-to-end wiring which justifies the file count, but execution quality should be monitored.
   - Fix: Consider splitting into smaller tasks if context budget becomes an issue during execution.

### Info

1. **[verify_command_format] No explicit `fails_when` statements**
   - Plan: 02-01, 02-02
   - Description: Verify blocks have runnable commands but no `<fails_when>` sibling declaring what constitutes failure. Commands exit non-zero on test/build failure, which is implicit, but explicit failure declarations improve clarity.
   - Fix: Add `<fails_when>` elements to verify blocks (e.g., "tests exit non-zero" or "build output contains 'error'").

---

## Recommendation

2 blocker(s) require revision before execution:

1. Add `acceptance_criteria` elements to all tasks (or confirm `<done>` equivalence)
2. Remove `2>/dev/null` from verify commands to prevent error suppression

The plans are structurally sound — requirements are fully covered, dependencies are correct, decisions are implemented, and scope is reasonable. These are mechanical fixes, not architectural issues.

Returning to planner with feedback.
