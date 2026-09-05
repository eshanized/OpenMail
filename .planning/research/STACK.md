# Stack Research: OpenMail Webmail Application

**Domain:** Self-hosted webmail application (Laravel monolith)
**Researched:** 2026-09-05
**Confidence:** HIGH (primary stack), MEDIUM (specialized libraries)

## Recommended Stack

### Core Framework

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| PHP | 8.3+ | Runtime | Laravel 12 minimum requirement; 8.3 is current LTS-quality with typed constants, readonly classes, and performance improvements. Laravel 13 also supports 8.3-8.5. |
| Laravel | 12.x | Backend framework | Latest stable with long support window (security until Feb 2027). Laravel 12 introduced Livewire starter kits and streamlined structure. Avoids 13.x which is too new (released Mar 2026, AI-focused features not needed here). |
| MySQL/MariaDB | 8.0+ / 10.6+ | Database | Required for shared hosting compatibility. MySQL full-text search powers application-level search without external services. MariaDB is the default on cPanel. |
| Composer | 2.x | Dependency management | Standard PHP dependency manager; required for installation. |

**Confidence: HIGH** — Laravel 12 with PHP 8.3 is the production-proven choice. Laravel 13 is too new (4 months old) with AI features irrelevant to this project. Laravel 11 security support ended March 2026.

### Frontend Stack

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Livewire | 3.x (3.8.x) | Dynamic UI components | Built-in Alpine.js, SPA-like navigation via `wire:navigate`, reactive props, file uploads, lazy loading. Ships with Alpine — no separate CDN needed. Livewire 4.x exists but 3.x is the mature, documented choice for production. |
| Blade | (Laravel built-in) | Templating | Native Laravel templating; zero additional dependencies. Component-based architecture pairs perfectly with Livewire. |
| Tailwind CSS | 4.x (4.3.x) | Utility-first CSS | Latest stable v4 with CSS-first configuration, no tailwind.config.js needed. Laravel 12 ships with Tailwind v4 integration via Vite. |
| Alpine.js | 3.x (3.17.x) | Lightweight JS interactivity | Included automatically by Livewire 3. Used for small interactive behaviors (dropdowns, modals, toggles) without full JS framework overhead. |
| Vite | 6.x | Asset bundling | Laravel's default build tool. Handles CSS/JS compilation, HMR in development, optimized builds for production. |

**Confidence: HIGH** — This is the officially recommended Laravel frontend stack. Livewire 3 + Alpine + Tailwind is the most productive combination for server-rendered dynamic UIs.

### Mail Protocol Libraries

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| webklex/php-imap | 6.2.x | IMAP client | Most mature Laravel IMAP library. Pure PHP IMAP implementation (no PHP IMAP extension required). Supports IMAP IDLE, OAuth, folder management, message parsing. 709 GitHub stars, active maintenance. Laravel wrapper available via `webklex/laravel-imap` 6.x. |
| Symfony Mailer | (via Laravel) | SMTP sending | Laravel's built-in mail system uses Symfony Mailer. Configure `MAIL_MAILER=smtp` with host/port/encryption. Supports STARTTLS, SSL, authentication. No additional package needed. |
| php-mime-mail-parser | 10.x | MIME message parsing | Highest-performance PHP email parser. Requires `mailparse` PECL extension. Handles charset conversion, attachments, nested MIME. Purpose-built for webmail applications. |
| zbateson/mail-mime-parser | 4.x | MIME parsing (alternative) | Pure PHP alternative if `mailparse` extension is unavailable on shared hosting. No PECL extension required. Slightly slower but more portable. |

**Confidence: HIGH** — webklex/php-imap is the de facto standard for Laravel IMAP. Symfony Mailer is Laravel's native mail transport. For MIME parsing, choose based on hosting: `mailparse` extension available → php-mime-mail-parser; not available → zbateson/mail-mime-parser.

