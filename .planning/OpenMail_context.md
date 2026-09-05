# OpenMail — Project Context

## 1. Project Identity

**Project Name:** OpenMail  
**Project Type:** Self-hosted, company-oriented webmail application  
**Primary Goal:** Provide a modern, secure, deployment-flexible webmail interface that companies can use internally with their existing IMAP/SMTP infrastructure.

OpenMail is not intended to replace a mail server in its initial architecture. It is a webmail application and mail-access layer that connects to existing mail infrastructure through standard protocols such as IMAP and SMTP.

The software should work for a company regardless of its hosting environment, provided the environment can run the application's requirements and make the necessary IMAP/SMTP connections.

Example deployment:

```text
mail.company.com
        │
        ▼
     OpenMail
        │
   ┌────┴────┐
   │         │
 IMAP      SMTP
   │         │
   ▼         ▼
Existing Company Mail Infrastructure
```

---

## 2. Product Vision

OpenMail should become a modern, self-hosted alternative to traditional webmail interfaces such as Roundcube, while remaining easier to deploy and operate than a complete mail-server platform.

The product should prioritize:

- Modern user experience
- Security
- Reliability
- Deployment simplicity
- Provider independence
- Company/internal use
- Strong separation between presentation, application logic, and mail transport
- Compatibility with ordinary hosting environments
- Future extensibility

The first release should be practical and lightweight. Complexity should only be introduced when it provides a measurable benefit.

---

## 3. Target Users

OpenMail is primarily designed for organizations that already operate or purchase email infrastructure.

Typical users include:

- Small businesses
- Startups
- Enterprises
- Educational institutions
- NGOs
- Internal company teams
- Organizations using cPanel-hosted mail
- Organizations using self-hosted Dovecot/Postfix/Exim infrastructure
- Organizations using any standards-compatible IMAP/SMTP provider

OpenMail is designed for internal/company use, not specifically for personal consumer mail.

---

## 4. Core Principle

OpenMail does **not** initially own the mailbox storage layer.

The company's existing mail server remains the source of truth for email data.

For example:

```text
OpenMail
   │
   ├── IMAP → imap.company.com
   │
   └── SMTP → smtp.company.com
```

OpenMail may store application-level metadata, preferences, sessions, cached indexes, and other information required for its own operation, but it should not unnecessarily duplicate every mailbox and attachment into its own database.

This reduces infrastructure requirements, storage duplication, migration complexity, and operational risk.

---

## 5. Initial Technology Stack

### Backend

- Laravel
- Modern supported PHP version
- MySQL/MariaDB
- Laravel session/authentication infrastructure
- Laravel queues/scheduler where supported

### Frontend

- Blade
- Livewire
- Tailwind CSS
- Alpine.js
- TypeScript where client-side behavior benefits from it

The initial architecture should remain a Laravel monolith.

Do not introduce a separate Next.js frontend or Rust API unless a future requirement demonstrates a clear need.

---

## 6. Deployment Targets

OpenMail must be designed with deployment flexibility in mind.

### Primary target

Shared hosting / cPanel environments.

The system should be deployable using:

- File Manager
- FTP
- Git
- Composer, where available
- MySQL
- PHP configuration
- Cron/Scheduled Tasks

### Secondary target

VPS or dedicated infrastructure.

A VPS deployment should require little or no application-level redesign.

### Domain example

```text
mail.company.com
```

The company should be able to deploy OpenMail under its own branded domain or subdomain.

---

## 7. High-Level Architecture

```text
                        Browser
                           │
                         HTTPS
                           │
                           ▼
                ┌─────────────────────┐
                │      OpenMail       │
                │      Laravel        │
                ├─────────────────────┤
                │ Authentication      │
                │ Session Management  │
                │ Mailbox Management  │
                │ Message Service     │
                │ Threading           │
                │ Search              │
                │ Composer            │
                │ Attachments         │
                │ Contacts            │
                │ Settings            │
                │ Security Layer      │
                └──────────┬──────────┘
                           │
                ┌──────────┴──────────┐
                │                     │
               IMAP                  SMTP
                │                     │
                ▼                     ▼
        Company Mail Server / Provider
```

---

## 8. Mail Provider Abstraction

The UI and domain/application layers must not be tightly coupled to raw IMAP or SMTP implementation details.

Use an abstraction similar to:

```php
interface MailProvider
{
    public function folders(): Collection;

    public function messages(
        string $folder,
        int $page,
        int $perPage,
    ): MessagePage;

    public function message(
        string $folder,
        string $uid,
    ): Message;

    public function send(OutgoingMessage $message): void;

    public function delete(
        string $folder,
        string $uid,
    ): void;

    public function markRead(
        string $folder,
        string $uid,
    ): void;
}
```

The initial implementation can be:

```text
MailProvider
└── ImapSmtpProvider
    ├── IMAP connection
    └── SMTP transport
```

The architecture must leave room for future providers without requiring a rewrite of the UI or business logic.

Potential future providers:

```text
MailProvider
├── ImapSmtpProvider
├── GmailProvider
├── MicrosoftProvider
└── CustomProvider
```

This provider abstraction is architectural, not a requirement to implement all providers in v1.

---

## 9. Authentication Model

OpenMail must carefully handle mailbox authentication.

The application should avoid permanently storing users' mailbox passwords in plaintext.

Preferred flow:

```text
User
 │
 │ email + password
 ▼
OpenMail
 │
 │ authenticate
 ▼
IMAP server
 │
 │ success
 ▼
OpenMail session
```

After successful authentication, OpenMail should use a secure application session rather than repeatedly exposing credentials to the browser.

Important controls:

- Secure cookies
- HttpOnly cookies
- SameSite protection
- Session rotation after authentication
- CSRF protection
- Login throttling/rate limiting
- Account lockout or progressive delays where appropriate
- Strict TLS for mail connections
- Secure session invalidation on logout

The exact credential strategy must be finalized before implementation of authentication.

---

## 10. Security Requirements

Security is a core product requirement because OpenMail provides access to potentially sensitive company email.

### Email HTML

Email content must never be rendered as trusted application HTML.

OpenMail must sanitize HTML email before presentation.

Protection must cover:

- JavaScript execution
- event-handler attributes
- dangerous URLs
- embedded frames
- malicious SVG/content
- DOM-based attacks
- HTML injection
- CSS-based abuse where relevant

### Remote resources

External images and resources must be handled cautiously because they can create tracking and privacy risks.

### Attachments

Validate:

- File size
- MIME type
- File extension
- Filename
- Path traversal
- Dangerous file types
- Storage boundaries

Attachments must never be able to escape the application's intended storage area.

### URLs

URLs inside messages should be handled safely.

Any future link-preview/proxy mechanism must include SSRF protections.

### General web security

Use standard Laravel security protections plus:

- Content Security Policy where practical
- Strict transport security
- Secure headers
- Input validation
- Output escaping
- Authorization checks
- Rate limiting
- Audit logging for security-sensitive events

---

## 11. Database Responsibility

The database is for OpenMail's application state and metadata.

Potential tables:

```text
users
sessions
mail_accounts
mail_folders
messages
message_threads
message_flags
attachments
contacts
contact_addresses
signatures
settings
audit_logs
```

Not every table must be implemented in v1.

Message-related records should reference the upstream mailbox using stable identifiers such as:

- Mailbox/folder
- IMAP UID
- Message-ID header
- Thread identifiers where available

The system must account for the fact that IMAP UIDs can be mailbox-specific and can change when mailbox state is rebuilt.

---

## 12. Initial Feature Set

### Authentication

- Login
- Logout
- Session management
- Login throttling
- Secure credential handling

### Mailbox

- Inbox
- Sent
- Drafts
- Trash
- Spam/Junk
- Archive
- Custom folders where supported

### Message list

- Sender
- Recipients
- Subject
- Timestamp
- Read/unread state
- Star/flag
- Attachment indicator
- Pagination
- Bulk selection

### Message viewer

- Plain-text rendering
- Sanitized HTML rendering
- Headers/metadata
- Attachments
- Download attachments
- Mark as read
- Star/flag
- Delete
- Move
- Reply
- Reply all
- Forward

### Composer

- To
- CC
- BCC
- Subject
- Plain text
- HTML
- Attachments
- Draft autosave
- Signature
- Send
- Cancel/discard

### Search

Initial search can use IMAP-native search functionality.

Application-level indexing should not be introduced until there is a demonstrated performance requirement.

### Contacts

- Recent recipients
- Contact storage
- Address autocomplete
- Contact editing

### Settings

- Profile
- Signature
- Theme
- Mail preferences
- Session/logout controls

---

## 13. UX Direction

OpenMail should feel like a modern productivity application rather than a legacy webmail client.

Design influence:

- Modern enterprise applications
- Gmail-like familiarity
- Proton-like security-oriented feel
- Linear-like clarity
- Minimal visual clutter

Core UI characteristics:

- Responsive
- Fast
- Keyboard-friendly
- Accessible
- Desktop optimized
- Mobile usable
- Clear hierarchy
- Minimal unnecessary navigation
- Consistent interaction patterns

Suggested desktop structure:

```text
┌──────────────────────────────────────────────────────────────┐
│ OpenMail                 Search              Notifications ⚙ │
├──────────────┬─────────────────────────────┬─────────────────┤
│              │                             │                 │
│ + Compose    │ Inbox                       │ Message         │
│              │                             │                 │
│ Inbox        │ Message list                │ Message body    │
│ Starred      │                             │                 │
│ Sent         │                             │                 │
│ Drafts       │                             │                 │
│ Archive      │                             │                 │
│ Spam         │                             │                 │
│ Trash        │                             │                 │
│              │                             │                 │
│ Labels       │                             │                 │
└──────────────┴─────────────────────────────┴─────────────────┘
```

The UI should adapt rather than simply shrink the desktop layout on mobile.

---

## 14. Threading

OpenMail should support conversation-style message grouping.

Threading should use standards-based signals where available, including:

- Message-ID
- In-Reply-To
- References
- Subject normalization as a fallback only

Threading must not assume that subject equality alone means messages belong to the same conversation.

Thread rendering must remain stable even when messages are received from different clients.

---

## 15. Caching and Performance

Do not cache sensitive information indiscriminately.

Potential cache candidates:

- Folder metadata
- Mailbox counts
- UI preferences
- Short-lived mailbox metadata
- Search/index metadata

The application must distinguish between:

```text
Authoritative mail state
```

and:

```text
Application cache
```

IMAP remains the source of truth unless OpenMail explicitly documents otherwise.

For the first release, avoid requiring Redis. Shared hosting compatibility is more important.

---

## 16. Queue/Scheduler Strategy

The system should work without requiring a long-running worker process.

Where supported, Laravel queues and scheduled tasks can be used for:

- Mail indexing
- Cache cleanup
- Temporary-file cleanup
- Deferred processing
- Maintenance

However, core mailbox interaction must not depend on a permanently running worker.

cPanel cron should be treated as an optional execution mechanism for scheduled operations.

---

## 17. Multi-Company / Internal Deployment Model

OpenMail should be designed so that a single deployment normally represents one company or organization.

Example:

```text
mail.acme.com      → OpenMail → Acme mail infrastructure
mail.example.org   → OpenMail → Example mail infrastructure
```

Do not prematurely introduce SaaS-style multi-tenancy.

However, internal domain/account configuration should be abstracted enough that the application can later support:

- Multiple company domains
- Multiple mail servers
- Multiple accounts
- Administrative policies

without a core redesign.

---

## 18. Multi-Account Support

A future-compatible internal architecture should allow one authenticated user to work with multiple mail accounts.

Example:

```text
Accounts
├── alice@company.com
├── support@company.com
└── operations@company.com
```

Account switching must not leak state between mailboxes.

Each account should have isolated:

- Sessions/credentials
- Mail provider connection context
- Folder state
- Draft context
- Settings where applicable

Multi-account support may be implemented after the core single-account experience is stable.

---

## 19. Project Structure

Recommended Laravel organization:

```text
app/
├── Auth/
├── Http/
├── Mail/
│   ├── Contracts/
│   │   ├── MailProvider.php
│   │   └── MailTransport.php
│   │
│   ├── Imap/
│   │   ├── ImapConnection.php
│   │   ├── ImapMailbox.php
│   │   └── ImapMessage.php
│   │
│   ├── Smtp/
│   │   └── SmtpTransport.php
│   │
│   ├── Mime/
│   │   ├── MimeParser.php
│   │   └── MimePart.php
│   │
│   └── Services/
│       ├── MailReader.php
│       ├── MailSender.php
│       ├── MailSearch.php
│       └── AttachmentService.php
│
├── Security/
├── Models/
├── Livewire/
└── Support/

resources/
├── views/
├── css/
└── js/

routes/
├── web.php
└── console.php

database/
├── migrations/
└── seeders/

storage/
```

The exact structure may evolve with implementation.

---

## 20. Development Phases

### Phase 1 — Foundation

- Laravel project
- Environment/configuration
- Database
- Authentication foundation
- Mail provider abstraction
- IMAP connection
- SMTP connection
- Basic health checks

### Phase 2 — Mailbox

- Folder navigation
- Inbox
- Message list
- Message viewer
- Read/unread
- Flags
- Move/delete

### Phase 3 — Composer

- New message
- Reply
- Reply all
- Forward
- Attachments
- Drafts
- Autosave
- Signatures

### Phase 4 — Organization

- Threading
- Search
- Contacts
- Labels
- Archive
- Advanced mailbox actions

### Phase 5 — Security Hardening

- HTML sanitization
- MIME validation
- Attachment security
- CSP
- Security headers
- Session hardening
- Rate limiting
- Audit events
- SSRF-safe URL handling

### Phase 6 — UX and Performance

- Mobile experience
- Keyboard shortcuts
- Optimistic UI where safe
- Progressive loading
- Better pagination
- Caching
- Accessibility
- Performance profiling

### Phase 7 — Advanced Capabilities

Potential features:

- Multi-account
- Multiple providers
- Admin controls
- SSO
- LDAP
- OAuth2
- 2FA
- PWA
- Browser notifications
- Optional indexing/search engine
- Organization policies

Advanced capabilities must not compromise the simple deployment model.

---

## 21. Non-Goals for v1

OpenMail v1 should not attempt to become:

- A complete mail server
- A replacement for Postfix/Exim/Dovecot
- A full groupware suite
- A calendar platform
- A file-sharing platform
- A consumer email provider
- A mandatory Redis/Kafka/OpenSearch deployment
- A cloud SaaS platform

The initial goal is excellent webmail.

---

## 22. Deployment Model

Example cPanel deployment:

```text
/home/ACCOUNT/
└── openmail/
    ├── app/
    ├── bootstrap/
    ├── config/
    ├── database/
    ├── public/
    ├── resources/
    ├── routes/
    ├── storage/
    └── vendor/
```

Recommended domain configuration:

```text
mail.company.com
        ↓
OpenMail /public
```

The public web root must expose only Laravel's `public/` directory.

Sensitive application files must remain outside the web root.

---

## 23. Example Company Configuration

```env
APP_NAME=OpenMail
APP_ENV=production
APP_URL=https://mail.company.com

DB_CONNECTION=mysql
DB_DATABASE=openmail
DB_USERNAME=openmail_user

MAIL_IMAP_HOST=imap.company.com
MAIL_IMAP_PORT=993
MAIL_IMAP_ENCRYPTION=ssl

MAIL_SMTP_HOST=smtp.company.com
MAIL_SMTP_PORT=465
MAIL_SMTP_ENCRYPTION=ssl
```

Credential values must be supplied securely through environment/configuration mechanisms and must never be committed to source control.

---

## 24. Engineering Standards

Code should follow production-grade engineering practices.

Required principles:

- Strong typing
- Clear service boundaries
- Dependency inversion
- Small cohesive classes
- Explicit validation
- Defensive error handling
- Secure defaults
- Testable business logic
- No duplicated protocol logic
- No credentials in source control
- No unnecessary abstraction for its own sake

Avoid:

- Fat controllers
- Raw IMAP logic inside Livewire components
- Direct SQL scattered across UI code
- Trusting user-provided MIME types
- Rendering raw email HTML
- Storing secrets unnecessarily
- Introducing infrastructure dependencies without justification

---

## 25. Testing Strategy

The project should include:

### Unit tests

For:

- Mail parsing
- Threading
- Message normalization
- Validation
- Security utilities
- Provider abstractions

### Feature tests

For:

- Authentication
- Mailbox operations
- Search
- Composer
- Drafts
- Attachments
- Authorization

### Security tests

For:

- XSS through email HTML
- Malicious attachments
- Path traversal
- CSRF
- Session fixation
- SSRF vectors
- Authentication brute force
- Unauthorized mailbox access

### Integration tests

Where safe and practical:

- IMAP connectivity
- SMTP sending
- Provider behavior
- TLS validation

Real external mail systems should not be required for the entire test suite.

---

## 26. Error Handling Philosophy

Mail servers fail in real-world ways.

OpenMail must distinguish:

```text
Authentication failure
Connection failure
TLS failure
Timeout
Mailbox unavailable
Invalid message
Attachment failure
SMTP rejection
Temporary server failure
Permanent server failure
```

User-visible errors should be understandable and non-sensitive.

Internal logs may contain diagnostic details, but must not expose passwords, session secrets, or sensitive authentication material.

---

## 27. Observability

The initial product should provide useful logs without requiring a complete observability stack.

Capture:

- Authentication events
- Login failures
- Mail send failures
- IMAP connection errors
- Unexpected exceptions
- Security events
- Attachment processing failures
- Administrative actions

Logs must avoid storing:

- Mailbox passwords
- Session tokens
- Authentication secrets
- Full email contents unless explicitly required for a controlled debugging mode

---

## 28. Product Philosophy

OpenMail should follow this rule:

> Make sophisticated mail functionality feel simple, while keeping the underlying architecture disciplined.

The user should not need to understand IMAP, SMTP, MIME, mail UIDs, MIME boundaries, or transport details to use OpenMail.

Those details belong behind clear application abstractions.

---

## 29. Long-Term Direction

The long-term vision is for OpenMail to become a polished, self-hosted webmail platform that organizations can deploy on their own infrastructure.

Potential future positioning:

```text
OpenMail
├── Self-hosted
├── Open-source
├── Company-focused
├── Provider-independent
├── Secure by default
├── Lightweight deployment
└── Modern UX
```

A mature installation could eventually support:

```text
                    OpenMail
                       │
       ┌───────────────┼────────────────┐
       │               │                │
     IMAP            OAuth             LDAP
       │               │                │
       ▼               ▼                ▼
 Company Mail     External Mail     Company Identity
```

The architecture should be prepared for this future, but v1 must remain focused.

---

## 30. Current Decision Summary

**Project:** OpenMail  
**Audience:** Companies / internal organizational use  
**Deployment:** Self-hosted, with shared hosting/cPanel as a first-class target  
**Backend:** Laravel  
**UI:** Blade + Livewire + Tailwind + Alpine.js  
**Database:** MySQL/MariaDB  
**Mail access:** IMAP + SMTP  
**Mailbox source of truth:** Existing mail server  
**Architecture:** Laravel monolith with provider abstractions  
**Authentication:** Secure mailbox authentication + application sessions  
**Initial priority:** Excellent, secure, modern webmail experience  
**Future priority:** Provider independence, multi-account support, organizational features

The guiding constraint is:

```text
Do not build a mail server.
Build the best practical interface and application layer
for existing company email infrastructure.
```

---

## 31. Installation & Setup Wizard

OpenMail must include a **first-class graphical setup wizard**, inspired by the simplicity of WordPress installation.

The goal is that a person with a domain and ordinary hosting can deploy OpenMail without needing to manually edit `.env` files, run complex CLI commands, or understand Laravel internals.

### Installation Experience

A fresh deployment should behave like:

```text
Upload OpenMail
      │
      ▼
Visit https://mail.example.com
      │
      ▼
┌─────────────────────────────────────────┐
│              OpenMail Setup             │
│                                         │
│  Welcome to OpenMail                    │
│  Let's configure your mail server.      │
│                                         │
│              [Get Started]              │
└─────────────────────────────────────────┘
      │
      ▼
System Requirements
      │
      ▼
Application / Database
      │
      ▼
Mail Server
      │
      ▼
Administrator
      │
      ▼
Security
      │
      ▼
Verification
      │
      ▼
Installation Complete
```

The wizard should be usable by a non-developer.

---

## 32. Setup Wizard Requirements

The installer should automatically detect whether OpenMail has already been configured.

A fresh deployment should enter installation mode when the application is not initialized.

Once installation is complete:

```text
/setup
```

must no longer be accessible, or must require a securely stored installation lock and administrator authorization.

The installer must never allow an attacker to re-run setup against an existing production installation.

---

## 33. Installation Step 1 — Welcome

Display:

```text
Welcome to OpenMail

A modern, self-hosted webmail platform for organizations.

[Get Started]
```

Provide links to:

- Documentation
- License
- Privacy/security documentation

Keep the first screen extremely simple.

---

## 34. Installation Step 2 — System Requirements

Automatically check the environment.

Examples:

```text
PHP Version                  ✓
Required PHP Extensions     ✓
Writable storage directory  ✓
Writable cache directory    ✓
Writable session directory  ✓
Database driver             ✓
OpenSSL                     ✓
Mbstring                    ✓
XML                         ✓
cURL                        ✓
Fileinfo                    ✓
PDO                         ✓
```

Where relevant, also check:

```text
Maximum upload size
Maximum POST size
Memory limit
Execution time
HTTPS
```

Display clear remediation instructions when a requirement fails.

Example:

```text
PHP 8.3 or newer is required.

Current version: PHP 8.1

Please select a newer PHP version in your hosting control panel.
```

The installer should distinguish:

```text
Required
Recommended
Optional
```

rather than treating every capability as fatal.

---

## 35. Installation Step 3 — Database Configuration

Provide a graphical database form:

```text
Database Driver
[ MySQL ▼ ]

Database Host
[ localhost              ]

Database Name
[ openmail               ]

Database Username
[ openmail_user          ]

Database Password
[ ********************  ]

Database Port
[ 3306                   ]

[ Test Connection ]
```

The wizard should test the connection before allowing the user to continue.

After a successful connection:

```text
✓ Database connection successful
✓ Database is writable
✓ Schema can be created
```

The installer may automatically:

- Create required tables
- Run migrations
- Seed only essential system data

It must never silently delete an existing production database.

---

## 36. Installation Step 4 — Mail Server Configuration

This is the most important OpenMail-specific part of the wizard.

### IMAP

```text
IMAP Host
[ imap.company.com ]

Port
[ 993 ]

Encryption
[ SSL/TLS ▼ ]

Validate TLS certificate
[ ✓ ]

Username format
[ Full email address ▼ ]
```

### SMTP

```text
SMTP Host
[ smtp.company.com ]

Port
[ 465 ]

Encryption
[ SSL/TLS ▼ ]

Authentication
[ Username + Password ▼ ]
```

Provide:

```text
[Test IMAP Connection]
[Test SMTP Connection]
```

The installer should clearly report:

```text
✓ IMAP connection successful
✓ Authentication successful
✓ Inbox accessible
✓ SMTP connection successful
✓ SMTP authentication successful
```

The test must not send an email unless the user explicitly chooses a "Send test email" action.

---

## 37. Automatic Mail Configuration Detection

Where possible, OpenMail should make setup easier by detecting common configurations.

For example, given:

```text
company.com
```

the installer can offer:

```text
Detected possible mail configuration:

IMAP: imap.company.com:993
SMTP: smtp.company.com:465

[Use Detected Settings]
```

Detection must be treated as a suggestion, not trusted configuration.

The user must be able to edit everything manually.

Do not make assumptions about DNS or mail infrastructure that cannot be verified.

---

## 38. Installation Step 5 — Application Configuration

Ask for:

```text
Application Name
[ OpenMail ]

Company / Organization Name
[ Example Company ]

Primary Domain
[ company.com ]

Application URL
[ https://mail.company.com ]

Default Timezone
[ Asia/Kolkata ▼ ]

Default Locale
[ English ▼ ]
```

These should become application-level settings rather than hard-coded values.

---

## 39. Installation Step 6 — Administrator Account

Create the first OpenMail administrator:

```text
Name
[ Eshan ]

Email
[ admin@company.com ]

Password
[ **************** ]
[ **************** ]

[ Create Administrator ]
```

Password requirements should be strong but practical.

The installer should validate:

- Minimum length
- Common-password resistance
- Confirmation
- Strength

The administrator password is an **OpenMail application credential**.

It must not be confused with the mailbox password unless the administrator intentionally uses the same account for both purposes.

---

## 40. Installation Step 7 — Security Configuration

Provide security-related defaults:

```text
Enforce HTTPS
[ ✓ ]

Secure Cookies
[ ✓ ]

Session Lifetime
[ 120 minutes ]

Login Rate Limiting
[ ✓ ]

Security Headers
[ ✓ ]

Content Security Policy
[ ✓ ]
```

Recommended values should be preselected.

Advanced settings can be hidden under:

```text
Advanced Security Settings
```

Do not overwhelm first-time users with security internals.

---

## 41. Installation Step 8 — Final Verification

Before completing installation, run an automated validation suite:

```text
Application configuration       ✓
Database connection             ✓
Database migrations             ✓
IMAP connection                 ✓
SMTP connection                 ✓
Filesystem permissions          ✓
Session storage                 ✓
Encryption configuration        ✓
HTTPS                           ✓
Application key                 ✓
```

If something fails, explain exactly what needs to be fixed.

Example:

```text
SMTP connection failed

Host:
smtp.company.com

Port:
465

Reason:
Connection timed out

Suggested actions:
• Verify the hostname
• Verify the port
• Check whether outbound SMTP is blocked by your host
• Try port 587 with STARTTLS
```

---

## 42. Installation Completion

Show:

```text
OpenMail is ready!

Your webmail has been configured successfully.

Webmail:
https://mail.company.com

Administrator:
admin@company.com

[Open OpenMail]
```

Also provide:

```text
Installation ID
Version
Configuration status
```

Do not display sensitive secrets.

---

## 43. Shared Hosting Compatibility

The installer must specifically account for cPanel/shared-hosting environments.

The setup process should work without requiring SSH where reasonably possible.

Preferred deployment flow:

```text
1. Upload OpenMail
2. Create MySQL database
3. Point domain/subdomain at /public
4. Visit domain
5. Complete graphical wizard
6. Done
```

Where Composer/CLI access is unavailable, the official release package should contain production dependencies so the user does not have to run Composer manually.

A separate developer installation may still use:

```text
composer install
npm install
npm run build
```

but normal end-user installation should not require these commands.

---

## 44. Installer Architecture

The installer should be implemented as a dedicated subsystem rather than scattered conditionals throughout the application.

Suggested organization:

```text
app/
└── Install/
    ├── Installer.php
    ├── InstallationState.php
    ├── RequirementChecker.php
    ├── DatabaseInstaller.php
    ├── MailConfigurationTester.php
    ├── ConfigurationWriter.php
    ├── SecurityConfigurator.php
    └── InstallationLock.php

resources/
└── views/
    └── install/
        ├── layout.blade.php
        ├── welcome.blade.php
        ├── requirements.blade.php
        ├── database.blade.php
        ├── mail.blade.php
        ├── application.blade.php
        ├── administrator.blade.php
        ├── security.blade.php
        ├── verify.blade.php
        └── complete.blade.php
```

Livewire may be used to make the wizard interactive without introducing a separate frontend application.

---

## 45. Installation State

The installer must be resumable.

If a user completes:

```text
Requirements
Database
Mail Configuration
```

and closes the browser, returning to the application should allow them to continue rather than forcing a complete restart.

Installation state must be stored securely.

Do not store plaintext database or mail passwords in arbitrary browser-visible state.

Sensitive credentials must be handled only through protected server-side mechanisms.

---

## 46. Installation Lock

After successful installation, create an immutable installation marker.

For example:

```text
storage/
└── framework/
    └── installed
```

or another secure server-side mechanism appropriate to the deployment.

Every setup request should verify the installation state before permitting setup operations.

The installer must fail closed.

---

## 47. Configuration Strategy

The installer should generate or configure the application's runtime configuration without exposing secrets publicly.

Where `.env` writing is supported:

```text
.env
```

may be generated or updated safely.

Where environment-file modification is unavailable, OpenMail should provide a supported configuration mechanism that works with the hosting environment.

The installer must validate file permissions after writing configuration.

Never expose:

- Database passwords
- Mailbox passwords
- Application encryption keys
- Session secrets

in HTML, client-side JavaScript, logs, or exception pages.

---

## 48. Upgrade Experience

The installation subsystem should later support upgrades.

A future update flow may provide:

```text
OpenMail Update Available

Current version: 1.3.0
Available version: 1.4.0

[Backup]
[Migrate Database]
[Update]
```

Upgrades must be separate from initial installation.

Never automatically run destructive migrations without an explicit, recoverable upgrade strategy.

---

## 49. Backup Safety

Before migrations or major configuration changes, OpenMail should be able to detect whether a backup exists.

For shared hosting, this may simply provide guidance:

```text
Before continuing, ensure your hosting backup system is enabled.

[I've confirmed my backup]
```

A future version may integrate application-level backup/export functionality.

OpenMail should never claim a backup exists when it has not actually verified one.

---

## 50. Installer UX Principle

The installer should follow the **WordPress philosophy**:

> Upload → Visit → Configure → Finish.

The target experience is:

```text
Technical user:
    Can use advanced settings.

Non-technical company administrator:
    Can complete installation without reading the source code.

Hosting provider:
    Can deploy OpenMail on ordinary PHP hosting.

Developer:
    Can still configure everything through environment variables and CLI tooling.
```

The graphical wizard is therefore not a convenience feature; it is a core part of OpenMail's deployment strategy.

---

## 51. Expanded Product Positioning

With the setup wizard, OpenMail's deployment proposition becomes:

```text
Any compatible server
        +
PHP/MySQL
        +
IMAP/SMTP mail infrastructure
        │
        ▼
   Upload OpenMail
        │
        ▼
Visit your domain
        │
        ▼
Graphical Setup Wizard
        │
        ▼
      OpenMail
```

This makes OpenMail suitable for internal company deployment without requiring the organization to hire a specialist to configure the application manually.

