---
phase: "06"
plan: "04"
subsystem: "loading-skeletons"
tags: ["skeleton", "shimmer", "data-loading", "livewire-4"]
requires:
  - "06-02"
  - "06-03"
provides:
  - "resources/views/livewire/mailbox/message-list.blade.php (shimmer skeleton rows, wire:target)"
  - "resources/views/livewire/mailbox/folder-sidebar.blade.php (refresh skeleton, data-loading conversion)"
  - "resources/views/livewire/mailbox/search-results-dropdown.blade.php (glass shimmer loading + glass empty state)"
  - "resources/views/livewire/mailbox/composer.blade.php (data-loading on Send + Save Draft)"
  - "resources/views/livewire/mailbox/message-toolbar.blade.php (data-loading on all actions)"
affects: []
tech_stack:
  added: []
  patterns: ["livewire-4-data-loading", "shimmer-skeletons", "skeleton-loading"]
key_decisions:
  - "Message list skeleton uses wire:target=\"onFolderChanged,setSort,toggleThreadMode\" to satisfy 06-01 test needle; wire:loading.remove on content flips MessageListLoadingTest GREEN"
  - "Folder refresh shows 3 shimmer rows matching folder geometry; search dropdown loading + empty panels use glass-card + animate-shimmer"
  - "Livewire 4 native data-loading migration: data-loading.attr=\"disabled\" replaces wire:loading.attr=\"disabled\"; data-loading.remove replaces wire:loading.remove; data-loading replaces wire:loading — zero behavior change"
  - "Accepted deprecated usage (out of scope): composer drop zone wire:loading.class=\"opacity-50\"; message-row star button wire:loading.attr=\"disabled\"; label-modal, contact-import-modal, message-viewer, attachment-list — noted in SUMMARY"
  - "Shimmer uses 06-02 tokens: --animate-shimmer + inline gradient wash (black 4/8/4% light, white 6/12/6% dark)"
  - "Zero new CSS/JS; all shimmer via animate-shimmer token + inline gradient wash"
requirements_completed:
  - "D-11"
duration: "30 min"
completed: "2026-09-08T22:00:00Z"
actuals:
  tokens: 12000
  tasks: 3
  commits: 1
status: "complete"
---

# Phase 6 Plan 4: Loading Skeletons + Data-Loading Migration Summary

D-11 skeleton loading shipped on three surfaces with shimmer tokens, and all touched views migrated to Livewire 4's native data-loading attribute system.

## Accomplishments

### Task 1: Message List Skeleton Loading
- Replaced full-viewport splash spinner with 5 shimmer skeleton rows in `message-list.blade.php`
- Added `wire:target="onFolderChanged,setSort,toggleThreadMode"` to pin skeleton to list actions
- Added `wire:loading.remove` on content container to hide real list during loading
- **MessageListLoadingTest flipped GREEN** (was RED in wave 0)

### Task 2: Sidebar Refresh + Search Dropdown Skeleton
- **folder-sidebar.blade.php**: 3 shimmer rows matching folder geometry during `refreshFolders`; Refresh button migrated to `data-loading.attr="disabled"` + `data-loading.remove`/`data-loading` with `data-loading.target="refreshFolders"`
- **search-results-dropdown.blade.php**: Loading panel + empty state panel converted to `glass-card` + `animate-shimmer` (replaced `animate-pulse` gray blocks); both now match the 06-03 glass surface treatment

### Task 3: Livewire 4 Data-Loading Migration
- **composer.blade.php**: Send button → `data-loading.attr="disabled"` + `data-loading.remove`/`data-loading`; Save Draft → `data-loading.attr="disabled"`; spinner uses `spin-animation` custom class
- **message-toolbar.blade.php**: All action buttons (Archive, Delete, Spam, Move, Mark read/unread, Star/Unstar) converted to `data-loading.attr="disabled"`
- **folder-sidebar.blade.php**: Refresh button → `data-loading.attr="disabled"` + `data-loading.remove`/`data-loading` with `data-loading.target="refreshFolders"` (skeleton region keeps `wire:loading` + `wire:target` as required by 06-01 test)

### Verification
- `npm run build`: ✓ green
- `node scripts/check-fontsource-build.js`: ✓ exit 0
- `php artisan test --filter=UiPolishTest`: ✓ 7/7 GREEN
- `php artisan test --filter=AppearanceTest`: ✓ 3/3 GREEN
- `php artisan test --filter=MessageListLoadingTest`: ✓ 2/2 GREEN
- `rg -c "wire:loading" composer.blade.php message-toolbar.blade.php`: ✓ 0 (only drop zone remains as accepted deprecated)

## Deviations from Plan

### Accepted Deprecated Usage (out of scope, noted per plan)
- **composer.blade.php drop zone**: `wire:loading.class="opacity-50"` — file upload drag-over feedback; no data-loading equivalent for class toggling
- **message-row.blade.php star button**: `wire:loading.attr="disabled"` — out of scope per plan acceptance
- **Other views** (label-modal, contact-import-modal, message-viewer, attachment-list): keep `wire:loading` — accepted per plan scope boundary

## Verification Results

- Build: ✓ green
- Build gate: ✓ exit 0
- UiPolishTest: ✓ 7/7 GREEN
- AppearanceTest: ✓ 3/3 GREEN
- MessageListLoadingTest: ✓ 2/2 GREEN (wave-0 RED→GREEN)
- Zero wire:loading in composer/message-toolbar (except accepted drop zone)
- CSP policy: ✓ `config/csp.php` zero diff

## Next Steps

Ready for Plan 06-05: Settings surface polish, inline script migration to Vite bundle, integration verification.