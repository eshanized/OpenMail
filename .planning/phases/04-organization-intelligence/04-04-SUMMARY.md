---
phase: "04"
plan: "04"
subsystem: "labels"
tags: ["labels", "label-service", "message-labeling", "archive-support", "tdd"]
dependency_graph:
  requires:
    - phase: 04-01
      provides: ["labels_table", "message_labels_table", "Label_model", "MessageLabel_pivot"]
    - phase: 04-02
      provides: ["MessageMetadata_Scout", "Label_model_completed"]
  provides:
    - "LabelService with full CRUD"
    - "Message labeling (apply/remove)"
    - "Label counts (unread/total)"
    - "Archive/Inbox label helpers"
    - "10-color palette from UI-SPEC"
  affects: ["Phase 4 Plan 8 (LabelSidebar)", "Phase 4 Plan 6 (MessageList label chips)"]
tech_stack:
  added: []
  patterns:
    - "TDD: tests written first (RED), then implementation (GREEN)"
    - "Service layer with user-scoped operations"
    - "Case-insensitive uniqueness validation at service layer"
    - "Pivot table user_id populated on syncWithoutDetaching"
key_files:
  created:
    - "app/Services/LabelService.php"
    - "tests/Feature/LabelTest.php"
  modified:
    - "app/Models/Label.php"
    - "app/Models/MessageMetadata.php"
decisions:
  - "Label uniqueness validated case-insensitively in service layer (not just DB constraint) for SQLite compatibility"
  - "Pivot table user_id explicitly set during syncWithoutDetaching to satisfy NOT NULL constraint"
  - "Archive label uses Gray (#6B7280), Inbox label uses Blue (#2563EB) per UI-SPEC"
  - "getForUser() uses withCount with conditional query for unread_count"
  - "MessageMetadata scopeWithLabel/scopeWithoutLabel for filter integration"
metrics:
  duration: "1h 15m"
  completed_date: "2026-09-07"
  tasks_completed: 1
  commits: 1
status: complete
actuals:
  tokens: 15000
  tasks: 1
  commits: 1
---

# Phase 04 Plan 04: Label Model + LabelService + Message Labeling + Archive Support Summary

**One-liner:** Implemented LabelService with full CRUD, message labeling, label counts, 10-color palette, and Archive/Inbox helpers using TDD — all 11 tests pass.

## Completed Tasks

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Label model + LabelService CRUD + message labeling + Archive support (TDD) | 46c544e | Label.php, MessageMetadata.php, LabelService.php, LabelTest.php |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Case-insensitive uniqueness validation in service layer**
- **Found during:** TDD RED phase (test `test_create_validates_name_unique_per_user_case_insensitive_and_color_from_palette`)
- **Issue:** SQLite default collation is case-sensitive; DB unique constraint on `['user_id', 'name']` allows 'Work' and 'WORK' as different names
- **Fix:** Added case-insensitive uniqueness check in `validateCreate()` and `validateUpdate()` using `whereRaw('LOWER(name) = ?', [strtolower($name)])`
- **Files modified:** `app/Services/LabelService.php`
- **Commit:** 46c544e

**2. [Rule 1 - Bug] Pivot table user_id NOT NULL constraint on syncWithoutDetaching**
- **Found during:** TDD GREEN phase (test `test_delete_removes_label_from_all_messages_and_deletes_label`)
- **Issue:** `syncWithoutDetaching($messageIds)` doesn't include pivot `user_id`, causing NOT NULL constraint violation on `message_labels.user_id`
- **Fix:** Build explicit sync array with `['user_id' => $label->user_id]` for each message ID
- **Files modified:** `app/Services/LabelService.php`
- **Commit:** 46c544e

**3. [Rule 2 - Missing critical functionality] Added forUser scope to Label model**
- **Found during:** Implementation (referenced in plan action item 1)
- **Issue:** Plan specified `Scope: forUser(userId)` but model didn't have it
- **Fix:** Added `scopeForUser(Builder $query, int $userId)` to Label model
- **Files modified:** `app/Models/Label.php`
- **Commit:** 46c544e

**4. [Rule 2 - Missing critical functionality] Added scopeWithLabel/scopeWithoutLabel to MessageMetadata**
- **Found during:** Implementation (plan action item 3)
- **Issue:** Plan specified query scopes for label filtering
- **Fix:** Added `scopeWithLabel` and `scopeWithoutLabel` using `whereHas`/`whereDoesntHave` on labels relationship
- **Files modified:** `app/Models/MessageMetadata.php`
- **Commit:** 46c544e

## Auth Gates

None encountered.

## Threat Flags

| Flag | File | Description |
|------|------|-------------|
| threat_flag: tampering | app/Services/LabelService.php | All operations scoped to user_id; T-04-16 mitigated |
| threat_flag: dos | app/Services/LabelService.php | Bulk operations limited by message IDs array size; T-04-17 mitigated |
| threat_flag: info_disclosure | app/Services/LabelService.php | getForUser() counts only user's messages; T-04-18 mitigated |

## Self-Check

**Created files verified:**
- FOUND: app/Services/LabelService.php
- FOUND: tests/Feature/LabelTest.php

**Modified files verified:**
- FOUND: app/Models/Label.php (has forUser scope)
- FOUND: app/Models/MessageMetadata.php (has scopeWithLabel/scopeWithoutLabel)

**Commits verified:**
- FOUND: 46c544e

**Tests verified:**
- All 11 tests in LabelTest.php pass

## Self-Check: PASSED