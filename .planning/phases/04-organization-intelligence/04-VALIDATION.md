---
phase: "04"
slug: "organization-intelligence"
# status lifecycle: draft (seeded by plan-phase) → validated (set by validate-phase §6)
# audit-milestone §5.5 distinguishes NOT-VALIDATED (draft) from PARTIAL (validated + nyquist_compliant: false) (#2117)
status: draft
nyquist_compliant: false
wave_0_complete: false
created: "2026-09-06"
---

# Phase 04 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Pest (Laravel default) + PHPUnit |
| **Config file** | `phpunit.xml` (existing) |
| **Quick run command** | `./vendor/bin/pest --filter=Phase4` |
| **Full suite command** | `./vendor/bin/pest` |
| **Estimated runtime** | ~120 seconds |

---

## Sampling Rate

- **After every task commit:** Run `./vendor/bin/pest --filter=Phase4`
- **After every plan wave:** Run `./vendor/bin/pest`
- **Before `/gsd-verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Threat Ref | Secure Behavior | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|------------|-----------------|-----------|-------------------|-------------|--------|
| 01-01-01 | 01 | 1 | THR-01, THR-02 | — | Thread tree builds correctly from Message-ID/In-Reply-To/References | unit | `./vendor/bin/pest tests/Unit/ThreadBuilderTest.php::testBuildsThreadTree` | ❌ W0 | ⬜ pending |
| 01-01-02 | 01 | 1 | THR-03 | — | Subject normalization fallback works for missing/corrupted headers | unit | `./vendor/bin/pest tests/Unit/ThreadBuilderTest.php::testSubjectFallback` | ❌ W0 | ⬜ pending |
| 01-01-03 | 01 | 1 | THR-04 | — | Thread expand/collapse via Alpine.js | feature | `./vendor/bin/pest tests/Feature/ThreadUITest.php::testExpandCollapse` | ❌ W0 | ⬜ pending |
| 01-01-04 | 01 | 1 | THR-05 | — | Stable thread ordering (latest message date desc) | unit | `./vendor/bin/pest tests/Unit/ThreadBuilderTest.php::testStableOrder` | ❌ W0 | ⬜ pending |
| 01-02-01 | 02 | 1 | SRCH-01, SRCH-07 | — | Scout database engine indexes MessageMetadata on save/sync | feature | `./vendor/bin/pest tests/Feature/SearchIndexingTest.php::testRealTimeIndexSync` | ❌ W0 | ⬜ pending |
| 01-02-02 | 02 | 1 | SRCH-02, SRCH-03, SRCH-04, SRCH-05 | — | Search filters: folder, date range, attachment, read/unread | feature | `./vendor/bin/pest tests/Feature/SearchTest.php::testFilters` | ❌ W0 | ⬜ pending |
| 01-02-03 | 02 | 1 | SRCH-06 | — | Search result highlighting wraps matches in `<mark>` | unit | `./vendor/bin/pest tests/Unit/SearchHighlightTest.php` | ❌ W0 | ⬜ pending |
| 01-02-04 | 02 | 1 | SRCH-07 | — | Global search + folder filter + instant dropdown | feature | `./vendor/bin/pest tests/Feature/SearchTest.php::testInstantDropdown` | ❌ W0 | ⬜ pending |
| 01-03-01 | 03 | 2 | CONT-01, CONT-02 | — | Contact CRUD + recent recipients auto-populated from IMAP | feature | `./vendor/bin/pest tests/Feature/ContactTest.php::testCrudAndRecentRecipients` | ❌ W0 | ⬜ pending |
| 01-03-02 | 03 | 2 | CONT-03 | — | Unified autocomplete (local + IMAP cache) in composer | feature | `./vendor/bin/pest tests/Feature/ContactAutocompleteTest.php::testUnifiedAutocomplete` | ❌ W0 | ⬜ pending |
| 01-03-03 | 03 | 2 | CONT-04, CONT-05 | — | Contact editing + groups | feature | `./vendor/bin/pest tests/Feature/ContactTest.php::testEditAndGroups` | ❌ W0 | ⬜ pending |
| 01-03-04 | 03 | 2 | CONT-06 | — | vCard 3.0 import/export with conflict resolution | feature | `./vendor/bin/pest tests/Feature/VCardTest.php` | ❌ W0 | ⬜ pending |
| 01-04-01 | 04 | 2 | LBL-01, LBL-02 | — | Label CRUD + apply/remove from messages | feature | `./vendor/bin/pest tests/Feature/LabelTest.php::testCrudAndApply` | ❌ W0 | ⬜ pending |
| 01-04-02 | 04 | 2 | LBL-03, LBL-04 | — | Label sidebar with counts + color chips + filter | feature | `./vendor/bin/pest tests/Feature/LabelTest.php::testSidebarAndFilter` | ❌ W0 | ⬜ pending |
| 01-04-03 | 04 | 2 | LBL-05 | — | Archive action: IMAP MOVE + label sync + undo toast | feature | `./vendor/bin/pest tests/Feature/LabelTest.php::testArchiveAction` | ❌ W0 | ⬜ pending |
| 01-05-01 | 05 | 2 | SRCH-01, THR-04 | — | Search results page: two-column layout, pagination, active filters | feature | `./vendor/bin/pest tests/Feature/SearchResultsPageTest.php` | ❌ W0 | ⬜ pending |
| 01-05-02 | 05 | 2 | THR-04, UI-SPEC | — | Thread row component: Alpine.js x-transition, load more, keyboard | feature | `./vendor/bin/pest tests/Feature/ThreadUITest.php::testThreadRowComponent` | ❌ W0 | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

- [ ] `tests/Unit/ThreadBuilderTest.php` — JWZ algorithm tests (THR-01, THR-02, THR-03, THR-05)
- [ ] `tests/Feature/ThreadUITest.php` — Thread expansion/collapse + component tests (THR-04)
- [ ] `tests/Feature/SearchTest.php` — Search filters, instant dropdown, global search (SRCH-01 through SRCH-07)
- [ ] `tests/Feature/SearchIndexingTest.php` — Scout real-time index sync (SRCH-07)
- [ ] `tests/Unit/SearchHighlightTest.php` — Highlighting on snippets (SRCH-06)
- [ ] `tests/Feature/SearchResultsPageTest.php` — Results page layout, filters, pagination (SRCH-02)
- [ ] `tests/Feature/ContactTest.php` — Contact CRUD, recent recipients, groups (CONT-01, CONT-02, CONT-04, CONT-05)
- [ ] `tests/Feature/ContactAutocompleteTest.php` — Unified autocomplete merge logic (CONT-03)
- [ ] `tests/Feature/VCardTest.php` — vCard import/export with conflict strategies (CONT-06)
- [ ] `tests/Feature/LabelTest.php` — Label CRUD, apply/remove, sidebar, colors, archive (LBL-01 through LBL-05)
- [ ] `composer require laravel/scout sabre/vobject astrotomic/laravel-vcard` — Install new packages
- [ ] `php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"` — Publish Scout config
- [ ] Run migrations for new tables (labels, contacts, groups, message_labels, contact_groups, contact_group_contact)

