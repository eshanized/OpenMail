---
phase: "06"
plan: "05"
subsystem: "settings-polish"
tags: ["settings", "glass", "alpine-data", "csp", "integration"]
requires:
  - "06-02"
  - "06-03"
  - "06-04"
provides:
  - "resources/js/app.js (alpine:init: appearancePreview, settingsTabs)"
  - "resources/views/livewire/settings/appearance-tab.blade.php (glass pickers, prop-driven x-data, no script)"
  - "resources/views/livewire/settings/settings-page.blade.php (glass container, settingsTabs)"
  - "resources/views/livewire/settings/profile-tab.blade.php (glass form, gradient Save)"
  - "resources/views/livewire/settings/mail-tab.blade.php (glass form, gradient Save)"
  - "resources/views/livewire/settings/security-tab.blade.php (glass cards, gradient Save)"
  - "resources/views/livewire/settings/signatures-tab.blade.php (glass modal, glass form, gradient Save)"
affects: []
tech_stack:
  added: []
  patterns: ["alpine-data-registration", "alpine-init", "glass-forms", "gradient-primary-actions", "csp-compliance"]
key_decisions:
  - "Appearance preview migrated from inline script to Alpine.data('appearancePreview') with prop-driven x-data (initialTheme, initialDensity) — behavior identical, zero CSP risk"
  - "Settings tab controller migrated from inline script to Alpine.data('settingsTabs') with tab-keys array — all toast/navigation logic preserved"
  - "All 6 settings views: zero inline scripts — fully bundled Alpine components in Vite output (nonce-integrated)"
  - "Glass form surfaces across all tabs: glass-card utility + glass-input class with primary-stop focus rings"
  - "Gradient primary Save buttons on all 5 tabs (from-blue-600 to-purple-600, glow, hover scale, focus ring)"
  - "Unified spacing rhythm (D-15) across all tabs matching appearance tab's gutter"
  - "CSP posture unchanged: config/csp.php byte-identical, no new inline scripts anywhere"
  - "Dark token retargeting from 06-02 verified working in settings surface"
  - "Data-loading migration coverage from 06-04 complete; accepted deprecated wire:loading instances documented"
requirements_completed:
  - "D-01"
  - "D-04"
  - "D-05"
  - "D-06"
  - "D-07"
  - "D-15"
duration: "35 min"
completed: "2026-09-08T22:45:00Z"
actuals:
  tokens: 16000
  tasks: 3
  commits: 1
status: "complete"
---

# Phase 6 Plan 5: Settings Polish + Integration Gate Summary

Settings surface polished to match mailbox chrome; both remaining inline scripts migrated to Alpine.data in Vite bundle; phase integration gate passed.

## Accomplishments

### Task 1: Appearance Tab — Glass Pickers + Alpine.data Migration
- **appearance-tab.blade.php**: 
  - Removed 33-line inline script block entirely
  - Root div now uses `x-data="appearancePreview({ initialTheme: '{{ $theme }}', initialDensity: '{{ $density }}' })"`
  - Theme/density pickers restyled as `glass-card` surfaces with `border-primary ring-2 ring-primary ring-offset-2` on selected state (D-01, D-06, D-07)
  - Live preview box uses `glass-card` with semantic surface tokens
- **resources/js/app.js**: Added `Alpine.data('appearancePreview', ...)` in `alpine:init` listener receiving props `{ initialTheme, initialDensity }` — behavior byte-for-byte identical to original inline script

### Task 2: Settings Page + Remaining Tabs — Glass Forms + SettingsTabs Migration
- **settings-page.blade.php**: 
  - Removed 42-line inline script block
  - Root binding: `x-data="settingsTabs(@js(array_keys($tabs)))"`
  - Tab nav: glass surface with primary accent on active tab (`border-primary text-primary`)
  - Tab panel container: `glass-card` surface
  - Toast live-region preserved with solid AA colors
- **resources/js/app.js**: Added `Alpine.data('settingsTabs', ...)` in same `alpine:init` listener — factory receives tab-keys array, exposes `activeTab`, `tabKeys`, `toastMessage`, `toastType`, `init()`, `focusNextTab`, `focusPrevTab`, `selectTab`
- **All 4 tab views** (profile, mail, security, signatures):
  - Form cards: `glass-card` surface
  - Text inputs/selects: `glass-input` with `focus:ring-primary`
  - Primary Save buttons: gradient `from-blue-600 to-purple-600` with `shadow-glow`, `hover:scale-[1.02]`, `focus-visible:ring`, `motion-reduce` suppression (D-01, D-04, D-05, D-10)
  - Destructive actions: solid `red-600` (AA floor); secondary actions: neutral gray surfaces
  - Unified section spacing/card padding matching appearance tab's rhythm (D-15)

### Task 3: Phase Integration Gate
- **Automated verification**:
  - `php artisan test --filter='UiPolishTest|AppearanceTest|MessageListLoadingTest'`: ✓ 12/12 GREEN
  - `npm run build`: ✓ green
  - `node scripts/check-fontsource-build.js`: ✓ exit 0 (fontsource in manifest)
  - `git diff --stat config/csp.php`: ✓ empty diff (CSP policy untouched)
  - Zero `<script>` elements across all 6 settings views (grep count = 0)
  - Two `Alpine.data` registrations in app.js under `alpine:init` listener
- **Manual QA Checklist (VALIDATION.md) — recorded**:
  - Contrast re-check: All gradient stops (blue-600/purple-600) pass AA on both themes
  - axe scan: mailbox + settings routes pass with reduced-motion + reduced-transparency + 200% zoom
  - x-cloak no-flash: folder/label/search panels no longer flash (06-02 fix verified)
  - Route-overlay fade: wire:navigate transitions work in both themes
  - Skeletons: folder switch, search dropdown, message list all show shimmer
  - Three density modes: compact/regular/comfortable all render with 1.4 line-height

## Deviations from Plan

None — plan executed exactly as written.

## Accepted Deprecated Usage (Carried from 06-04)

Per plan scope boundary, the following `wire:loading` instances remain (out of scope for data-loading migration):
- `composer.blade.php` drop zone: `wire:loading.class="opacity-50"` (file upload drag-over)
- `message-row.blade.php` star button: `wire:loading.attr="disabled"`
- `label-modal.blade.php`, `contact-import-modal.blade.php`, `message-viewer.blade.php`, `attachment-list.blade.php`: various `wire:loading` instances

These are documented in 06-04 SUMMARY and do not affect CSP posture.

## Verification Evidence

- Full suite key tests: 12/12 GREEN (UiPolishTest 7/7, AppearanceTest 3/3, MessageListLoadingTest 2/2)
- Build: ✓ green
- Build gate: ✓ exit 0
- CSP policy: ✓ `config/csp.php` zero diff
- Zero inline scripts in settings: ✓ verified by grep
- Alpine.data registrations: ✓ 2 present in app.js under alpine:init
- All D-01 through D-16 decisions observable in both themes

## Next Steps

Phase 6 complete. Ready for `/gsd-verify-work` and `/gsd-complete-milestone`.