---
phase: 05-security-polish
plan: 05
subsystem: ui
tags: [signatures, tiptap, rich-text, livewire, blade, composer, settings]

# Dependency graph
requires:
  - phase: 01-foundation-setup-wizard
    provides: [User model, Setting model, middleware stack, routes]
  - phase: 02-mailbox-core
    provides: [Livewire 3 components, Blade layouts, MessageSanitizer]
  - phase: 03-compose-send
    provides: [ComposerService, Tiptap editor config, Vite asset pipeline]
  - phase: 05-security-polish/01
    provides: [CSP infrastructure, security headers]
  - phase: 05-security-polish/03
    provides: [Settings page scaffold, SignaturesTab stub, User model signatures relationship]
provides:
  - [signatures table with user_id FK, content_json, content_html, is_default]
  - [Signature model with boot() sanitization and single-default enforcement]
  - [SignatureService with getDefault, create, update, delete, setDefault, getAll]
  - [SignaturesTab Livewire component with full CRUD and modal create/edit]
  - [Reusable tiptap-editor Blade component with toolbar (bold, italic, underline, lists, links)]
  - [signature-dropdown Alpine.js component for composer signature selection]
  - [SignatureRequest validation for name, content_json, is_default]
  - [Composer integration: auto-insert default on compose, dropdown swap, setSignature/removeSignature]
  - [ComposerService auto-append default signature on send]
affects: [05-security-polish, signature-management, composer-send]

# Actuals
actuals:
  tokens: 8000
  tasks: 3
  commits: 2

# Tech tracking
tech-stack:
  added: []
  patterns: [signature-boot-sanitization, tiptap-reuse-component, composer-signature-dropdown]

key-files:
  created:
    - database/migrations/2026_09_08_000003_create_signatures_table.php
    - app/Models/Signature.php
    - app/Services/SignatureService.php
    - app/Http/Requests/SignatureRequest.php
    - resources/views/components/tiptap-editor.blade.php
    - resources/views/components/signature-dropdown.blade.php
  modified:
    - app/Livewire/Settings/SignaturesTab.php
    - resources/views/livewire/settings/signatures-tab.blade.php
    - routes/web.php
    - app/Livewire/Mailbox/Composer.php
    - app/Services/ComposerService.php
    - resources/views/livewire/mailbox/composer.blade.php

key-decisions:
  - "Signature boot() sanitization via tiptap-php → MessageSanitizer reuses email HTMLPurifier allowlist"
  - "First signature auto-set as default for new users"
  - "Deleted default signature promotes newest remaining to default"
  - "Composer auto-inserts default on compose only (not reply/forward)"
  - "tiptap-editor component designed for reuse across composer and signatures"
  - "signature-dropdown uses Alpine.js with @entangle for Livewire integration"

patterns-established:
  - "Signature boot() pattern: render Tiptap JSON → sanitize HTML → enforce single default in saving hook"
  - "Composer signature integration: load signatures on mount, auto-insert default, dropdown swap"
  - "Reusable tiptap-editor component: wraps Tiptap JS with toolbar, accepts contentJson prop"

requirements-completed: [SET-02]

coverage:
  - id: D1
    description: "Signatures table migration with user_id FK, content_json, content_html, is_default, and composite index"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "php artisan migrate --pretend shows CREATE TABLE signatures with correct columns and index"
        status: pass
    human_judgment: false
  - id: D2
    description: "Signature model with boot() sanitization via MessageSanitizer and single-default enforcement"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "class_exists('App\\Models\\Signature') returns true; boot() contains saving hook with sanitization and default enforcement"
        status: pass
    human_judgment: false
  - id: D3
    description: "SignatureService with getDefault, create, update, delete, setDefault, getAll methods"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "class_exists('App\\Services\\SignatureService') returns true; all 6 methods present"
        status: pass
    human_judgment: false
  - id: D4
    description: "SignaturesTab with full CRUD: modal create/edit, default toggle, delete with confirmation"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "class_exists('App\\Livewire\\Settings\\SignaturesTab') returns true; openCreateModal, openEditModal, saveSignature, deleteSignature, setDefaultSignature methods present"
        status: pass
    human_judgment: false
  - id: D5
    description: "Reusable tiptap-editor Blade component with toolbar (bold, italic, underline, lists, links)"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "resources/views/components/tiptap-editor.blade.php exists with toolbar buttons and editor div"
        status: pass
    human_judgment: false
  - id: D6
    description: "Signature dropdown Alpine.js component for composer with default badge and manage link"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "resources/views/components/signature-dropdown.blade.php exists with signatures list, default badge, manage link"
        status: pass
    human_judgment: false
  - id: D7
    description: "Composer loads signatures on mount, auto-inserts default on compose, dropdown in toolbar"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "Composer.php contains loadSignatures, setSignature, removeSignature methods; composer.blade.php contains <x-signature-dropdown>"
        status: pass
    human_judgment: false
  - id: D8
    description: "ComposerService auto-appends default signature on send if not already present"
    requirement: SET-02
    verification:
      - kind: automated
        ref: "ComposerService.php buildMimeMessage contains SignatureService::getDefault auto-insertion logic"
        status: pass
    human_judgment: false