*If none: "Existing infrastructure covers all phase requirements."*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| Thread expansion animation smoothness | THR-04 | CSS transitions + Alpine.js x-transition; visual fluidity | Open folder with threaded messages; click thread row; verify 150ms ease-out expand; collapse; verify no layout shift |
| Search dropdown keyboard navigation | SRCH-01, SRCH-06 | Arrow key navigation, Enter to open, Escape to close | Focus search input; type query; use Arrow Down/Up to navigate results; press Enter to open message; press Escape to close |
| Label color contrast on white background | LBL-04, UI-SPEC | WCAG 4.5:1 contrast verification | Open label sidebar; verify all 10 palette colors pass WebAIM contrast checker on white (#FFFFFF) |
| Contact avatar initial + color hash | CONT-02, UI-SPEC | Deterministic color-from-email algorithm visual check | Create contacts with different emails; verify avatar shows first letter of name; verify consistent color per email |
| Mobile sidebar tabbed navigation | UI-SPEC D-19 | Responsive breakpoint behavior (<768px) | Resize browser to <768px; verify sidebar tabs become fixed bottom navigation with safe-area-inset; test all 3 tabs |
| Archive action optimistic update + undo | LBL-05 | Toast timing, IMAP move reversal | Select message; press 'e' or click archive; verify message disappears from list; toast appears for 5s; click Undo; verify message returns to Inbox |
| Thread toggle persistence per folder | UI-SPEC D-18 | localStorage key `openmail:threadMode:{folderPath}` | Switch to Inbox; toggle to Flat; navigate to Sent; verify default is Flat; go back to Inbox; verify Threaded persisted |
| Search highlighting after DOMPurify | SRCH-06, SEC-01 | Highlights applied AFTER sanitization | Search for term in HTML message; verify `<mark>` wraps match in snippet; verify no XSS from malicious highlight injection |

*If none: "All phase behaviors have automated verification."*

---

## Validation Sign-Off

- [ ] All tasks have `<automated>` verify or Wave 0 dependencies
- [ ] Sampling continuity: no 3 consecutive tasks without automated verify
- [ ] Wave 0 covers all MISSING references
- [ ] No watch-mode flags
- [ ] Feedback latency < 30s
- [ ] `nyquist_compliant: true` set in frontmatter

**Approval:** pending