---
phase: "04"
plan: "01"
subsystem: "database-threading"
tags: ["migrations", "threading", "jwz-algorithm", "database-schema"]
dependency_graph:
  requires: []
  provides: ["labels", "message_labels", "contacts", "contact_groups", "contact_group_contact", "thread_header_cache", "scout_columns"]
  affects: ["Phase 4 Plan 2 (LabelService)", "Phase 4 Plan 3 (ContactService)", "Phase 4 Plan 4 (SearchService)", "Phase 1 Task 2 (ThreadBuilder)"]
tech_stack:
  added:
    - "ThreadHeaderCache model"
    - "ThreadBuilder service (JWZ algorithm)"
  patterns:
    - "Database migrations with user-scoped tables"
    - "Thread header caching for cross-folder thread reconstruction"
    - "FULLTEXT index for Scout database engine"
key_files:
  created:
    - "database/migrations/2026_09_06_000001_create_labels_table.php"
    - "database/migrations/2026_09_06_000002_create_message_labels_table.php"
    - "database/migrations/2026_09_06_000003_create_contacts_table.php"
    - "database/migrations/2026_09_06_000004_create_contact_groups_table.php"
    - "database/migrations/2026_09_06_000005_create_contact_group_contact_table.php"
    - "database/migrations/2026_09_06_000006_add_scout_columns_to_message_metadata.php"
    - "database/migrations/2026_09_06_000007_create_thread_header_cache_table.php"
    - "app/Models/ThreadHeaderCache.php"
    - "app/Services/ThreadBuilder.php"
    - "tests/Unit/Services/ThreadBuilderTest.php"
  modified: []
decisions:
  - "Used JWZ algorithm for threading (standard used by Gmail, Thunderbird, mutt)"
  - "Added thread_header_cache table with 24-hour TTL for cross-folder thread root headers"
  - "FULLTEXT index on message_metadata for Scout database engine search"
  - "Message-ID cleaning strips angle brackets per RFC 5322"
  - "Subject normalization strips Re:/Fwd: prefixes case-insensitively"
  - "2-day window for subject-based thread grouping fallback"
metrics:
  duration: "1h 43m"
  completed_date: "2026-09-06"
  tasks_completed: 2
  commits: 2
status: complete
actuals:
  tokens: 74000
  tasks: 2
  commits: 2
---

# Phase 04 Plan 01: Database Migrations + ThreadBuilder Service Summary

**One-liner:** Created all 7 Phase 4 database migrations (labels, contacts, groups, pivots, Scout columns, thread_header_cache) and implemented ThreadBuilder service with JWZ threading algorithm and thread header cache integration.

## Completed Tasks

| Task | Name | Commit | Files |
|------|------|--------|-------|
| 1 | Database migrations for Phase 4 core tables + thread header cache | a03ff40 | 7 migration files |
| 2 | ThreadBuilder service with JWZ algorithm + thread header cache integration (TDD) | 3156faf | ThreadBuilder.php, ThreadHeaderCache.php, ThreadBuilderTest.php |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed foreign key reference in message_labels migration**
- **Found during:** Migration run (SQLite)
- **Issue:** `constrained()` defaulted to `message_metadatas` table but actual table is `message_metadata`
- **Fix:** Explicitly specified `constrained('message_metadata')` in migration
- **Files modified:** `database/migrations/2026_09_06_000002_create_message_labels_table.php`
- **Commit:** a03ff40

**2. [Rule 1 - Bug] FULLTEXT index creation for SQLite compatibility**
- **Found during:** Migration run (SQLite doesn't support FULLTEXT)
- **Issue:** `DB::statement` for FULLTEXT fails on SQLite
- **Fix:** Made FULLTEXT index creation conditional on MySQL driver only
- **Files modified:** `database/migrations/2026_09_06_000006_add_scout_columns_to_message_metadata.php`
- **Commit:** a03ff40

**3. [Rule 1 - Bug] Circular reference handling in ThreadBuilder**
- **Found during:** TDD RED phase (test `handles_circular_references_gracefully_no_infinite_loop`)
- **Issue:** Initial cycle detection (per-edge) didn't catch cycles formed by mutual references
- **Fix:** Implemented two-pass approach: build parent map first, then DFS with three-color marking to detect all cycles, then break them
- **Files modified:** `app/Services/ThreadBuilder.php`
- **Commit:** 3156faf

**4. [Rule 1 - Bug] Subject normalization regex didn't handle leading whitespace**
- **Found during:** TDD RED phase (test `groupBySubject_strips_re_fwd_normalizes_whitespace_groups_by_subject_and_2day_window`)
- **Issue:** Regex `/^(Re|Fwd):\s*/i` failed to match "Re:" when subject had leading spaces
- **Fix:** Added `trim()` before regex replacement
- **Files modified:** `app/Services/ThreadBuilder.php`
- **Commit:** 3156faf

**5. [Rule 1 - Bug] foreach iteration over inCycle array used values instead of keys**
- **Found during:** TDD GREEN phase debugging
- **Issue:** `foreach ($inCycle as $messageId)` iterated over values (all `1`) instead of keys (message IDs)
- **Fix:** Changed to `foreach (array_keys($inCycle) as $messageId)`
- **Files modified:** `app/Services/ThreadBuilder.php`
- **Commit:** 3156faf

**6. [Rule 2 - Missing critical functionality] Added ThreadHeaderCache model**
- **Found during:** ThreadBuilder implementation (referenced in `resolveMissingParentsFromCache`)
- **Issue:** Plan didn't explicitly mention creating the Eloquent model for thread_header_cache table
- **Fix:** Created `app/Models/ThreadHeaderCache.php` with proper casts and relationships
- **Files created:** `app/Models/ThreadHeaderCache.php`
- **Commit:** 3156faf

## Auth Gates

None encountered.

## Threat Flags

| Flag | File | Description |
|------|------|-------------|
| threat_flag: tampering | app/Services/ThreadBuilder.php | Message-ID headers from IMAP server; ThreadBuilder validates format via cleanMessageId() |
| threat_flag: dos | app/Services/ThreadBuilder.php | MAX_DEPTH constant (50) guards against deep recursion in attachChildren() |
| threat_flag: info_disclosure | app/Models/ThreadHeaderCache.php | User-scoped queries via user_id foreign key prevent cross-user leakage |

## Self-Check

**Created files verified:**
- FOUND: database/migrations/2026_09_06_000001_create_labels_table.php
- FOUND: database/migrations/2026_09_06_000002_create_message_labels_table.php
- FOUND: database/migrations/2026_09_06_000003_create_contacts_table.php
- FOUND: database/migrations/2026_09_06_000004_create_contact_groups_table.php
- FOUND: database/migrations/2026_09_06_000005_create_contact_group_contact_table.php
- FOUND: database/migrations/2026_09_06_000006_add_scout_columns_to_message_metadata.php
- FOUND: database/migrations/2026_09_06_000007_create_thread_header_cache_table.php
- FOUND: app/Models/ThreadHeaderCache.php
- FOUND: app/Services/ThreadBuilder.php
- FOUND: tests/Unit/Services/ThreadBuilderTest.php

**Commits verified:**
- FOUND: a03ff40
- FOUND: 3156faf

## Self-Check: PASSED