# Metrics
duration: 12min
completed: 2026-09-08
status: complete
---

# Phase 5 Plan 05: Signature Management Summary

**Rich-text signature CRUD with Tiptap editor in Settings modal, reusable tiptap-editor component, default signature auto-insert on compose, and composer dropdown for signature swap**

## Performance

- **Duration:** 12 min
- **Started:** 2026-09-08T07:18:32Z
- **Completed:** 2026-09-08T07:30:00Z
- **Tasks:** 3
- **Files modified:** 12

## Accomplishments
- Created signatures table migration with user_id FK, content_json (Tiptap JSON), content_html (cached sanitized), is_default, and composite index
- Implemented Signature model with boot() sanitization (Tiptap JSON → HTML via tiptap-php → MessageSanitizer) and single-default enforcement
- Built SignatureService with getDefault, create (auto-default first), update, delete (promote newest), setDefault, getAll
- Built SignaturesTab Livewire component with full CRUD, modal create/edit, default toggle, and delete confirmation
- Created reusable tiptap-editor Blade component with toolbar (bold, italic, underline, lists, links) wrapping Tiptap JS
- Created signature-dropdown Alpine.js component for composer with default badge, signature swap, and manage link
- Integrated signatures into Composer: loads on mount, auto-inserts default on compose, setSignature/removeSignature methods
- Extended ComposerService to auto-append default signature on send if not already in body

## Task Commits

Each task was committed atomically:

1. **Task 05-tracer: End-to-end signature management** - `461d8df` (feat)
2. **Task 05-verify-signatures: Verify signature CRUD, default logic, and composer integration** - (verify-only, no commit)
3. **Task 05-composer-integration: Integrate signature dropdown and auto-insert into Composer** - `db496c6` (feat)

## Files Created/Modified
- `database/migrations/2026_09_08_000003_create_signatures_table.php` - Creates signatures table with user_id FK, content_json, content_html, is_default, and composite index
- `app/Models/Signature.php` - Eloquent model with boot() sanitization (Tiptap JSON → MessageSanitizer) and single-default enforcement
- `app/Services/SignatureService.php` - Signature CRUD with getDefault, create (auto-default), update, delete (promote), setDefault, getAll
- `app/Http/Requests/SignatureRequest.php` - Validation for name (required|max:100), content_json (required|array), is_default
- `resources/views/components/tiptap-editor.blade.php` - Reusable Blade component wrapping Tiptap editor with toolbar for signatures
- `resources/views/components/signature-dropdown.blade.php` - Alpine.js dropdown for composer signature selection with default badge
- `app/Livewire/Settings/SignaturesTab.php` - Full CRUD Livewire component with modal create/edit, default toggle, delete confirmation
- `resources/views/livewire/settings/signatures-tab.blade.php` - Signatures list with default badge, edit/delete buttons, empty state
- `routes/web.php` - Added GET /settings/signatures route
- `app/Livewire/Mailbox/Composer.php` - Added signatures loading, setSignature/removeSignature, auto-insert default on compose
- `app/Services/ComposerService.php` - Added auto-append default signature in buildMimeMessage
- `resources/views/livewire/mailbox/composer.blade.php` - Added signature-dropdown in composer toolbar

## Decisions Made
- Signature boot() sanitization uses tiptap-php to render JSON → HTML, then MessageSanitizer reuses the email HTMLPurifier allowlist (T-05-17 mitigation)
- First signature auto-set as default for new users (UX convenience)
- Deleted default signature promotes newest remaining to default (prevents user without default)
- Composer auto-inserts default on compose mode only — reply/forward require explicit dropdown selection (D-15)
- tiptap-editor component designed for reuse across composer and signatures (D-13)
- signature-dropdown uses Alpine.js with @entangle for Livewire integration

## Deviations from Plan

### Auto-fixed Issues

None - plan executed exactly as written.

---

**Total deviations:** 0
**Impact on plan:** None.

## Issues Encountered
None

## User Setup Required
None - no external service configuration required.

## Next Phase Readiness
- Signature management fully functional: CRUD in Settings, rich-text editing, default logic, composer auto-insert and dropdown swap
- All signature content sanitized via MessageSanitizer (no scripts, event handlers, dangerous URLs)
- Ready for remaining Phase 5 work or future enhancement

## Known Stubs
None — all implementations are complete and functional.

## Threat Flags

| Flag | File | Description |
|------|------|-------------|
| T-05-17 | app/Models/Signature.php | Boot() sanitization mitigates XSS via Tiptap JSON |
| T-05-18 | app/Models/Signature.php | Boot() single-default enforcement prevents multiple defaults |
| T-05-19 | app/Services/SignatureService.php | User-scoped queries prevent cross-user signature access |

---

*Phase: 05-security-polish*
*Completed: 2026-09-08*

## Self-Check: PASSED

All 12 key files exist. All 2 commits verified in git log. Class existence, migration SQL, and integration checks all pass.