### Security Libraries

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| HTMLPurifier | 4.17.x | Server-side HTML sanitization | Industry standard for PHP HTML sanitization. Whitelist-based, standards-compliant. Use for initial server-side sanitization pass. Note: has known parser differential vulnerabilities with libxml2 under non-default configurations (when HTML comments enabled). For defense-in-depth, pair with client-side DOMPurify. |
| DOMPurify | 3.x | Client-side HTML sanitization | Sanitize in the browser at render time. Eliminates parser differential attacks that affect server-side sanitizers. Recommended as primary sanitization layer for email HTML rendering. |
| spatie/laravel-csp | latest | Content Security Policy headers | Preset-based CSP configuration with nonce support. Integrates with Vite for automatic nonce handling. Supports report-only mode for testing. Production-ready with many third-party service presets. |

**Confidence: MEDIUM** — HTMLPurifier is the standard but has known limitations. The OWASP research shows that server-side PHP sanitizers all share libxml2 parser differential vulnerabilities. The correct architecture is: server-side HTMLPurifier as defense-in-depth + client-side DOMPurify as primary sanitization.

### Search & Indexing

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Laravel Scout (database engine) | (built-in) | Full-text search | Ships with Laravel. Uses MySQL/PostgreSQL full-text indexes + LIKE clauses. No external service required. `SCOUT_DRIVER=database`. Perfect for shared hosting. |
| MySQL FULLTEXT indexes | (native) | Search backend | Native MySQL capability. Supports boolean mode, relevance scoring. Works on InnoDB tables since MySQL 5.6. No Redis, Meilisearch, or Elasticsearch needed. |

**Confidence: HIGH** — Laravel Scout's database engine is explicitly designed for this use case. The documentation states: "For most applications, this is all you need." Meilisearch/Elasticsearch are overkill for a single-company webmail app.

### Session & Cache (No Redis)

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| Database sessions | (built-in) | Session storage | `SESSION_DRIVER=database`. Laravel 12 defaults to database sessions. Works on shared hosting with MySQL. Performance is 40-60% faster than file-based on contended shared hosting disks. |
| Database cache | (built-in) | Application cache | `CACHE_STORE=database`. Use for folder metadata, UI preferences, short-lived mailbox metadata. No Redis dependency. |
| File cache | (built-in) | Fallback cache | `CACHE_STORE=file`. Simpler alternative for single-server deployments. Lower performance than database on shared hosting due to disk I/O contention. |

**Confidence: HIGH** — Laravel's database session driver is the default since Laravel 11. Documented, production-proven, zero additional infrastructure. Rate limiting works with database cache driver.

### Deployment & Hosting

| Technology | Version | Purpose | Why Recommended |
|------------|---------|---------|-----------------|
| cPanel shared hosting | — | Primary deployment target | OpenMail's core constraint. WordPress-style "Upload → Visit → Configure → Finish" experience. No SSH/CLI required for end users. |
| Laravel FTP Deployer | latest | CI/CD for shared hosting | `composer require inja-online/ftp-deployer`. Builds locally, deploys via FTP/FTPS. Runs migrations via temporary HTTP runner. Designed for exactly this constraint. |
| Vite | 6.x | Asset building | Build assets locally or in CI, deploy compiled output. Shared hosts never run Node/npm. |

**Confidence: HIGH** — The shared hosting deployment model is well-documented. The key insight: build off-host, deploy the artifact. The shared host receives ready-to-serve files only.

## Installation

```bash
# Core framework
composer create-project laravel/laravel openmail "^12.0"

# Mail protocol
composer require webklex/laravel-imap
composer require php-mime-mail-parser/php-mime-mail-parser

# Security
composer require ezyang/htmlpurifier
composer require spatie/laravel-csp

# Search
composer require laravel/scout

# Frontend (included in Laravel 12 by default)
# Livewire, Tailwind CSS, Alpine.js come with laravel/laravel

# Build assets
npm install
npm run build

# Development
npm run dev
```

**PHP Extension Requirements:**
```bash
# Required
php -m | grep mbstring    # Multibyte string support
php -m | grep openssl     # TLS for IMAP/SMTP
php -m | grep pdo_mysql   # MySQL driver
php -m | grep curl        # HTTP client
php -m | grep fileinfo    # MIME type detection

# Recommended (for best MIME parsing performance)
pecl install mailparse    # PECL mailparse extension

# Optional (legacy IMAP protocol support)
# php-imap extension (only needed for POP3/NNTP legacy protocols)
```

