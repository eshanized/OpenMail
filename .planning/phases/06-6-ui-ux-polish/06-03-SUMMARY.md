---
phase: "06"
plan: "03"
subsystem: "mailbox-chrome"
tags: ["css", "glass", "badges", "gradient", "hover", "focus"]
requires:
  - "06-02"
provides:
  - "resources/css/app.css (glass-card, glass-input, blob layer, fallbacks)"
  - "resources/views/layouts/app.blade.php (blob layer)"
  - "resources/views/layouts/mailbox.blade.php (glass sidebar)"
  - "resources/views/livewire/mailbox/folder-sidebar.blade.php (glass, soft-tag badges)"
  - "resources/views/livewire/mailbox/message-row.blade.php (hover/focus)"
  - "resources/views/livewire/mailbox/search-results-dropdown.blade.php (glass panel)"
  - "resources/views/livewire/mailbox/label-chips.blade.php (soft-tag overflow)"
  - "resources/views/livewire/mailbox/composer.blade.php (glass modal, gradient Send)"
  - "resources/views/livewire/mailbox/message-toolbar.blade.php (gradient Archive, red Delete)"
  - "resources/views/components/composer-recipient-chips.blade.php (glass inputs)"
affects: []
tech_stack:
  added: []
  patterns: ["glassmorphism", "soft-tag-badges", "gradient-primary-actions", "surface-token-restyle"]
key_decisions:
  - "Glass cards/inputs use backdrop-blur-sm (8px) with translucent fills (white/60 light, white/10 dark) and subtle borders; @supports fallback to near-solid surface colors"
  - "Decorative blob layer (blue-500/violet-500 at 25% opacity) makes glass visible over flat cream/soft-dark surfaces (Pitfall 4)"
  - "Soft-tag badges use tinted 100-level bg + 600-level text (blue-600 for unread, gray-500 for overflow) — AA on cream"
  - "Primary actions Send/Archive: gradient from-blue-600 to-purple-600, shadow-glow, hover scale 1.02, focus-visible ring, motion-reduce suppression"
  - "Destructive Delete: solid red-600 (AA floor); secondary buttons remain neutral"
  - "Message rows: surface-tinted hover + left gradient accent + focus ring; NO scale transform on full-width rows (Pitfall 6)"
  - "Message-viewer remains chrome-free (glass/gradient classes excluded — T-06-03 guard)"
requirements_completed:
  - "D-01"
  - "D-04"
  - "D-05"
  - "D-06"
  - "D-07"
  - "D-08"
  - "D-10"
duration: "35 min"
completed: "2026-09-08T21:38:00Z"
actuals:
  tokens: 18000
  tasks: 3
  commits: 1
status: "complete"
---

# Phase 6 Plan 3: Full Mailbox Chrome Summary

Expanded the tracer into the complete mailbox chrome: glass surfaces, soft-tag badges, gradient primary actions, and hover/focus polish on all interactive elements.

## Accomplishments

### Task 1: Glass Foundation
- **resources/css/app.css**: Added glass-card and glass-input utilities with:
  - Translucent fills (white/60 light, white/10 dark) paired with surface tokens
  - 8px backdrop blur (`backdrop-blur-sm` at v4 scale) + 1px subtle borders
  - `@supports` fallback for browsers without backdrop-filter (near-solid surface colors)
  - `@media (prefers-reduced-transparency: reduce)` guard removing blur + forcing near-solid fills
- **resources/views/layouts/app.blade.php**: Added decorative blob layer (`.bg-blobs`) — fixed, pointer-events-none, aria-hidden, z-0 behind nav/main. Two radial gradients using D-04 locked 500-level stops (blue-500/violet-500) at 25% opacity — the only allowed use of decorative-large stops per D-04 reconciliation.

### Task 2: Mailbox Chrome Restyle
- **mailbox.blade.php**: Glass sidebar shell + mobile drawer using `glass-card`
- **folder-sidebar.blade.php**: 
  - Glass card container
  - Tab bar restyled with surface tokens (active: tinted fill + primary accent border/text)
  - Folder rows: surface-tinted hover, active folder gets border-l-2 border-primary
  - Unread badges: soft-tag pattern standardized to `bg-blue-100 text-blue-600` (AA on cream)
  - Total count text: `text-gray-500` (cream contrast floor)
- **message-row.blade.php**: 
  - Removed `hover:bg-gray-50` scale (Pitfall 6 — full-width rows don't scale)
  - Added `hover:bg-white/60 dark:hover:bg-white/10` surface tint
  - Left gradient accent bar on hover via `hover:bg-linear-to-r from-blue-600 to-purple-600` (handled by CSS)
  - `focus-visible:ring-2 ring-primary` for keyboard navigation
- **search-results-dropdown.blade.php**: Converted to `glass-card backdrop-blur-sm`; skeleton and empty state restyled to match
- **label-chips.blade.php**: Overflow `+N` pill restyled to soft-tag (`bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300`); label chips preserve validated inline hex colors unchanged

### Task 3: Primary Actions + Glass Inputs
- **composer.blade.php**: 
  - Modal container: `glass-card`
  - Send button: exact gradient primary treatment matching tracer (glow, hover scale 1.02, focus ring, motion-reduce)
  - Subject field: `glass-input`
- **message-toolbar.blade.php**:
  - Toolbar container: `glass-card`
  - Archive button: gradient primary (same as Compose/Send)
  - Delete button: solid `bg-red-600` (AA floor per contrast table)
  - Secondary buttons (Spam, Move, Mark read/unread, Star): neutral glass/white surfaces
- **composer-recipient-chips.blade.php**: Chips container uses `glass-input`; input field already borderless transparent

## Verification Results

- `npm run build`: ✓ green
- `php artisan test --filter=UiPolishTest`: ✓ **7/7 GREEN** (all methods now pass)
  - compose gradient ✓, send/archive gradient ✓, x-cloak ✓, glass surfaces ✓, soft-tag badges ✓, message-viewer chrome guard ✓, CSP nonce ✓
- `php artisan test --filter=AppearanceTest`: ✓ 3/3 GREEN (theme/density persistence intact)
- CSP policy: ✓ `config/csp.php` zero diff
- No new inline scripts in any restyled view (T-06-04)

## Deviations from Plan

None — plan executed exactly as written.

## Next Steps

Ready for Plan 06-04: Loading skeletons with shimmer, Livewire 4 data-loading attributes, replacing the full-screen spinner overlay.