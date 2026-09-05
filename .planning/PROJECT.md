# OpenMail

## What This Is

OpenMail is a modern, self-hosted webmail application for organizations. It connects to existing company mail infrastructure (IMAP/SMTP) through a clean provider abstraction, delivering a Gmail-like experience without requiring a mail server. Designed for deployment on shared hosting (cPanel) or VPS, it includes a WordPress-style graphical setup wizard so non-technical administrators can deploy it without editing config files or running CLI commands.

## Core Value

Provide a secure, modern webmail interface that any organization can deploy on their existing mail infrastructure with zero command-line interaction.

## Requirements

### Validated

(None yet — ship to validate)

### Active

- [ ] Graphical setup wizard (8-step installer with auto-detection, installation lock, resumable state)
- [ ] IMAP/SMTP mail provider abstraction with clean interface
- [ ] Authentication (email/password login, secure sessions, login throttling)
- [ ] Mailbox navigation (Inbox, Sent, Drafts, Trash, Spam, Archive, custom folders)
- [ ] Message list (sender, recipients, subject, timestamps, read/unread, star/flag, attachment indicator, pagination, bulk selection)
- [ ] Message viewer (plain-text + sanitized HTML rendering, headers, attachments, download, actions)
- [ ] Composer (To/CC/BCC, subject, plain text + HTML, attachments, draft autosave, signatures, send)
- [ ] Reply, Reply All, Forward
- [ ] Message threading (conversation-style grouping using Message-ID, In-Reply-To, References)
- [ ] Full application-level search/indexing
- [ ] Contacts (recent recipients, storage, address autocomplete, editing)
- [ ] Labels and folder organization
- [ ] Full security hardening (HTML sanitization, CSP, rate limiting, audit logging, session hardening, SSRF-safe URL handling)
- [ ] Database-backed application state (MySQL/MariaDB)
- [ ] Caching strategy (folder metadata, UI preferences, short-lived mailbox metadata)

### Out of Scope

- Complete mail server — OpenMail is an interface, not Postfix/Dovecot
- Multi-account support — single account first, architecture ready for future
- OAuth2/SSO/LDAP — future provider integrations
- Calendar, file sharing, groupware — webmail only for v1
- PWA/browser notifications — future enhancement
- Redis/Kafka/OpenSearch — shared hosting compatibility first
- Consumer email provider model — company/internal use only
- Mobile app — responsive web only

## Context

- **Tech stack**: Laravel (PHP), Blade + Livewire + Tailwind CSS + Alpine.js, MySQL/MariaDB
- **Mail access**: IMAP for reading, SMTP for sending — existing infrastructure remains source of truth
- **Architecture**: Laravel monolith with provider abstraction (MailProvider interface)
- **Deployment target**: Shared hosting (cPanel) as first-class, VPS as secondary
- **Security model**: Secure mailbox auth + application sessions, HTML email sanitization, CSP headers
- **Database role**: Application metadata, sessions, preferences, cached indexes — NOT full mailbox storage
- **Threading**: Standards-based (Message-ID, In-Reply-To, References), subject normalization as fallback only
- **Search**: Full application-level indexing (not IMAP-native only)
- **Setup wizard**: WordPress philosophy — Upload → Visit → Configure → Finish

## Constraints

- **Deployment**: Must work on shared hosting (cPanel) with PHP + MySQL — no required CLI/SSH
- **Dependencies**: No Redis, Kafka, or OpenSearch required for v1
- **Architecture**: Laravel monolith — no separate frontend or API unless justified
- **Mail storage**: Existing mail server is source of truth, OpenMail stores application state only
- **Security**: HTML email must never render as trusted application HTML
- **Cred handling**: Never store mailbox passwords in plaintext; use secure application sessions
- **Worker processes**: Core mailbox interaction must not depend on permanently running worker
- **Multi-tenancy**: Single deployment = one company. No premature SaaS multi-tenancy

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Laravel monolith over separate frontend/API | Deployment simplicity, shared hosting compatibility | — Pending |
| IMAP/SMTP provider abstraction | Future provider independence without core rewrite | — Pending |
| Setup wizard in v1 | Core to deployment strategy — non-technical admin target | — Pending |
| Full security hardening in v1 | Company email access requires production-grade security from day one | — Pending |
| Full search indexing in v1 | IMAP-native search insufficient for modern UX expectations | — Pending |
| Threading in v1 | Conversation view is table stakes for modern webmail | — Pending |
| Labels in v1 | Gmail-style organization expected by users | — Pending |
| Single account first | Avoid premature complexity, architecture supports multi-account later | — Pending |
| No Redis dependency | Shared hosting compatibility is higher priority than advanced caching | — Pending |
| Application-level search | IMAP-native search is too limited for the UX we want | — Pending |

## Evolution

This document evolves at phase transitions and milestone boundaries.

**After each phase transition** (via `/gsd-transition`):
1. Requirements invalidated? → Move to Out of Scope with reason
2. Requirements validated? → Move to Validated with phase reference
3. New requirements emerged? → Add to Active
4. Decisions to log? → Add to Key Decisions
5. "What This Is" still accurate? → Update if drifted

**After each milestone** (via `/gsd-complete-milestone`):
1. Full review of all sections
2. Core Value check — still the right priority?
3. Audit Out of Scope — reasons still valid?
4. Update Context with current state

---
*Last updated: 2026-09-05 after initialization*
