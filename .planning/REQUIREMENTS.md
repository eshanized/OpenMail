# Requirements: OpenMail

**Defined:** 2026-09-05
**Core Value:** Provide a secure, modern webmail interface that any organization can deploy on their existing mail infrastructure with zero command-line interaction.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Setup & Installation

- [ ] **SETUP-01**: Graphical setup wizard detects fresh installation and enters install mode
- [ ] **SETUP-02**: System requirements check (PHP version, extensions, permissions, database driver)
- [ ] **SETUP-03**: Database configuration form with connection testing
- [ ] **SETUP-04**: IMAP/SMTP configuration with connection testing and port/encryption validation
- [ ] **SETUP-05**: Automatic mail configuration detection for common domains
- [ ] **SETUP-06**: Application configuration (name, organization, domain, timezone)
- [ ] **SETUP-07**: Administrator account creation with strong password validation
- [ ] **SETUP-08**: Security configuration defaults (HTTPS, secure cookies, session lifetime, rate limiting)
- [ ] **SETUP-09**: Final verification suite (config, database, IMAP, SMTP, filesystem, encryption)
- [ ] **SETUP-10**: Installation lock prevents re-running setup on existing installation
- [ ] **SETUP-11**: Resumable installation state (browser close preserves progress)
- [ ] **SETUP-12**: Shared hosting compatible (no CLI/SSH required for installation)

### Authentication

- [ ] **AUTH-01**: User can log in with email/password via IMAP authentication
- [ ] **AUTH-02**: Secure application session created after successful IMAP auth
- [ ] **AUTH-03**: Login throttling with progressive delays after failed attempts
- [ ] **AUTH-04**: Session rotation after authentication
- [ ] **AUTH-05**: CSRF protection on all forms
- [ ] **AUTH-06**: Secure cookies (HttpOnly, SameSite, Secure flags)
- [ ] **AUTH-07**: Session invalidation on logout
- [ ] **AUTH-08**: TLS enforcement for IMAP/SMTP connections

### Mailbox

- [ ] **MAIL-01**: Folder navigation (Inbox, Sent, Drafts, Trash, Spam, Archive)
- [ ] **MAIL-02**: Custom folder display where supported by mail server
- [ ] **MAIL-03**: Folder metadata caching (unread counts, total messages)
- [ ] **MAIL-04**: Create/rename/delete custom folders

### Message List

- [ ] **MSG-01**: Display sender, recipients, subject, timestamp for each message
- [ ] **MSG-02**: Read/unread visual state
- [ ] **MSG-03**: Star/flag indicator
- [ ] **MSG-04**: Attachment indicator
- [ ] **MSG-05**: Pagination with configurable page size
- [ ] **MSG-06**: Bulk selection (checkbox, shift-click, select all)
- [ ] **MSG-07**: Sort by date, sender, subject, size

### Message Viewer

- [ ] **VIEW-01**: Plain-text rendering
- [ ] **VIEW-02**: Sanitized HTML rendering in sandboxed iframe
- [ ] **VIEW-03**: Message headers/metadata display
- [ ] **VIEW-04**: Attachment list with download
- [ ] **VIEW-05**: Mark as read/unread
- [ ] **VIEW-06**: Star/flag toggle
- [ ] **VIEW-07**: Delete (move to Trash)
- [ ] **VIEW-08**: Move to folder
- [ ] **VIEW-09**: Remote image blocking (block by default, user opt-in)
- [ ] **VIEW-10**: Print-friendly view

### Composer

- [ ] **COMP-01**: To, CC, BCC fields with contact autocomplete
- [ ] **COMP-02**: Subject field
- [ ] **COMP-03**: Plain text body
- [ ] **COMP-04**: HTML body with rich text editor
- [ ] **COMP-05**: Attachment upload with drag-and-drop
- [ ] **COMP-06**: Draft autosave (periodic during composition)
- [ ] **COMP-07**: Email signature insertion
- [ ] **COMP-08**: Send via SMTP
- [ ] **COMP-09**: Cancel/discard draft
- [ ] **COMP-10**: Reply (inline quoted)
- [ ] **COMP-11**: Reply All
- [ ] **COMP-12**: Forward (with attachments)
- [ ] **COMP-13**: Undo send (configurable delay 5-30s)

### Threading

- [ ] **THR-01**: Conversation-style message grouping
- [ ] **THR-02**: Standards-based threading (Message-ID, In-Reply-To, References)
- [ ] **THR-03**: Subject normalization as fallback only
- [ ] **THR-04**: Thread expansion/collapse
- [ ] **THR-05**: Stable rendering across different message sources

### Search

