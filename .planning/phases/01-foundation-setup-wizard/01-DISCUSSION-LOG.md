# Phase 1: Foundation & Setup Wizard - Discussion Log

> **Audit trail only.** Do not use as input to planning, research, or execution agents.
> Decisions are captured in CONTEXT.md — this log preserves the alternatives considered.

**Date:** 2026-09-05
**Phase:** 1-Foundation & Setup Wizard
**Areas discussed:** Setup wizard flow, IMAP/SMTP testing, Auto-detection logic, Session & auth defaults

---

## Setup wizard flow

| Option | Description | Selected |
|--------|-------------|----------|
| Linear 8-step (Recommended) | 1) Welcome/detect install 2) System requirements 3) Database config 4) Mail config 5) App settings 6) Admin account 7) Security defaults 8) Verify & finish | ✓ |
| Consolidated 6-step | Merge system check into welcome, merge security into app settings | |
| Flexible order | Sidebar nav, save progress, let admin jump between steps | |

**User's choice:** Linear 8-step (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Inline errors (Recommended) | Show error below failing field, let admin fix and retry without losing data | ✓ |
| Modal alert | Pop a modal explaining the error with a retry button | |
| Stop and block | Can't proceed until error is resolved | |

**User's choice:** Inline errors (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Resume from last step (Recommended) | Save progress in DB/session, ask "Continue from step X?" on return | ✓ |
| Auto-resume silently | Jump straight back to where they left off | |
| Start over | Always restart from step 1 | |

**User's choice:** Resume from last step (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Full suite, live status (Recommended) | Test DB, IMAP, SMTP, filesystem, PHP config, encryption with green/red status per check | ✓ |
| Quick check, pass/fail | Just verify DB + mail with single success/failure message | |
| Background pre-check | Run checks as admin fills each step, final step shows summary | |

**User's choice:** Full suite, live status (Recommended)
**Notes:** None

---

## IMAP/SMTP testing

| Option | Description | Selected |
|--------|-------------|----------|
| Full connection test (Recommended) | Connect, authenticate, list folders (IMAP) / verify server accepts auth (SMTP) | ✓ |
| Connection + auth only | Just verify host/port/credentials work | |
| Connection only | Just check if host:port is reachable | |

**User's choice:** Full connection test (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| User-friendly + details (Recommended) | Simple message with expandable technical details | ✓ |
| Technical details only | Show full error from PHP/library | |
| Simple message only | Just "Connection failed" with no details | |

**User's choice:** User-friendly + details (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Retry in place (Recommended) | Keep form filled after failure, let admin fix and hit "Test again" | ✓ |
| Auto-retry once | Automatically retry once after 2-second delay | |
| No retry, fix first | Don't allow retry until admin changes at least one field | |

**User's choice:** Retry in place (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Connection only (Recommended) | Verify SMTP server accepts auth and is reachable, no email sent | ✓ |
| Send test email | Actually send a test email to admin's address | |
| Both options | Let admin choose between "Verify connection" or "Send test email" | |

**User's choice:** Connection only (Recommended)
**Notes:** None

---

## Auto-detection logic

| Option | Description | Selected |
|--------|-------------|----------|
| Big 3 + generic (Recommended) | Gmail, Outlook/365, Yahoo with pre-filled settings; generic fallback | ✓ |
| Big 6 + generic | Add iCloud, ProtonMail (via Bridge), Fastmail | |
| Generic only | Just use mail.domain.com defaults, let admin enter everything | |

**User's choice:** Big 3 + generic (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Pre-fill from domain (Recommended) | Admin enters email, system detects provider from domain, pre-fills settings | ✓ |
| Dropdown selection | Admin picks provider from dropdown, system fills settings | |
| Manual only | No auto-detection, admin enters all details manually | |

**User's choice:** Pre-fill from domain (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Show generic fields (Recommended) | Pre-fill with mail.domain.com / smtp.domain.com defaults | ✓ |
| Blank fields | Show empty fields with placeholder text | |
| Prompt for provider | Ask "Is this Gmail, Outlook, or something else?" then fill | |

**User's choice:** Show generic fields (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| SSL/TLS (Recommended) | Use port 993 (IMAP) / 465 (SMTP) with SSL/TLS | ✓ |
| STARTTLS | Use port 143 (IMAP) / 587 (SMTP) with STARTTLS upgrade | |
| Auto-detect | Try SSL/TLS first, fall back to STARTTLS | |

**User's choice:** SSL/TLS (Recommended)
**Notes:** None

---

## Session & auth defaults

| Option | Description | Selected |
|--------|-------------|----------|
| 24 hours (Recommended) | Standard for webmail, balances security with convenience | ✓ |
| 8 hours | Work-day length, more secure but users re-login if they leave | |
| 7 days | Persistent login, most convenient but higher risk | |

**User's choice:** 24 hours (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Progressive delays (Recommended) | 5s after 3rd fail, 30s after 5th, 5min after 7th, lock for 15min after 10th | ✓ |
| Simple lockout | Lock account for 15 minutes after 5 failed attempts | |
| CAPTCHA only | Show CAPTCHA after 3 failed attempts, no lockout | |

**User's choice:** Progressive delays (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| HttpOnly + SameSite=Lax (Recommended) | HttpOnly prevents XSS, SameSite=Lax prevents CSRF, Secure flag when HTTPS detected | ✓ |
| HttpOnly + SameSite=Strict | Stricter CSRF protection but breaks some cross-site link clicks | |
| HttpOnly only | Just prevent JS access, minimal protection | |

**User's choice:** HttpOnly + SameSite=Lax (Recommended)
**Notes:** None

---

| Option | Description | Selected |
|--------|-------------|----------|
| Strong password + email (Recommended) | Require 8+ chars, mixed case, number. Email must be valid | ✓ |
| Strong password + any email | Same password rules but email doesn't need to match IMAP account | |
| Use configured IMAP email | Admin email MUST match the IMAP mailbox being configured | |

**User's choice:** Strong password + email (Recommended)
**Notes:** None

---

## Agent's Discretion

- Database schema design
- Laravel project structure
- Livewire component organization

## Deferred Ideas

None — discussion stayed within phase scope.