## Alternatives Considered

| Recommended | Alternative | When to Use Alternative |
|-------------|-------------|-------------------------|
| Laravel 12.x | Laravel 13.x | Only if you need AI primitives or semantic search — neither applies to OpenMail |
| Laravel 12.x | Laravel 11.x | Never — security support ended March 2026 |
| webklex/php-imap 6.x | DirectoryTree/ImapEngine | If you prefer a newer, cleaner API. ImapEngine is younger (38 stars) but well-designed. No PHP IMAP extension required. Laravel facade included. |
| webklex/php-imap 6.x | ddeboer/imap | If you want a more traditional OOP approach. Uses PHP IMAP extension. Less Laravel-native. |
| php-mime-mail-parser 10.x | zbateson/mail-mime-parser 4.x | If `mailparse` PECL extension is unavailable on shared hosting. Pure PHP, no extension needed. Slower parsing. |
| HTMLPurifier (server) | pacman-dom/html-sanitizer | Never — HTMLPurifier is the industry standard |
| DOMPurify (client) | Sanitizer API (browser native) | When browser support reaches your target matrix. Currently not universally available. |
| Laravel Scout (database) | Meilisearch | Only if you need typo tolerance, faceting, or sub-50ms search. Adds infrastructure complexity. |
| Laravel Scout (database) | Elasticsearch | Never for v1 — massive infrastructure overhead for a single-company webmail app |
| Database sessions | Redis sessions | Only if you move to VPS with multiple servers. Shared hosting can't run Redis. |
| Database cache | Redis cache | Same as above — shared hosting constraint |
| spatie/laravel-csp | hand-rolled CSP headers | Never — the package handles nonces, presets, Vite integration, and reporting |

## What NOT to Use

| Avoid | Why | Use Instead |
|-------|-----|-------------|
| Laravel 13.x | Too new (4 months old), AI-focused features irrelevant, smaller ecosystem compatibility | Laravel 12.x (stable, well-supported) |
| PHP IMAP extension (ext-imap) | Requires server-level installation, not available on shared hosting, legacy API | webklex/php-imap (pure PHP implementation) |
| Redis | Cannot run on shared hosting, violates core deployment constraint | Database driver for sessions, cache, and rate limiting |
| Meilisearch/Elasticsearch | Requires separate service, violates shared hosting constraint | Laravel Scout with MySQL FULLTEXT |
| Inertia.js / React / Vue | Adds SPA complexity, build step, API layer — not needed for server-rendered webmail | Livewire + Blade + Alpine.js |
| Roundcube | Existing webmail solution, not a library — you're building your own | Custom Laravel application |
| PHPMailer | SMTP sending library — Laravel already has Symfony Mailer built-in | Laravel's native mail system (Symfony Mailer) |
| league/html-to-markdown | Wrong direction — you need HTML sanitization, not conversion | HTMLPurifier + DOMPurify |
| `wire:model.live` as default | Sends request on every keystroke — performance killer for search fields | `wire:model` (deferred by default in Livewire 3) |
| Livewire 4.x | Too new, less documentation, breaking changes from v3 | Livewire 3.x (mature, documented, stable) |
| Tailwind CSS v4 `@apply` abuse | Overuse leads to unmaintainable CSS; v4 deprecates many v3 patterns | Utility-first approach with minimal `@apply` |

## Stack Patterns by Variant

**If shared hosting (cPanel) — primary target:**
- Use `database` driver for sessions, cache, and Scout search
- Use `file` driver only if database performance is problematic
- Build assets locally, deploy via FTP/SFTP
- Use zbateson/mail-mime-parser if `mailparse` extension unavailable
- No Redis, no queue workers, no Supervisor

**If VPS (secondary target):**
- Can use `redis` for sessions, cache, and rate limiting
- Can run `php artisan queue:work` for background jobs
- Can use Meilisearch for better search quality
- Can use Supervisor for process management
- Same application code — just different `.env` configuration

**If development environment:**
- Use `npm run dev` for HMR with Vite
- Use SQLite for local database (faster iteration)
- Use Mailpit or Mailtrap for SMTP testing
- Use `php artisan tinker` for IMAP debugging