- [ ] **SRCH-01**: Full-text search across subject, sender, recipients, body
- [ ] **SRCH-02**: Search within specific folder
- [ ] **SRCH-03**: Date range filtering
- [ ] **SRCH-04**: Has attachment filter
- [ ] **SRCH-05**: Read/unread filter
- [ ] **SRCH-06**: Search result highlighting
- [ ] **SRCH-07**: Application-level indexing (not IMAP-native only)

### Contacts

- [ ] **CONT-01**: Recent recipients auto-populated
- [ ] **CONT-02**: Contact storage (name, email, phone, notes)
- [ ] **CONT-03**: Address autocomplete in composer
- [ ] **CONT-04**: Contact editing
- [ ] **CONT-05**: Contact groups
- [ ] **CONT-06**: vCard import/export

### Labels & Organization

- [ ] **LBL-01**: Gmail-style labels alongside folders
- [ ] **LBL-02**: Apply/remove labels from messages
- [ ] **LBL-03**: Label sidebar with message counts
- [ ] **LBL-04**: Color-coded labels
- [ ] **LBL-05**: Archive action (move to Archive folder)

### Security

- [ ] **SEC-01**: HTML email sanitization (scripts, event handlers, dangerous URLs, embedded frames)
- [ ] **SEC-02**: CSS injection prevention
- [ ] **SEC-03**: Content Security Policy headers
- [ ] **SEC-04**: Rate limiting on login and API endpoints
- [ ] **SEC-05**: Audit logging (login events, failed attempts, send actions)
- [ ] **SEC-06**: Session hardening (rotation, timeout, fixation prevention)
- [ ] **SEC-07**: SSRF-safe URL handling in message content
- [ ] **SEC-08**: Attachment filename sanitization (UUID-based, path traversal prevention)
- [ ] **SEC-09**: MIME type validation
- [ ] **SEC-10**: Security headers (X-Frame-Options, X-Content-Type-Options, Referrer-Policy)

### Settings

- [ ] **SET-01**: Profile display (name, email)
- [ ] **SET-02**: Signature management (create, edit, delete, set default)
- [ ] **SET-03**: Theme selection (light/dark, respects system preference)
- [ ] **SET-04**: Mail preferences (page size, default folder, reply behavior)
- [ ] **SET-05**: Session/logout controls
- [ ] **SET-06**: Density settings (compact, regular, comfortable)

### Database & Infrastructure

- [ ] **DB-01**: MySQL/MariaDB schema for application state
- [ ] **DB-02**: Database sessions (no Redis dependency)
- [ ] **DB-03**: Database caching for folder metadata
- [ ] **DB-04**: Message metadata storage (folder, UID, Message-ID, flags)
- [ ] **DB-05**: Application settings storage

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Advanced Features

- **ADV-01**: Multi-account support (multiple mailboxes per user)
- **ADV-02**: OAuth2/SSO/LDAP authentication
- **ADV-03**: Sieve filter management (server-side mail rules)
- **ADV-04**: IMAP IDLE for real-time updates
- **ADV-05**: Email scheduling (send later)
- **ADV-06**: Read receipts
- **ADV-07**: Email templates
- **ADV-08**: Import from other clients

### Provider Integrations

- **PRV-01**: Gmail API provider
- **PRV-02**: Microsoft Graph API provider
- **PRV-03**: Custom API provider

### Organizational Features

- **ORG-01**: Admin controls (user management, policies)
- **ORG-02**: Shared inboxes
- **ORG-03**: Multi-tenancy

### Platform Extensions

- **PLT-01**: PWA with offline support
- **PLT-02**: Browser notifications
- **PLT-03**: Native mobile apps
- **PLT-04**: Calendar integration (CalDAV)

## Out of Scope

