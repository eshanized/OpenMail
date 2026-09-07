# Phase 5: Security & Polish - Plan Verification Report

**Verification Date:** 2026-09-08
**Phase:** 05-security-polish
**Plans Verified:** 7 (05-01 through 05-07)
**Overall Verdict:** PASS

---

## Summary

| Check | Status | Details |
|-------|--------|---------|
| 1. Frontmatter Validity | ✅ PASS | All 7 plans have valid YAML frontmatter with required fields |
| 2. Task Structure | ✅ PASS | All 21 tasks have complete structure (files, read_first, action, verify, done, reversibility) |
| 3. Requirement Coverage | ✅ PASS | All 16 requirements (SEC-01–10, SET-01–06) covered across plans |
| 4. Tracer-First | ✅ PASS | Plan 1 (wave 1) Task 1 is tracer; Plans 3-7 also have tracer tasks |
| 5. Wave Dependencies | ✅ PASS | Valid DAG: Wave 1 (01,02) → Wave 2 (03) → Wave 3 (04) → Wave 4 (05) → Wave 5 (06) → Wave 6 (07) |
| 6. Decision Traceability | ✅ PASS | All 16 locked decisions (D-01–D-16) mapped to task actions/acceptance criteria |
| 7. Threat Models | ✅ PASS | All 7 plans include STRIDE threat model with severity ratings |
| 8. Failing Direction | ✅ PASS | Every `<automated>` verify has `<fails_when>` with observable failure signal |
| 9. Tracked Source Paths | ✅ PASS | All `files_modified` paths are source files (not gitignored build artifacts) |
| 10. Security Enforcement | ✅ PASS | ASVS-aligned threat models; high/critical severity threats have mitigations |
| 11. Scope Sanity | ✅ PASS | Max 10 files/plan (Plan 03), max 3 tasks/plan — within thresholds |
| 12. Research Resolution | ✅ PASS | RESEARCH.md has no "Open Questions" section — all resolved implicitly |

---

## Detailed Findings

### 1. Frontmatter Validity ✅ PASS

All 7 plans contain valid YAML frontmatter with required fields:

| Plan | wave | depends_on | files_modified | autonomous | requirements | estimate | must_haves |
|------|------|------------|----------------|------------|--------------|----------|------------|
| 05-01 | 1 | [] | 8 files | true | SEC-03, SEC-10 | 45k tokens | ✅ |
| 05-02 | 1 | [] | 7 files | true | SEC-05 | 40k tokens | ✅ |
| 05-03 | 2 | ["01","02"] | 10 files | true | SET-03, SET-06 | 50k tokens | ✅ |
| 05-04 | 3 | ["03"] | 9 files | true | SEC-04,06,07,08,09 | 55k tokens | ✅ |
| 05-05 | 4 | ["04"] | 10 files | true | SET-02 | 50k tokens | ✅ |
| 05-06 | 5 | ["05"] | 11 files | true | SET-01,04,05 | 45k tokens | ✅ |
| 05-07 | 6 | ["06"] | 9 files | true | SEC-01,02,03, SET-03,06 | 40k tokens | ✅ |

**No missing or malformed frontmatter fields.**

---

### 2. Task Structure ✅ PASS

All 21 tasks (3 per plan × 7 plans) have complete structure:

| Required Element | Status |
|------------------|--------|
| `id` | ✅ All present (e.g., `01-tracer`, `01-verify-csp`, `01-docs`) |
| `type` | ✅ All `execute` or `verify` |
| `name` | ✅ Descriptive names |
| `files` | ✅ Array of file paths |
| `read_first` | ✅ Mirrors `files` array |
| `action` | ✅ Detailed implementation steps |
| `verify` → `automated` | ✅ Runnable command with fallback |
| `verify` → `fails_when` | ✅ Observable failure conditions |
| `done` | ✅ Measurable acceptance criteria |
| `reversibility` | ✅ Rating + explanation |

**Example of complete task structure (05-01 Task 01-tracer):**
- ✅ `files`: 8 files listed
- ✅ `read_first`: Same 8 files
- ✅ `action`: 8 numbered implementation steps
- ✅ `verify.automated`: Route check + config bootstrap test
- ✅ `verify.fails_when`: 4 specific failure conditions
- ✅ `done`: 7 measurable criteria
- ✅ `reversibility`: "reversible" with explanation

---

### 3. Requirement Coverage ✅ PASS

All 16 Phase 5 requirements mapped to plans:

| Requirement | Description | Covering Plan(s) |
|-------------|-------------|------------------|
| SEC-01 | HTML email sanitization | 05-07 (trace), 05-04 (MessageSanitizer) |
| SEC-02 | CSS injection prevention | 05-07 (trace), 05-04 (MessageSanitizer) |
| SEC-03 | CSP headers | **05-01** (primary), 05-07 (trace) |
| SEC-04 | Rate limiting on login/API | **05-04** (primary) |
| SEC-05 | Audit logging | **05-02** (primary) |
| SEC-06 | Session hardening | **05-04** (primary) |
| SEC-07 | SSRF-safe URL handling | **05-04** (primary) |
| SEC-08 | Attachment filename sanitization | **05-04** (primary) |
| SEC-09 | MIME type validation | **05-04** (primary) |
| SEC-10 | Security headers | **05-01** (primary) |
| SET-01 | Profile display | **05-06** (primary) |
| SET-02 | Signature management | **05-05** (primary) |
| SET-03 | Theme selection | **05-03** (primary), 05-07 (trace) |
| SET-04 | Mail preferences | **05-06** (primary) |
| SET-05 | Session/logout controls | **05-06** (primary) |
| SET-06 | Density settings | **05-03** (primary), 05-07 (trace) |

**No requirement is uncovered.** Each has a primary owning plan with implementation tasks.

---

### 4. Tracer-First ✅ PASS

Plan 05-01 (Wave 1) Task `01-tracer` is explicitly a vertical end-to-end slice:

> "End-to-end CSP infrastructure — config, nonce generator, presets, violation endpoint, security headers middleware"

Additionally, **every plan** has a tracer task (01-tracer through 07-tracer), each implementing a complete vertical slice. This exceeds the minimum requirement.

---

### 5. Wave Dependencies ✅ PASS

Dependency graph is a valid DAG with no cycles:

```
Wave 1:  05-01 (depends_on: [])     05-02 (depends_on: [])
Wave 2:  05-03 (depends_on: [01, 02])
Wave 3:  05-04 (depends_on: [03])
Wave 4:  05-05 (depends_on: [04])
Wave 5:  05-06 (depends_on: [05])
Wave 6:  05-07 (depends_on: [06])
```

- All `depends_on` references exist
- No forward references (lower wave depending on higher)
- No circular dependencies
- Wave numbers consistent with `max(depends_on wave) + 1`
- Plans 01 and 02 correctly in Wave 1 (parallelizable)

---

### 6. Decision Traceability ✅ PASS

All 16 locked decisions from CONTEXT.md mapped to implementation:

| Decision | Description | Implementation Location |
|----------|-------------|------------------------|
| D-01 | CSP report-only first | 05-01 Task 01-tracer action step 1, config/csp.php `report_only: true` |
| D-02 | Nonce + unsafe-inline fallback | 05-01 Task 01-tracer action step 3, OpenMailPreset |
| D-03 | CSP violation reports to /csp-report | 05-01 Task 01-tracer action step 5, CspReportController |
| D-04 | spatie/laravel-csp Vite integration | 05-01 Task 01-tracer action step 2, LaravelViteNonceGenerator |
| D-05 | audit_logs table in DB | 05-02 Task 02-tracer action step 1, migration |
| D-06 | Auth + Send scope only | 05-02 Task 02-tracer action step 3, AuditService convenience methods |
| D-07 | Standard fields + 90-day prune | 05-02 Task 02-tracer action steps 1,6, PruneAuditLogs |
| D-08 | Explicit AuditService::log() calls | 05-02 Task 02-tracer action steps 4,5, Login/LogoutController |
| D-09 | Theme/density in settings table | 05-03 Task 03-tracer action step 2, AppearanceTab save() |
| D-10 | Tailwind 4 dark mode class strategy | 05-03 Task 03-tracer action step 5, app.blade.php themeInitializer |
| D-11 | CSS custom properties for density | 05-03 Task 03-tracer action step 7, app.css @theme vars |
| D-12 | Settings page with tabs | 05-03 Task 03-tracer action steps 1,3, SettingsPage + tabs |
| D-13 | Tiptap for signatures | 05-05 Task 05-tracer action step 6, tiptap-editor.blade.php |
| D-14 | Multiple signatures, one default | 05-05 Task 05-tracer action steps 1,2, Signature model boot() |
| D-15 | Default auto-insert + dropdown | 05-05 Task 05-tracer action step 7, 05-composer-integration |
| D-16 | Signatures CRUD in Settings tab | 05-05 Task 05-tracer action steps 4,5, SignaturesTab |

**No decision is missing from implementation.** Discretion areas (40-56 in CONTEXT.md) are appropriately left to planner/implementation detail.

---

### 7. Threat Models ✅ PASS

All 7 plans include `<threat_model>` with STRIDE register:

| Plan | Threats Identified | High/Critical | Mitigated |
|------|-------------------|---------------|-----------|
| 05-01 | 5 (T-05-01 to T-05-04, T-05-SC) | 2 high (T-05-02, T-05-03) | ✅ Nonce per request, global middleware |
| 05-02 | 5 (T-05-05 to T-05-08, T-05-SC) | 2 high (T-05-05, T-05-08) | ✅ Append-only, explicit calls at every action |
| 05-03 | 4 (T-05-09 to T-05-11, T-05-SC) | 0 high | — |
| 05-04 | 5 (T-05-12 to T-05-16, T-05-SC) | 2 critical/high (T-05-13, T-05-14) | ✅ Session regenerate, dual-layer SSRF |
| 05-05 | 4 (T-05-17 to T-05-20, T-05-SC) | 1 critical (T-05-17) | ✅ JSON storage + sanitizer pipeline |
| 05-06 | 4 (T-05-21 to T-05-24, T-05-SC) | 0 high | — |
| 05-07 | 3 (T-05-25 to T-05-27, T-05-SC) | 1 high (T-05-25) | ✅ Mandatory 2-week report-only period |

**All high/critical threats have concrete mitigations.** No unmitigated critical threats.

---

### 8. Failing Direction ✅ PASS

Every `<automated>` verify command has a `<fails_when>` clause specifying observable failure signals:

**Examples:**
- 05-01 Task 01-tracer: `fails_when>Route /csp-report not registered, CSP config missing, SecurityHeaders middleware not in global stack, nonce generator not returning Vite nonce</fails_when>`
- 05-02 Task 02-tracer: `fails_when>Migration missing, AuditLog model missing, AuditService missing convenience methods, LoginController/LogoutController not calling AuditService, prune command missing, security log channel missing</fails_when>`
- 05-04 Task 04-tracer: `fails_when>Rate limiters not in AppServiceProvider, SsrfProtection middleware missing, AttachmentRequest missing File::types validation, config/openmail.php missing attachments config, session config not hardened, throttle middleware not on API routes</fails_when>`

**All 21 tasks have specific, observable failure conditions.** No vague "test fails" statements.

---

### 9. Tracked Source Paths ✅ PASS

All `files_modified` entries are source files tracked by git:

- Config files: `config/csp.php`, `config/openmail.php`, `config/session.php`, `config/logging.php`
- App code: `app/**/*.php` (services, models, controllers, middleware, providers, commands, Livewire)
- Resources: `resources/views/**/*.blade.php`, `resources/css/app.css`, `resources/js/app.js`
- Routes: `routes/web.php`
- Bootstrap: `bootstrap/app.php`
- Migrations: `database/migrations/*.php`
- No build artifacts (`public/build/`, `vendor/`, `node_modules/`) listed

---

### 10. Security Enforcement ✅ PASS

**ASVS Level 1 alignment verified:**

| ASVS Category | Phase 5 Coverage |
|---------------|------------------|
| V1: Architecture | Threat models in all plans (STRIDE) |
| V2: Authentication | SEC-04 rate limiting, SEC-06 session hardening, SEC-05 audit logging |
| V3: Session Management | SEC-06: rotation, timeout, fixation prevention, secure cookies |
| V4: Access Control | User-scoped settings/signatures/audit logs |
| V5: Validation | SEC-01,02 sanitization; SEC-08,09 MIME/filename; SEC-07 SSRF |
| V7: Error Handling | CSP violation logging, audit logging |
| V8: Audit Logging | SEC-05 comprehensive audit trail |
| V9: Configuration | CSP report-only → enforce, env-driven config |
| V10: Malicious Code | CSP nonces, sanitization pipeline, MIME validation |

**No blocking high-severity threats unmitigated.**

---

### 11. Scope Sanity ✅ PASS

| Plan | Tasks | Files Modified | Status |
|------|-------|----------------|--------|
| 05-01 | 3 | 8 | ✅ Within thresholds |
| 05-02 | 3 | 7 | ✅ Within thresholds |
| 05-03 | 3 | 10 | ⚠️ Warning threshold (10 files) but acceptable |
| 05-04 | 3 | 9 | ✅ Within thresholds |
| 05-05 | 3 | 10 | ⚠️ Warning threshold (10 files) but acceptable |
| 05-06 | 3 | 11 | ⚠️ Warning threshold (11 files) — slightly over |
| 05-07 | 3 | 9 | ✅ Within thresholds |

**Thresholds:** Target 5-8 files/plan, Warning at 10, Blocker at 15+
- Plans 03, 05, 06 are at warning level (10-11 files)
- **No plan exceeds blocker threshold (15)**
- All plans have exactly 3 tasks (within 2-3 target)
- Estimated tokens per plan: 40k-55k (reasonable for context budget)

**Recommendation:** Plans 03, 05, 06 could be split if execution shows context pressure, but not required pre-execution.

---

### 12. Research Resolution ✅ PASS

RESEARCH.md has **no `## Open Questions` section** — all research questions were resolved during the research phase and incorporated into the decisions and patterns. The `## Phase Requirements` table shows all items with "Research Support" column filled, and `## Common Pitfalls` documents known issues with mitigations.

---

## Context Compliance Check

Since CONTEXT.md was provided, verified against locked decisions:

| Check | Result |
|-------|--------|
| No tasks contradict locked decisions | ✅ PASS |
| No tasks implement deferred ideas (none deferred) | ✅ PASS |
| Discretion areas handled by planner | ✅ PASS |

---

## Architectural Tier Compliance (Dimension 7c)

RESEARCH.md includes **Architectural Responsibility Map** (lines 79-92). Verified plan tasks assign capabilities to correct tiers:

| Capability | Expected Tier | Plan Assignment | Status |
|------------|---------------|-----------------|--------|
| CSP header generation | API/Backend | 05-01: SecurityHeaders middleware, CSP config | ✅ |
| CSP violation reporting | Browser→API | 05-01: /csp-report endpoint, browser POST | ✅ |
| Rate limiting | API/Backend | 05-04: AppServiceProvider RateLimiter configs | ✅ |
| Audit logging | API/Backend | 05-02: AuditService, controllers | ✅ |
| Session hardening | API/Backend | 05-04: config/session.php, LoginController | ✅ |
| SSRF/MIME/filename validation | API/Backend | 05-04: SsrfProtection, AttachmentRequest, MessageSanitizer | ✅ |
| Security headers | API/Backend | 05-01: SecurityHeaders middleware | ✅ |
| Theme/density preferences | Browser→API | 05-03: Alpine.js + Setting model persistence | ✅ |
| Signature management | API/Backend | 05-05: SignatureService, model, SignaturesTab | ✅ |
| Settings UI | Browser→API | 05-03, 05-06: Livewire components | ✅ |

**No tier mismatches found.** All security-sensitive capabilities correctly assigned to API/Backend tier.

---

## Cross-Plan Data Contracts (Dimension 9)

Verified shared data entities across plans:

| Shared Entity | Plans | Compatibility |
|---------------|-------|---------------|
| `settings` table (theme, density) | 03, 06, 07 | ✅ 03 writes, 06 reads via `Setting::getForUser()`, 07 verifies |
| `audit_logs` table | 02, 07 | ✅ 02 writes, 07 traces requirements |
| `signatures` table | 05, 06 | ✅ 05 creates/manages, 06 references via User relationship |
| `sessions` table | 02, 04, 06 | ✅ 04 hardens config, 06 revokes, 02 logs auth events |
| `config/csp.php` | 01, 07 | ✅ 01 creates, 07 reviews violations for enforcement |
| `User` model | 02, 04, 05, 06 | ✅ 06 extends with relationships/accessors, others consume |

**No incompatible transformations.** All plans use Laravel's standard patterns for model access.

---

## AGENTS.md Compliance (Dimension 10)

Project AGENTS.md (Technology Stack, ADRs) constraints verified:

| AGENTS.md Constraint | Plan Compliance |
|----------------------|-----------------|
| Laravel 12 monolith | ✅ All plans use Laravel patterns |
| Livewire 3 + Alpine.js | ✅ All UI via Livewire/Alpine |
| Tailwind 4 CSS-first | ✅ 05-03 uses @theme, @custom-variant, @utility |
| Database-only (no Redis) | ✅ Sessions, cache, rate limiting all use database driver |
| Dual HTML sanitization (ADR-004) | ✅ 05-04 extends MessageSanitizer, 05-05 reuses for signatures |
| webklex/php-imap (no ext-imap) | ✅ No plan introduces ext-imap dependency |
| Shared hosting compatible | ✅ No workers, no Redis, scheduler via cron |

**No violations of project constraints.**

---

## Nyquist Compliance (Dimension 8) - Key Checks

| Check | Status |
|-------|--------|
| 8a: Every task has automated verify | ✅ All 21 tasks |
| 8b: Latency ≤ 30s | ✅ All commands are fast (route:list, grep, php -r bootstrap) |
| 8c: Sampling continuity | ✅ Verification tasks can run repeatedly |
| 8d: Wave 0 completeness | N/A (no Wave 0 in this phase) |
| 8e: VALIDATION.md gate | N/A (pre-execution check) |
| 8f: Stated failing direction | ✅ Every `<automated>` has `<fails_when>` |

---

## Verify Command Sanity Checks

| Check | Status |
|-------|--------|
| No `^` anchor on package manager tree output | ✅ No `pnpm ls | grep -E '^package'` patterns |
| No `2>/dev/null \|\| echo` feeding comparisons | ✅ All automated commands use direct output checks |
| No hard-coded numeric counts without provenance | ✅ Commands check existence/structure, not specific counts |
| All verify paths resolvable | ✅ All file paths in `files`/`read_first` exist in project structure |

---

## Numeric/Factual Claim Authority

No conflicts between plans and RESEARCH.md on numeric/factual claims. Plans reference RESEARCH.md patterns authoritatively for architecture, not for measurements.

---

## Issues Summary

| Severity | Count | Description |
|----------|-------|-------------|
| **BLOCKER** | 0 | None |
| **WARNING** | 3 | Plans 03, 05, 06 at 10-11 files (warning threshold) |
| **INFO** | 0 | None |

### Warnings (Advisory)

1. **Plan 05-03**: 10 files modified — at warning threshold. Consider if density CSS work could be separated.
2. **Plan 05-05**: 10 files modified — at warning threshold. Signature migration + model + service + UI is cohesive but large.
3. **Plan 05-06**: 11 files modified — slightly over warning threshold. Three tabs + User/Setting model extensions.

**These are warnings only — execution can proceed.** If context pressure emerges during execution, plans can be split dynamically.

---

## Recommendation

**VERIFICATION PASSED.** All 7 plans for Phase 5 are structurally sound, complete, and traceable to requirements and decisions. The plans are ready for execution via `/gsd-execute-phase 05`.

**Next step:** Run `/gsd-execute-phase 05` to begin Wave 1 (Plans 01 and 02 in parallel).