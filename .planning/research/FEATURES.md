# Feature Landscape

**Domain:** Self-hosted company webmail application
**Researched:** 2026-09-05
**Confidence:** HIGH

## Executive Summary

Webmail products fall into three distinct categories: pure webmail clients (Roundcube, SnappyMail), groupware suites (SOGo), and modern JMAP-native clients (Bulwark, Stalwart). OpenMail targets the first category — a pure webmail interface — but with a critical differentiator: **deployment simplicity via graphical setup wizard**. No existing self-hosted webmail offers WordPress-like installation for non-technical administrators. The competitive landscape shows that table stakes are well-established (IMAP/SMTP basics, threading, search, contacts), while differentiators cluster around deployment experience, keyboard productivity, security defaults, and performance architecture.

## Table Stakes (Users Expect These)

Features users assume exist. Missing these = product feels incomplete.

| Feature | Why Expected | Complexity | Notes |
|---------|--------------|------------|-------|
| Read messages (HTML + plain text) | Core email function | LOW | HTML must be sanitized. Sandboxed iframe rendering is industry standard. |
| Compose messages | Core email function | LOW | To/CC/BCC, subject, body (text + HTML), attachments |
| Reply / Reply All / Forward | Every email client has these | LOW | Inline or quoted reply with signature |
| Folder navigation | Inbox, Sent, Drafts, Trash, Spam minimum | LOW | Custom folders expected for organization |
| Message list view | Sender, subject, timestamp, read/unread state | LOW | Pagination or virtual scroll. Attachment indicator. |
| Attachment download | Users must access files sent to them | LOW | MIME decoding, filename sanitization, size validation |
| Attachment upload/compose | Must send files | LOW | File size limits, MIME type validation, drag-and-drop |
| Search | Finding messages is critical | MEDIUM | Basic IMAP search minimum. Application-level indexing expected by power users. |
| Contacts / address book | Autocomplete when composing | MEDIUM | Recent recipients minimum. Full contact storage ideal. |
| Mark read/unread | Basic triage | LOW | Single and bulk operations |
| Delete messages | Basic mailbox management | LOW | Move to Trash first, then purge |
| Move messages between folders | Organization | LOW | Drag-and-drop or menu-based |
| Draft auto-save | Preventing data loss | LOW | Periodic save during compose |
| Login / logout | Authentication | LOW | Email + password, secure session |
| Responsive design | Mobile access expected | MEDIUM | Not a mobile app — responsive web. Desktop-first, mobile-usable. |
| HTTPS support | Security baseline | LOW | TLS for web interface, strict for IMAP/SMTP connections |
| Spell check | Compose quality | LOW | Browser-native or lightweight integration |
| Print messages | Users print emails | LOW | Clean print stylesheet |

## Differentiators (Competitive Advantage)

Features that set the product apart. Not required, but valuable.

| Feature | Value Proposition | Complexity | Notes |
|---------|-------------------|------------|-------|
| **Graphical setup wizard** | **Killer differentiator.** No self-hosted webmail offers WordPress-like installation. This is OpenMail's primary moat. | HIGH | 8-step wizard with auto-detection, installation lock, resumable state. Core to deployment strategy. |
| Message threading (conversation view) | Gmail-like grouping expected by modern users | MEDIUM | Standards-based: Message-ID, In-Reply-To, References. Subject normalization as fallback only. |
| Keyboard shortcuts | Power user productivity (Gmail muscle memory) | LOW | `c` compose, `r` reply, `d` delete, `j/k` navigate, `e` archive. Very high ROI. |
| Sieve filter management | Server-side mail rules (auto-responders, forwarding, organization) | MEDIUM | Visual rule builder + raw Sieve editor. Critical for business use. Roundcube's most valued plugin. |
| Dark/light theme toggle | User preference, accessibility | LOW | Respects `prefers-color-scheme` by default. Manual toggle as override. |
| Labels/tags (Gmail-style) | Organization beyond folders | MEDIUM | Non-destructive labeling. Sidebar with counts. Gmail users expect this. |
| Archive function | Quick inbox zero without deletion | LOW | Single key press. Gmail-style `\Archive` flag. |
| Undo send | Prevent embarrassing mistakes | LOW | Configurable delay (5-30s). Simple but high perceived value. |
| Drag-and-drop organization | Natural interaction for moving messages | LOW | Between folders, onto labels. Touch-friendly fallback. |
| Bulk selection + actions | Efficiency for large mailboxes | LOW | Checkbox + shift-click, select all visible, bulk move/delete/mark |
| Right-click context menu | Desktop-native feel | LOW | Long-press on mobile. Actions like Reply, Forward, Delete, Move, Flag. |
| Toast notifications with undo | Non-blocking feedback | LOW | "Message deleted. [Undo]" pattern. Better than alert dialogs. |
| Read receipts | Business communication tracking | LOW | Request delivery/read confirmation. Respect recipient's choice to decline. |
| Email signatures | Professional identity | LOW | Multiple signatures per account. HTML + plain text. |
| Quota display | Storage awareness | LOW | Show used/total from IMAP QUOTA extension |
| Audit logging (admin) | Security compliance | MEDIUM | Login events, failed attempts, send actions. Essential for company use. |
| Login throttling / rate limiting | Brute force protection | LOW | Progressive delays, account lockout. Security table stakes for company email. |
| Content Security Policy | XSS prevention for email HTML | MEDIUM | Strict CSP headers. Email content is untrusted — must not execute as app HTML. |
| HTML email sanitization | Security (prevent XSS via email) | MEDIUM | DOMPurify or equivalent. Strip scripts, event handlers, dangerous URLs, embedded frames. |
| Remote image blocking | Privacy (prevent tracking pixels) | LOW | Block by default, user opt-in per message. Count of blocked items shown. |
| Multi-account support | Power users with multiple addresses | HIGH | Unified inbox. Account switching without state leakage. Future enhancement. |
| Export contacts (vCard) | Data portability | LOW | vCard import/export. Standard format. |
| Contact groups | Organization | LOW | Group expansion in compose autocomplete |
| Folder management (create/rename/delete) | Custom organization | LOW | Context menu from sidebar. Drag-and-drop reparenting. |
| Density settings | Visual preference | LOW | Extra-compact, compact, regular, comfortable. Gmail offers this. |

## Anti-Features (Commonly Requested, Often Problematic)

Features that seem good but create problems.

| Feature | Why Requested | Why Problematic | Alternative |
|---------|---------------|-----------------|-------------|
| **Full groupware (calendar, contacts, tasks)** | "Me too" vs Google Workspace | Massive scope creep. SOGo tried this — it's a different product category. Calendar requires CalDAV, task management, shared resources, free/busy. Each is its own product. | Ship excellent webmail first. Calendar/contacts sync is a v2+ feature when there's demonstrated demand. |
| **Built-in mail server** | "I want everything in one place" | OpenMail is an interface, not Postfix/Dovecot. Running a mail server requires IP reputation management, deliverability expertise, DNS configuration. Completely different operational model. | Document how to pair with existing mail servers. Never become the mail server. |
| **Real-time everything via WebSocket** | Feels more "modern" | IMAP IDLE is sufficient for new mail notifications. Full WebSocket architecture adds complexity, requires persistent connections, breaks on shared hosting (no long-running workers). | Use IMAP IDLE where supported. Poll as fallback. Push notifications via service worker (future). |
| **AI features in v1** | Trendy, "smart" email | Requires external API keys, adds privacy concerns, complexity budget better spent on core features. Users of self-hosted email care about privacy — AI requires sending email content to external services. | Defer to v2+. If added, make it opt-in with local-only model option. |
| **Native mobile apps** | "Everyone uses phones" | Responsive web covers 80% of mobile use cases. Native apps require maintaining iOS + Android codebases, app store submissions, push notification infrastructure. | Responsive web first. PWA with offline support as future enhancement. |
| **OAuth2/SSO/LDAP in v1** | Enterprise identity integration | Adds significant complexity to authentication model. Requires configuration for each identity provider. Most small companies using shared hosting don't have LDAP. | Architecture-ready but not implemented. Add when there's customer demand. |
| **Multi-tenancy / SaaS model** | "Sell to many companies" | Completely different architecture (data isolation, per-tenant config, billing). Premature for a v1 product targeting self-hosted single-company deployment. | Single deployment = one company. Architecture supports future multi-account within a company. |
| **Redis/OpenSearch dependency** | Performance at scale | Breaks shared hosting compatibility (cPanel). Redis requires persistent daemon. OpenSearch requires JVM. Both add operational complexity. | Use database caching (MySQL/MariaDB). File-based caching for UI preferences. Document Redis as optional performance upgrade. |
| **Collaborative features (shared inboxes, comments, @mentions)** | Team email management | This is Front/HelpScout territory. Requires shared state, permissions, real-time sync. Complex permission model. | Single-user mailbox first. Shared mailbox is v2+ if demand exists. |
| **Email scheduling** | "Send later" | Requires a persistent queue/scheduler. Complex on shared hosting where cron is the only option. | Defer to v2. Can approximate with "draft + reminder" pattern. |
| **Read tracking / analytics** | "Did they open my email?" | Privacy-invasive (tracking pixels). Technically unreliable (many clients block images). Often considered spammy. | Offer as optional feature with clear disclosure to recipients. Never enable by default. |
| **Email templates** | Reusable responses | Often becomes a mini-marketing tool. Over-engineered for v1. | Canned responses (text snippets) is sufficient. Templates are v2+. |
| **Import from other clients** | Migration ease | Complex: requires parsing Outlook .pst, Apple Mail exports, various formats. High effort for low initial payoff. | Support vCard import for contacts. IMAP migration is the mail server's job, not the webmail's. |

## Feature Dependencies

```
Authentication
    └──requires──> Login form, session management, secure credential handling
                       └──requires──> CSRF protection, rate limiting

Message Viewer
    └──requires──> HTML sanitization
                       └──requires──> DOMPurify or equivalent sanitizer
    └──requires──> MIME parsing
                       └──requires──> Attachment extraction

Composer
    └──requires──> SMTP sending
    └──requires──> Draft auto-save
    └──requires──> Signature management

Threading
    └──requires──> Message-ID / In-Reply-To / References parsing
    └──requires──> Message list (must display threads, not individual messages)

Search
    └──enhances──> Message list (search filters the list)
    └──requires──> IMAP search OR application-level indexing

Labels
    └──requires──> Application-level metadata storage
    └──enhances──> Message list (labels shown as tags/badges)
    └──enhances──> Folder navigation (labels in sidebar)

Sieve Filters
    └──requires──> ManageSieve protocol support from IMAP server
    └──enhances──> Mail organization (server-side rules)

Setup Wizard
    └──requires──> Installation lock mechanism
    └──requires──> IMAP/SMTP connection testing
    └──requires──> Database configuration
    └──requires──> Admin account creation

Keyboard Shortcuts
    └──enhances──> Message list (j/k navigation, bulk actions)
    └──enhances──> Message viewer (reply, forward, delete)
    └──enhances──> Composer (send, save draft)

Security Hardening
    └──requires──> HTML sanitization (all email content)
    └──requires──> CSP headers (prevent XSS)
    └──requires──> Session hardening (HttpOnly, Secure, SameSite cookies)
    └──requires──> Rate limiting (login, API)
    └──requires──> Audit logging
```

### Dependency Notes

- **Setup Wizard is foundational:** It's the first thing a user encounters. It must work before any mailbox feature.
- **Threading depends on message parsing:** Message-ID and reference headers must be parsed correctly before threading can group messages.
- **Labels require application-level storage:** Unlike folders (which map to IMAP), labels are application metadata stored in MySQL.
- **Search has two paths:** IMAP-native search (simpler, limited) vs application-level indexing (complex, powerful). v1 can start with IMAP search.
- **Security hardening is pervasive:** HTML sanitization touches message viewing, CSP touches all pages, session hardening touches authentication.

## MVP Definition

### Launch With (v1)

Minimum viable product — what's needed to validate the concept.

- [ ] **Setup wizard (8 steps)** — Core deployment strategy. Without this, OpenMail is just another webmail that requires CLI/config editing. This is the primary differentiator.
- [ ] **Authentication (login/logout/session)** — Must work. Secure by default.
- [ ] **IMAP/SMTP provider abstraction** — Architecture foundation. Clean interface for future providers.
- [ ] **Folder navigation (Inbox, Sent, Drafts, Trash, Spam)** — Minimum mailbox structure.
- [ ] **Message list (sender, subject, timestamp, read/unread, flags)** — Core inbox experience.
- [ ] **Message viewer (HTML + plain text, sanitized)** — Must render email safely.
- [ ] **Compose (To/CC/BCC, subject, body, attachments)** — Must send email.
- [ ] **Reply / Reply All / Forward** — Every email client has these.
- [ ] **Delete / Move messages** — Basic mailbox management.
- [ ] **Draft auto-save** — Prevent data loss.
- [ ] **Basic search** — IMAP-native search minimum.
- [ ] **Contacts (recent recipients, autocomplete)** — Compose UX depends on this.
- [ ] **Security hardening (HTML sanitization, CSP, rate limiting, session hardening)** — Company email access requires production-grade security from day one.

### Add After Validation (v1.x)

Features to add once core is working.

- [ ] **Message threading** — Conversation view expected by modern users. Add when message parsing is stable.
- [ ] **Keyboard shortcuts** — High-ROI productivity feature. Easy to add as enhancement layer.
- [ ] **Labels/tags** — Gmail-style organization. Requires application-level metadata.
- [ ] **Sieve filter management** — Server-side mail rules. Business-critical for power users.
- [ ] **Dark/light theme** — User preference. Low effort, high satisfaction.
- [ ] **Archive function** — Quick inbox zero. Simple flag-based.
- [ ] **Right-click context menu** — Desktop-native feel.
- [ ] **Bulk selection + actions** — Efficiency for large mailboxes.
- [ ] **Toast notifications with undo** — Non-blocking feedback.
- [ ] **Density settings** — Visual preference (compact/comfortable).
- [ ] **Full application-level search/indexing** — When IMAP search proves insufficient.
- [ ] **Admin panel** — User management, system settings, audit logs.
- [ ] **Export/import contacts (vCard)** — Data portability.

### Future Consideration (v2+)

Features to defer until product-market fit is established.

- [ ] **Multi-account support** — Unified inbox across multiple mailboxes. High complexity.
- [ ] **OAuth2/SSO/LDAP** — Enterprise identity integration. Add when there's customer demand.
- [ ] **Calendar integration** — CalDAV support. Different product category (groupware).
- [ ] **PWA / offline support** — Service worker, cached mail. Requires significant architecture.
- [ ] **Multi-tenancy** — SaaS deployment model. Completely different architecture.
- [ ] **Plugin system** — Extensibility framework. premature until core is stable.
- [ ] **Email scheduling** — Requires persistent queue. Complex on shared hosting.
- [ ] **Read receipts / tracking** — Privacy considerations. Optional feature with disclosure.
- [ ] **Collaborative features** — Shared inboxes, @mentions, comments. Team email territory.
- [ ] **AI features** — Summarization, categorization. Privacy concerns for self-hosted.
- [ ] **Localization (i18n)** — Multi-language support. High effort but important for global reach.
- [ ] **Accessibility audit (WCAG AA)** — Screen reader support, focus management, contrast. Important but specialized.

## Feature Prioritization Matrix

| Feature | User Value | Implementation Cost | Priority |
|---------|------------|---------------------|----------|
| Setup wizard | HIGH (deployment strategy) | HIGH | P1 |
| Authentication | HIGH (security) | LOW | P1 |
| IMAP/SMTP abstraction | HIGH (architecture) | MEDIUM | P1 |
| Folder navigation | HIGH (core) | LOW | P1 |
| Message list | HIGH (core) | LOW | P1 |
| Message viewer | HIGH (core) | MEDIUM | P1 |
| Compose | HIGH (core) | LOW | P1 |
| Reply/Reply All/Forward | HIGH (core) | LOW | P1 |
| Delete/Move | HIGH (core) | LOW | P1 |
| Draft auto-save | HIGH (core) | LOW | P1 |
| Basic search | HIGH (core) | MEDIUM | P1 |
| Contacts/autocomplete | HIGH (core) | MEDIUM | P1 |
| Security hardening | HIGH (company email) | MEDIUM | P1 |
| Message threading | HIGH | MEDIUM | P2 |
| Keyboard shortcuts | HIGH | LOW | P2 |
| Labels/tags | MEDIUM | MEDIUM | P2 |
| Sieve filter management | MEDIUM | MEDIUM | P2 |
| Dark/light theme | MEDIUM | LOW | P2 |
| Archive function | MEDIUM | LOW | P2 |
| Right-click context menu | MEDIUM | LOW | P2 |
| Bulk selection + actions | MEDIUM | LOW | P2 |
| Toast notifications | LOW | LOW | P2 |
| Density settings | LOW | LOW | P3 |
| Full search indexing | HIGH | HIGH | P3 |
| Admin panel | HIGH | HIGH | P3 |
| vCard import/export | LOW | LOW | P3 |
| Multi-account | HIGH | HIGH | P3 |
| OAuth2/SSO/LDAP | MEDIUM | HIGH | P3 |
| Calendar | MEDIUM | VERY HIGH | P3 |
| PWA/offline | MEDIUM | VERY HIGH | P3 |

**Priority key:**
- P1: Must have for launch
- P2: Should have, add when core is stable
- P3: Nice to have, future consideration

## Competitor Feature Analysis

| Feature | Roundcube | SnappyMail | SOGo | Bulwark | OpenMail |
|---------|-----------|------------|------|---------|----------|
| Setup wizard | No (config files) | No (config files) | No (CLI) | No | **Yes (8-step graphical)** |
| Threading | Via plugin | Basic | Native | Native | Standards-based |
| Keyboard shortcuts | Plugin | Limited | Limited | Full | Planned |
| Sieve filters | Plugin | Built-in | Built-in | Built-in | Planned |
| PGP encryption | Plugin | Built-in | No | Built-in | Deferred |
| Multi-account | Plugin | Built-in | No | Built-in | Deferred |
| Dark mode | Plugin | Built-in | Limited | Built-in | Planned |
| Admin UI | Config files | Web panel | Config + CLI | Web dashboard | Planned |
| Deploy complexity | Low (PHP) | Low (PHP) | High (daemon) | Medium (Docker) | **Very low (wizard)** |
| RAM usage | 150-300 MB | 50-100 MB | 300-500 MB | ~100 MB | TBD |
| Architecture | Server-rendered | SPA (AJAX) | Daemon | JMAP SPA | Laravel monolith |
| Calendar | Plugin | Basic | Native | Built-in | Deferred |
| Contacts | Plugin | Basic | Native | Built-in | Planned |
| Mobile sync | IMAP only | IMAP only | ActiveSync | JMAP | Responsive web |
| License | GPL v3 | AGPL v3 | GPL v2 | AGPL-3.0 | TBD |

## Sources

- Panelica: SOGo vs Roundcube comparison (2026) — https://panelica.com/blog/sogo-vs-roundcube-2026
- Panelica: Roundcube vs SnappyMail vs SOGo (2026) — https://panelica.com/blog/roundcube-vs-rainloop-vs-sogo-self-hosted-webmail-comparison
- selfhosting.sh: Best Self-Hosted Webmail Clients (2026) — https://selfhosting.sh/best/webmail/
- Bulwark Webmail FEATURES.md — https://github.com/bulwarkmail/webmail/blob/main/FEATURES.md
- Tachyon Webmail feature matrix — https://tachyonmail.app/
- Roundcube keyboard shortcuts plugin — https://github.com/texxasrulez/keyboard_shortcuts
- Roundcube plugins wiki — https://github.com/roundcube/roundcubemail/wiki/Plugins
- Enterprise Webmail Clients Comparison Guide (2026) — https://webmails.live/comparing-webmail-clients-for-enterprise-use-criteria-for-ch
- MailEnable feature matrix — https://www.mailenable.com/features/web-mail.asp
- Nextcloud Mail UX report — https://github.com/nextcloud/mail/issues/12408
- Self-hosted email comparison (Mailcow vs Stalwart vs Mailu) — https://profor.pro/blog/self-hosted-email-2026-mailcow-stalwart-mailu/
- Stalwart + Bulwark analysis — https://devopspack.com/stalwart-bulwark-self-hosted-email-jmap/

---

*Feature research for: OpenMail — self-hosted company webmail*
*Researched: 2026-09-05*