| Feature | Reason |
|---------|--------|
| Complete mail server | OpenMail is an interface, not Postfix/Dovecot |
| Groupware suite (calendar, tasks) | Different product category, massive scope creep |
| Real-time WebSocket everything | Breaks shared hosting, IMAP IDLE sufficient |
| AI features | Privacy concerns, requires external API keys |
| Redis/OpenSearch dependency | Breaks shared hosting compatibility |
| Collaborative features | Front/HelpScout territory |
| Built-in mail server | Different operational model |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| SETUP-01 | Phase 1 | Pending |
| SETUP-02 | Phase 1 | Pending |
| SETUP-03 | Phase 1 | Pending |
| SETUP-04 | Phase 1 | Pending |
| SETUP-05 | Phase 1 | Pending |
| SETUP-06 | Phase 1 | Pending |
| SETUP-07 | Phase 1 | Pending |
| SETUP-08 | Phase 1 | Pending |
| SETUP-09 | Phase 1 | Pending |
| SETUP-10 | Phase 1 | Pending |
| SETUP-11 | Phase 1 | Pending |
| SETUP-12 | Phase 1 | Pending |
| AUTH-01 | Phase 1 | Pending |
| AUTH-02 | Phase 1 | Pending |
| AUTH-03 | Phase 1 | Pending |
| AUTH-04 | Phase 1 | Pending |
| AUTH-05 | Phase 1 | Pending |
| AUTH-06 | Phase 1 | Pending |
| AUTH-07 | Phase 1 | Pending |
| AUTH-08 | Phase 1 | Pending |
| MAIL-01 | Phase 2 | Pending |
| MAIL-02 | Phase 2 | Pending |
| MAIL-03 | Phase 2 | Pending |
| MAIL-04 | Phase 2 | Pending |
| MSG-01 | Phase 2 | Pending |
| MSG-02 | Phase 2 | Pending |
| MSG-03 | Phase 2 | Pending |
| MSG-04 | Phase 2 | Pending |
| MSG-05 | Phase 2 | Pending |
| MSG-06 | Phase 2 | Pending |
| MSG-07 | Phase 2 | Pending |
| VIEW-01 | Phase 2 | Pending |
| VIEW-02 | Phase 2 | Pending |
| VIEW-03 | Phase 2 | Pending |
| VIEW-04 | Phase 2 | Pending |
| VIEW-05 | Phase 2 | Pending |
| VIEW-06 | Phase 2 | Pending |
| VIEW-07 | Phase 2 | Pending |
| VIEW-08 | Phase 2 | Pending |
| VIEW-09 | Phase 2 | Pending |
| VIEW-10 | Phase 2 | Pending |
| COMP-01 | Phase 3 | Pending |
| COMP-02 | Phase 3 | Pending |
| COMP-03 | Phase 3 | Pending |
| COMP-04 | Phase 3 | Pending |
| COMP-05 | Phase 3 | Pending |
| COMP-06 | Phase 3 | Pending |
| COMP-07 | Phase 3 | Pending |
| COMP-08 | Phase 3 | Pending |
| COMP-09 | Phase 3 | Pending |
| COMP-10 | Phase 3 | Pending |
| COMP-11 | Phase 3 | Pending |
| COMP-12 | Phase 3 | Pending |
| COMP-13 | Phase 3 | Pending |
| THR-01 | Phase 4 | Pending |
| THR-02 | Phase 4 | Pending |
| THR-03 | Phase 4 | Pending |
| THR-04 | Phase 4 | Pending |
| THR-05 | Phase 4 | Pending |
| SRCH-01 | Phase 4 | Pending |
| SRCH-02 | Phase 4 | Pending |
| SRCH-03 | Phase 4 | Pending |
| SRCH-04 | Phase 4 | Pending |
| SRCH-05 | Phase 4 | Pending |
| SRCH-06 | Phase 4 | Pending |
| SRCH-07 | Phase 4 | Pending |
| CONT-01 | Phase 4 | Pending |
| CONT-02 | Phase 4 | Pending |
| CONT-03 | Phase 4 | Pending |
| CONT-04 | Phase 4 | Pending |
| CONT-05 | Phase 4 | Pending |
| CONT-06 | Phase 4 | Pending |
| LBL-01 | Phase 4 | Pending |
| LBL-02 | Phase 4 | Pending |
| LBL-03 | Phase 4 | Pending |
| LBL-04 | Phase 4 | Pending |
| LBL-05 | Phase 4 | Pending |
| SEC-01 | Phase 5 | Pending |
| SEC-02 | Phase 5 | Pending |
| SEC-03 | Phase 5 | Pending |
| SEC-04 | Phase 5 | Pending |
| SEC-05 | Phase 5 | Pending |
| SEC-06 | Phase 5 | Pending |
| SEC-07 | Phase 5 | Pending |
| SEC-08 | Phase 5 | Pending |
| SEC-09 | Phase 5 | Pending |
| SEC-10 | Phase 5 | Pending |
| SET-01 | Phase 5 | Pending |
| SET-02 | Phase 5 | Pending |
| SET-03 | Phase 5 | Pending |
| SET-04 | Phase 5 | Pending |
| SET-05 | Phase 5 | Pending |
| SET-06 | Phase 5 | Pending |
| DB-01 | Phase 1 | Pending |
| DB-02 | Phase 1 | Pending |
| DB-03 | Phase 2 | Pending |
| DB-04 | Phase 2 | Pending |
| DB-05 | Phase 1 | Pending |

**Coverage:**
- v1 requirements: 95 total
- Mapped to phases: 95
- Unmapped: 0 ✓

---
*Requirements defined: 2026-09-05*
*Last updated: 2026-09-05 after roadmap creation*
