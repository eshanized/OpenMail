---
phase: "03"
slug: "compose-send"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
# audit-milestone §5.5 distinguishes NOT-VALIDATED (draft) from PARTIAL (validated + nyquist_compliant: false) (#2117)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-09-06"
---

# Phase 03 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest (PHP) / Vitest (JS) |
| **Config file** | `phpunit.xml` / `vitest.config.js` |
| **Quick run command** | `php artisan test --filter=Compose` |
| **Full suite command** | `php artisan test && npm run test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Compose`
- **After every plan wave:** Run `php artisan test && npm run test`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 03-01-01 | 01 | 1 | COMP-01 | T-03-01 | Recipient chips sanitized, no XSS in autocomplete | unit | `php artisan test --filter=ComposerRecipientTest` | ❌ W0 | ⬜ pending |
| 03-01-02 | 01 | 1 | COMP-02 | T-03-02 | Subject field required, length validated | unit | `php artisan test --filter=ComposerSubjectTest` | ❌ W0 | ⬜ pending |
| 03-01-03 | 01 | 1 | COMP-03, COMP-04 | T-03-03 | Tiptap content sanitized via HTMLPurifier + DOMPurify | unit | `php artisan test --filter=ComposerBodyTest` | ❌ W0 | ⬜ pending |
| 03-01-04 | 01 | 1 | COMP-05 | T-03-04 | Attachment upload: size limit, MIME allowlist, UUID filename | unit | `php artisan test --filter=ComposerAttachmentTest` | ❌ W0 | ⬜ pending |
| 03-01-05 | 01 | 1 | COMP-06 | T-03-05 | Draft autosave: LocalStorage debounce, IMAP sync interval | integration | `php artisan test --filter=DraftAutosaveTest` | ❌ W0 | ⬜ pending |
| 03-01-06 | 01 | 1 | COMP-07 | T-03-06 | Signature placeholder inserted, replaced on send | unit | `php artisan test --filter=ComposerSignatureTest` | ❌ W0 | ⬜ pending |
| 03-01-07 | 01 | 1 | COMP-08 | T-03-07 | SMTP send + IMAP APPEND to Sent (reverse order) | integration | `php artisan test --filter=SendMessageTest` | ❌ W0 | ⬜ pending |
| 03-01-08 | 01 | 1 | COMP-09 | T-03-08 | Cancel/discard clears LocalStorage, cleans up IMAP Drafts | unit | `php artisan test --filter=DiscardDraftTest` | ❌ W0 | ⬜ pending |
| 03-01-09 | 01 | 1 | COMP-10, COMP-11 | T-03-09 | Reply/Reply All: quoted text collapsible, attribution correct | integration | `php artisan test --filter=ReplyComposerTest` | ❌ W0 | ⬜ pending |
| 03-01-10 | 01 | 1 | COMP-12 | T-03-10 | Forward: quoted text + attachments forwarded | integration | `php artisan test --filter=ForwardComposerTest` | ❌ W0 | ⬜ pending |
| 03-01-11 | 01 | 1 | COMP-13 | T-03-11 | Undo send: delay configurable, toast with countdown, cancel moves to Drafts | integration | `php artisan test --filter=UndoSendTest` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/ComposerTest.php` — stubs for COMP-01 through COMP-13
- [ ] `tests/Unit/ComposerTest.php` — unit test stubs for each requirement
- [ ] `tests/Unit/MessageSanitizerTest.php` — existing, extend for quoted content
- [ ] `npm install -D vitest @testing-library/vue` — if not present (JS tests for Tiptap integration)

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Composer modal opens/closes smoothly | COMP-01 | Visual/UX timing | Click "Compose" → verify modal appears, Escape closes, backdrop click closes |
| Tiptap toolbar buttons work | COMP-04 | Rich text interactions | Type in editor, click Bold/Italic/Link → verify formatting applied |
| Drag-drop attachment upload | COMP-05 | Browser file API | Drag file to drop zone → verify upload progress, preview appears |
| Autocomplete dropdown keyboard nav | COMP-01 | Accessibility/UX | Type in To field → ↓/↑/Enter/Tab navigation works |
| Undo send toast countdown | COMP-13 | Time-based UI | Send email → verify toast appears, countdown decrements, Undo works |
| Collapsible quote blocks in reply | COMP-10/11 | Interaction/UX | Open reply → verify quote collapsed, click "Show quoted text" → expands |
| Mobile composer bottom sheet | COMP-01 | Responsive behavior | Resize to <768px → composer opens as full-screen bottom sheet |
| Draft autosave indicator states | COMP-06 | Async timing | Type in composer → "Saving…" → "Saved just now" → "Saved 2 min ago" |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending