# OpenMail

A modern, self-hosted webmail application for organizations. OpenMail connects to existing company mail infrastructure (IMAP/SMTP) through a clean provider abstraction, delivering a Gmail-like experience without requiring a mail server.

Designed for deployment on shared hosting (cPanel) or VPS, it includes a WordPress-style graphical setup wizard so non-technical administrators can deploy it without editing config files or running CLI commands.

## Features

### Core Mail

- **Full IMAP/SMTP integration** — Connects to any IMAP server for reading and SMTP for sending
- **Folder management** — Browse, create, rename, and delete IMAP folders with automatic role detection (Inbox, Sent, Drafts, Trash, Archive)
- **Message threading** — JWZ algorithm builds conversation trees from Message-ID, In-Reply-To, and References headers with subject-based fallback grouping
- **Compose, reply, reply-all, forward** — Rich HTML editor powered by TipTap with attachment support
- **Undo send** — Configurable delay (5-30s) moves sent messages to Drafts before SMTP delivery
- **Auto-save drafts** — Client-side LocalStorage autosave with periodic IMAP sync
- **Attachment management** — MIME-validated file uploads (25MB per file, 50MB total) with a configurable allowlist
- **Contact autocomplete** — Recipient suggestions from recent Sent/Inbox recipients, searchable address book, and contact groups

### Search

- **Full-text search** — MySQL/MariaDB FULLTEXT indexes via Laravel Scout for fast relevance-ranked results
- **Instant search** — Debounced dropdown results for quick access
- **Advanced filters** — Filter by folder, date range, attachment presence, read/unread, flagged, and labels
- **Query sanitization** — Prevents FULLTEXT boolean mode injection attacks

### Organization

- **Labels** — Gmail-style labels with a 10-color palette; apply, remove, and filter messages by label
- **Contact management** — Create, edit, delete contacts with groups and usage-frequency tracking
- **Email signatures** — Multiple signatures per user with default selection, auto-insertion in new messages, and HTML sanitization

### Security

- **HTML email sanitization** — Dual-layer: server-side HTMLPurifier with whitelist-based filtering, plus client-side DOMPurify
- **Remote image blocking** — External images stripped from HTML email; loaded only on user consent
- **URL sanitization** — Blocks `javascript:`, `data:`, `vbscript:`, `file:` schemes and private IP ranges (SSRF prevention)
- **Content Security Policy** — Configurable CSP headers with nonce support, report-only mode, and violation logging
- **Security headers middleware** — X-Content-Type-Options, X-Frame-Options, Referrer-Policy, and more
- **SSRF protection middleware** — Additional layer against server-side request forgery
- **Audit logging** — Tracks login success/failure, account lockouts, and mail send actions with IP and user agent
- **Session management** — View active sessions, revoke other sessions, database-backed encrypted sessions
- **Rate limiting** — Per-route throttling on settings, search, CSP reports, and session revocation endpoints
- **Attachment validation** — MIME type inspection (not just file extension) with configurable allowlist
- **Secure credential handling** — IMAP/SMTP passwords encrypted via Laravel's Crypt, never stored in plaintext

### Setup & Administration

- **8-step Setup Wizard** — Graphical browser-based installer:
  1. Welcome & app naming
  2. System requirements check
  3. Database configuration with connection test and migration runner
  4. Mail server setup with auto-detection for Gmail, Outlook, Yahoo, and generic providers
  5. Application settings (organization, domain, timezone)
  6. Administrator account creation (enforced password complexity)
  7. Security configuration (HTTPS, secure cookies)
  8. Verification suite with fixable error indicators
- **CLI installer** — `php artisan openmail:install` for headless deployment
- **Installation lock** — Prevents re-installation after setup completes
- **No CLI required for end users** — Everything works through the web interface

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Livewire 3 + Blade + Alpine.js + Tailwind CSS 4 |
| Rich Editor | TipTap 3 (with extensions: tables, task lists, links, emoji, images, history) |
| Mail Reading | webklex/laravel-imap 6.x (pure PHP IMAP, no ext-imap required) |
| Mail Sending | Symfony Mailer (via Laravel's mail system) |
| HTML Sanitization | HTMLPurifier 4.x (server) + DOMPurify 3.x (client) |
| Search | Laravel Scout with MySQL FULLTEXT (database driver) |
| CSP | spatie/laravel-csp 3.x |
| Build | Vite 7 + laravel-vite-plugin |
| Testing | Pest 3 / PHPUnit 11 |

## Requirements

- PHP 8.2+ (with extensions: mbstring, xml, ctype, json)
- MySQL 8.0+ / MariaDB 10.6+ (for full-text search)
- Composer 2.x
- Node.js 18+ (for development only)
- Web server (Apache with mod_rewrite, or Nginx)

### Optional

- `ext-mailparse` (PECL) — Recommended for best MIME parsing performance
- `ext-imap` — Only needed for POP3/NNTP legacy protocols (not used by OpenMail)

## Installation

### Web Installer (Recommended)

1. Upload the project files to your web server
2. Point your domain to the `public/` directory
3. Open your browser and navigate to your domain
4. You will be automatically redirected to the Setup Wizard
5. Follow the 8-step wizard to configure your database, mail server, and admin account

### CLI Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan openmail:install
npm install && npm run build
```

### Quick Setup (Development)

```bash
composer setup    # Installs deps, generates key, runs migrations, builds assets
composer dev      # Starts server, queue worker, logs, and Vite concurrently
```

## Configuration

### Environment Variables

Key configuration in `.env`:

```ini
# Database (MySQL recommended for production)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=openmail
DB_USERNAME=root
DB_PASSWORD=

# IMAP (reading mail)
OPENMAIL_IMAP_HOST=imap.gmail.com
OPENMAIL_IMAP_PORT=993
OPENMAIL_IMAP_ENCRYPTION=ssl

# SMTP (sending mail)
OPENMAIL_SMTP_HOST=smtp.gmail.com
OPENMAIL_SMTP_PORT=465
OPENMAIL_SMTP_ENCRYPTION=ssl

# Undo send delay (5-30 seconds)
OPENMAIL_UNDO_SEND_DELAY=10

# Content Security Policy
CSP_ENABLED=false
CSP_REPORT_ONLY=true
```

### Mail Provider Auto-Detection

The setup wizard auto-detects IMAP/SMTP settings for:

- **Gmail** / Google Workspace (`@gmail.com`)
- **Microsoft Outlook** / Office 365 (`@outlook.com`, `@hotmail.com`, `@live.com`)
- **Yahoo Mail** (`@yahoo.com`, `@ymail.com`)
- **Custom** — Falls back to `mail.{domain}` / `smtp.{domain}` with SSL

## Architecture

```
app/
├── Console/Commands/       # Artisan commands (install, process pending sends, prune audit logs)
├── Http/
│   ├── Controllers/        # Auth, search, CSP reports, setup
│   ├── Middleware/          # Install lock, security headers, SSRF protection
│   └── Requests/           # Form request validation
├── Install/                # Setup wizard backend (DB installer, config writer, security config)
├── Livewire/
│   ├── Mailbox/            # Core UI components (composer, message list/viewer, folders, labels, contacts, search)
│   ├── Settings/           # Settings tabs (profile, mail, appearance, security, signatures)
│   ├── LoginForm.php       # Authentication
│   └── SetupWizard.php     # 8-step installation wizard
├── Models/                 # Eloquent models (User, Contact, Label, MessageMetadata, etc.)
├── Services/               # Business logic
│   ├── ImapMailboxService  # IMAP operations (folders, messages, flags, append, trash)
│   ├── ComposerService     # MIME building, SMTP send, undo send, drafts
│   ├── MessageSanitizer    # HTMLPurifier + URL sanitization + remote image blocking
│   ├── SearchService       # Full-text search with Scout + LIKE fallback
│   ├── ThreadBuilder       # JWZ algorithm for conversation threading
│   ├── LabelService        # Label CRUD with 10-color palette
│   ├── ContactService      # Contact & group management
│   ├── SignatureService    # Multi-signature management
│   ├── AuditService        # Security event logging
│   ├── MailConfigDetector  # Auto-detect IMAP/SMTP from email domain
│   ├── FolderMapper        # IMAP folder role/name normalization
│   └── ...                 # Connection testers, VCard, autocomplete cache
├── Support/                # Helpers and utilities
└── Providers/              # Service providers
```

## Scheduled Tasks

```php
// Process queued sends every minute
Schedule::command('pending-sends:process')->everyMinute()->withoutOverlapping(5);

// Prune audit logs older than 90 days daily at 02:00
Schedule::command('audit:prune')->dailyAt('02:00');
```

## Development

### Running Tests

```bash
composer test
```

### Building Assets

```bash
npm run dev     # Development with HMR
npm run build   # Production build
```

### Code Style

```bash
./vendor/bin/pint  # Laravel Pint (PSR-12)
```

## Security

OpenMail adheres to strict security standards including dual HTML sanitization, remote image blocking, CSP nonces, and credential encryption. Please review our [Security Policy](SECURITY.md) for vulnerability reporting guidelines.

## Documentation & Community

- **Non-Technical Setup Guide**: [SETUP.md](SETUP.md)
- **Contribution Guidelines**: [CONTRIBUTING.md](CONTRIBUTING.md)
- **Code of Conduct**: [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md)
- **Changelog**: [CHANGELOG.md](CHANGELOG.md)

## License

OpenMail is open-sourced software licensed under the [MIT license](LICENSE).