## Version Compatibility

| Package | Compatible With | Notes |
|---------|-----------------|-------|
| laravel/framework ^12.0 | PHP 8.2 - 8.5 | PHP 8.3 recommended for production |
| livewire/livewire ^3.8 | Laravel 10, 11, 12, 13 | Livewire 3.x supports Laravel 10+ |
| webklex/laravel-imap ^6.2 | Laravel 6+ | Requires `webklex/php-imap ^6.2.0` |
| php-mime-mail-parser ^10.0 | PHP 8.2+ | Requires `ext-mailparse` PECL extension |
| zbateson/mail-mime-parser ^4.0 | PHP 8.1+ | Pure PHP, no extension needed |
| ezyang/htmlpurifier ^4.17 | PHP 5.5+ | Universal compatibility |
| spatie/laravel-csp | Laravel 9+ | Integrates with Vite nonce handling |
| laravel/scout ^10.x | Laravel 11, 12, 13 | Database engine built-in |
| tailwindcss ^4.3 | Node.js 18+ | Vite plugin included |
| alpinejs ^3.17 | — | Included by Livewire 3 automatically |

## Architecture Decision Records

### ADR-001: Laravel 12 over 13
**Decision:** Use Laravel 12.x
**Rationale:** Laravel 13 released March 2026 with AI primitives and semantic search. OpenMail is a traditional webmail app — these features add complexity without value. Laravel 12 has 14+ months of maturity, extensive documentation, and full ecosystem support. Security support until Feb 2027.
**Trade-off:** Misses Laravel 13's improvements. Can upgrade later when 13.x matures.

### ADR-002: Livewire 3 over 4
**Decision:** Use Livewire 3.x (3.8.x)
**Rationale:** Livewire 4.x released recently with breaking changes. Livewire 3 is the documented, stable version with the largest community knowledge base. All tutorials, packages, and examples target v3.
**Trade-off:** Will need migration path to v4 eventually. But v3 is supported alongside v4 (both receive patches).

### ADR-003: Database-only infrastructure
**Decision:** No Redis, no Meilisearch, no external services
**Rationale:** Shared hosting is the primary deployment target. cPanel environments cannot run Redis, Meilisearch, or queue workers. The database driver for sessions, cache, and search is explicitly designed for this constraint.
**Trade-off:** Lower performance ceiling. Acceptable for single-company deployment with <100 concurrent users.

### ADR-004: Dual HTML sanitization
**Decision:** Server-side HTMLPurifier + client-side DOMPurify
**Rationale:** OWASP research (2024) demonstrated that all PHP server-side HTML sanitizers share libxml2 parser differential vulnerabilities. The correct architecture sanitizes at render time in the browser. HTMLPurifier provides defense-in-depth; DOMPurify is the primary security control.
**Trade-off:** Slightly more complexity. Justified by the security requirements of rendering untrusted email HTML.

## Sources

- Laravel releases page (laravel.com/docs/releases) — version support matrix confirmed
- Packagist: webklex/laravel-imap 6.2.0 (2025-04-25) — IMAP library version and features
- Packagist: php-mime-mail-parser 10.0.0 (2026-04-22) — MIME parser PHP 8.2+ requirement
- Packagist: livewire/livewire 4.4.2 / 3.8.6 (2026-08-24) — Livewire version availability
- Packagist: tailwindcss 4.3.x (2026-07-16) — Tailwind v4 stable
- GitHub: alpinejs/alpine 3.17.x (2026-08-24) — Alpine.js version
- OWASP AppSec USA 2024: "Why Server-Side HTML Sanitization Fails" — HTMLPurifier vulnerability research
- Laravel Scout docs (laravel.com/docs/scout) — database engine documentation
- spatie/laravel-csp GitHub — CSP package features and presets
- DeployHQ blog (2026-07-24) — cPanel Laravel deployment patterns
- Laravel rate limiting docs — database cache driver compatibility
- ImapEngine docs (imapengine.com) — alternative IMAP library evaluation

---
*Stack research for: OpenMail webmail application*
*Researched: 2026-09-05*
