---
phase: "06"
slug: "6-ui-ux-polish"
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-09-09"
---

# Phase 6 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | PHPUnit 11.5.56 |
| **Config file** | phpunit.xml (Unit + Feature suites; sqlite `:memory:`, `CACHE_STORE=array`, sync queue) |
| **Quick run command** | `php artisan test --filter=Mailbox` |
| **Full suite command** | `php artisan test` + `npm run build` (asset manifest check) |
| **Estimated runtime** | ~60 seconds |

---

## Sampling Rate

- **After every task commit:** Run `php artisan test --filter=Mailbox`
- **After every plan wave:** Run `php artisan test` + `npm run build`
- **Before `/gsd-verify-work`:** Full suite must be green + `npm run build` green + manual visual pass (contrast table re-check on shipped colors)
- **Max feedback latency:** ~60 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 06-01-01 | 01 | 1 | D-11 | T-06-01 / — | message-list renders skeleton guards, no spinner overlay | unit (Livewire) | `php artisan test --filter=MessageListLoadingTest` | ❌ W0 | ⬜ pending |
| 06-01-02 | 01 | 1 | D-01/D-04 | T-06-01 / — | primary actions carry gradient classes (`from-blue-600 to-purple-600`) | unit (Livewire) | `php artisan test --filter=UiPolishTest::test_compose_button_has_gradient_classes` | ❌ W0 | ⬜ pending |
| 06-01-03 | 01 | 1 | D-06/D-07/D-08 | T-06-01 / — | glass classes + badge tints present in mailbox sidebar/search markup | unit (source) | `php artisan test --filter=UiPolishTest::test_glass_surface_classes_present` | ❌ W0 | ⬜ pending |
| 06-01-04 | 01 | 1 | D-13 | T-06-01 / — | font CSS emitted by Vite | build-time | `node scripts/check-fontsource-build.js` | ❌ W0 (script) | ⬜ pending |
| 06-01-05 | 01 | 1 | D-14/D-15/D-16 | T-06-01 / — | density tokens still applied (1.4 line-height on regular) | unit (Livewire) | `php artisan test --filter=AppearanceTest` + `rg -c "line-height-base: 1\.4" resources/css/app.css` (expects 2) | ❌ W0 | ⬜ pending |
| 06-02-01 | 02 | 1 | Theme persistence | T-06-02 / — | appearance-tab interaction unchanged by restyle | unit (Livewire) | `php artisan test --filter=AppearanceTest` | ❌ W0 | ⬜ pending |
| 06-02-02 | 02 | 1 | CSP | T-06-02 / — | layout still emits nonce meta tag (`@cspNonceMetaTag`) | unit | `php artisan test --filter=UiPolishTest::test_layout_emits_csp_nonce_meta` | ❌ W0 | ⬜ pending |
| 06-03-01 | 03 | 2 | D-09/D-10/D-12 | T-06-03 / — | animations + overlay hook present in built assets | manual + grep | `rg -l "route-overlay\|animate-fade-in" public/build/assets/*.js` | manual | ⬜ pending |
| 06-03-02 | 03 | 2 | Contrast, blur, motion | T-06-03 / — | WCAG AA, visual quality | manual (browser + axe/Lighthouse) | — | manual-only | ⬜ pending |
| 06-05-03 | 05 | 4 | D-01..D-16 | T-06-01..05 / — | phase integration gate — full suite, build, CSP null-diff, QA evidence | full suite | `php artisan test && npm run build && node scripts/check-fontsource-build.js && test -z "$(git diff --stat config/csp.php)"` | — | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Feature/Mailbox/MessageListLoadingTest.php` — asserts `wire:loading`/`wire:loading.remove` skeleton guards exist and old overlay spinner markup is gone
- [ ] `tests/Feature/Settings/AppearanceTest.php` — theme/density radio states still render and persist after restyle
- [ ] `tests/Feature/UiPolishTest.php` — gradient classes on primary actions; glass classes on inputs/cards; `x-cloak` CSS rule present in app.css via a source-level assertion
- [ ] Build gate script — verify `public/build/manifest.json` includes fontsource css after `npm run build`

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Contrast compliance (WCAG AA) | D-01/D-04 | Requires visual judgment of shipped colors | Re-check contrast table on shipped gradient stops against light/dark surfaces |
| Glass effect feel (blur, opacity) | D-06/D-07 | Subjective visual quality | Toggle light/dark, verify readability and blur performance |
| Reduced-motion behavior | D-09/D-10/D-12 | Requires OS-level setting + visual check | Enable `prefers-reduced-motion`, verify animations suppressed |
| Route overlay fade | D-12 | Requires browser navigation session | Navigate between mailbox/settings, verify fade cross works and no flash |

*Manual checks required — these behaviors require visual judgment or OS-level state not captured by automated tests.*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 60s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** {pending / approved 2026-09-09}
