# Pitfalls Research

**Domain:** Self-hosted Laravel IMAP/SMTP webmail application
**Researched:** 2026-09-05
**Confidence:** HIGH

## Critical Pitfalls

### Pitfall 1: Rendering Email HTML as Trusted Application Content

**What goes wrong:**
Email HTML is rendered directly using `dangerouslySetInnerHTML` or equivalent, allowing attacker-controlled scripts, event handlers, `javascript:` URLs, and SVG payloads to execute within the trusted application context. A single malicious email can steal session tokens, read application data, or hijack user sessions.

**Why it happens:**
Developers treat email content as "just data" and render it like any other HTML. Email HTML is one of the most hostile input surfaces on the internet — every inbound message can contain arbitrary HTML, embedded images, links, styles, and malformed markup. The 2026 Black Hat research demonstrated CSS-only attacks that bypass even "sanitized" renderings to steal passwords and tokens.

**How to avoid:**
Implement a 4-layer defense: (1) DOMPurify server-side sanitization stripping `<script>`, event handlers, `javascript:` URLs, and malicious SVG; (2) Render email inside a sandboxed `<iframe>` with `sandbox="allow-same-origin"` but NOT `allow-scripts`; (3) Apply strict CSP inside the iframe (`default-src 'none'; img-src https: data:; style-src 'unsafe-inline'`); (4) Never use `dangerouslySetInnerHTML` on unsanitized email content. Feature-detect iframe sandbox support and fall back to plaintext-only for unsupported browsers.

**Warning signs:**
- Email rendering uses `dangerouslySetInnerHTML` or `v-html` without prior sanitization
- No iframe sandbox wrapping email content
- CSP headers missing or allow `unsafe-inline` for scripts
- No DOMPurify or HTMLPurifier in the rendering pipeline

**Phase to address:** Phase 5 (Security Hardening) — but the HTML sanitizer must be architected in Phase 2 (Mailbox) when message viewer is built

---

### Pitfall 2: IMAP UID as Sole Message Identity

**What goes wrong:**
The application uses IMAP UID as the only identifier for messages. IMAP UIDs are folder-specific, can change when a mailbox is rebuilt (UIDVALIDITY reset), and are not unique across folders. Exchange servers can change UIDs when another protocol (MAPI, EWS) modifies a message. This causes duplicate tickets, lost messages, and broken references.

**Why it happens:**
IMAP UIDs appear stable during development with small test mailboxes. The UID specification says they "should not change" but reality differs — server maintenance, mailbox compaction, and multi-protocol access all cause UID reassignment. Developers assume UID uniqueness across folders without reading RFC 3501 carefully.

**How to avoid:**
Use composite identity: `(folder_path, uid)` as the primary lookup, but always cross-reference with `Message-ID` header for deduplication. Store `Message-ID` in the database and use it as the authoritative identity for threading and deduplication. When UID changes are detected (via UIDVALIDITY check), reconcile by Message-ID rather than treating the message as new. Never assume UID uniqueness across folders.

**Warning signs:**
- Database schema uses only `uid` as primary key without folder context
- No Message-ID storage or cross-referencing
- No UIDVALIDITY tracking per folder
- "Duplicate message" reports from users viewing Sent vs Inbox

**Phase to address:** Phase 1 (Foundation) — database schema must establish composite identity from day one

---

### Pitfall 3: SMTP Port/Encryption Mismatch

**What goes wrong:**
The setup wizard or configuration accepts port 465 with `tls` encryption (STARTTLS), or port 587 with `ssl` encryption (implicit TLS). The connection fails silently or with cryptic errors like `stream_socket_enable_crypto(): SSL: Success` (which is actually a failure). Users see "Connection timed out" or "Authentication failed" when the real issue is protocol mismatch.

**Why it happens:**
The port/encryption mapping is non-intuitive: port 465 = implicit TLS (`ssl`), port 587 = STARTTLS (`tls`). Shared hosting providers like GoDaddy hijack port 587 connections and redirect to their own proxy, presenting mismatched certificates. Different PHP libraries interpret "tls" vs "ssl" differently — Laravel's Symfony Mailer treats them as distinct transport modes.

**How to avoid:**
The setup wizard must enforce correct port/encryption pairs:
- Port 465 → Encryption: SSL/TLS (implicit)
- Port 587 → Encryption: STARTTLS
- Port 25 → Usually blocked, warn users
- Port 2525 → Alternative, note it's non-standard

Auto-detect and reject mismatched combinations before attempting connection. During the IMAP/SMTP test in the wizard, report the actual TLS certificate CN and compare against the expected hostname. Document the GoDaddy/Secureserver hijack issue in troubleshooting.

**Warning signs:**
- Users report "Authentication failed" when credentials are correct
- `stream_socket_enable_crypto()` errors in logs
- Connection works locally but fails on production
- TLS certificate CN doesn't match the configured hostname

**Phase to address:** Phase 1 (Foundation) — setup wizard must validate port/encryption pairs

---

### Pitfall 4: Attachment Filename Path Traversal (CVE-2023-35169 Pattern)

**What goes wrong:**
An attacker sends an email with an attachment named `../../../../var/www/html/shell.php`. When the application saves attachments using the original filename from email headers, the file escapes the intended storage directory. On shared hosting with PHP, this can result in remote code execution if the file lands in a web-accessible directory.

**Why it happens:**
Email attachment filenames come from untrusted external input. The `Content-Disposition: attachment; filename=` header is attacker-controlled. Libraries like `webklex/php-imap` (pre-5.3.0) had a CVE for exactly this — they passed unsanitized filenames directly to filesystem operations. Developers assume "it came from IMAP, so it's safe."

**How to avoid:**
Never use the original attachment filename for storage. Generate UUID-based filenames (`bin2hex(random_bytes(16))`) and store the original display name in the database only. Validate the resolved path stays within the storage directory before every write. Use `finfo_file()` (magic bytes) to validate MIME types — never trust `Content-Type` headers. Store attachments outside the web root and serve through a controlled endpoint with `Content-Disposition: attachment`.

**Warning signs:**
- Attachment save uses original filename from email headers
- No path traversal check before filesystem write
- Attachments stored inside `public/` or `public_html/`
- No magic byte validation, only extension checks

**Phase to address:** Phase 1 (Foundation) — attachment storage architecture must be correct from the start

---

### Pitfall 5: Storing Mailbox Passwords in Plaintext or Recoverable Form

**What goes wrong:**
Mailbox passwords are stored in the database in plaintext, base64, or reversible encryption. If the database is compromised (SQL injection, backup leak, shared hosting file access), all user mailbox credentials are exposed. This is catastrophic because users often reuse passwords across services.

**Why it happens:**
The application needs to authenticate to IMAP/SMTP on behalf of the user, creating a temptation to store credentials for re-use. Developers use base64 encoding thinking it's "encryption." Some store passwords in `.env` files that are web-accessible on misconfigured shared hosting.

**How to avoid:**
Never store mailbox passwords in plaintext. Options: (1) Session-only: Authenticate to IMAP, store the authenticated session/connection, discard the password immediately; (2) If persistent storage is required for background operations, use Laravel's encryption (`Crypt::encryptString()`) with a key derived from `APP_KEY`, and never log or expose decrypted values; (3) The setup wizard must never write passwords to log files, exception pages, or client-side JavaScript. Implement credential rotation support for future extensibility.

**Warning signs:**
- Password fields in database are VARCHAR without encryption
- `.env` file readable from web root
- Passwords appearing in Laravel log files
- Exception pages showing full connection strings

**Phase to address:** Phase 1 (Foundation) — credential handling architecture must be designed before any authentication code is written

---

### Pitfall 6: Laravel on Shared Hosting Deployment Failures

**What goes wrong:**
Application crashes with 500 errors on shared hosting due to: wrong document root (pointing at project root instead of `public/`), `storage/` and `bootstrap/cache/` not writable, missing `APP_KEY`, Composer dependencies not installed, wrong PHP version, or `.env` file inside web root.

**Why it happens:**
Shared hosting has constraints that VPS environments don't: no SSH by default, limited PHP extensions, suPHP file ownership, restricted `open_basedir`, and no queue workers. The WordPress-style deployment wizard must account for all of these. Developers test on VPS and assume shared hosting works identically.

**How to avoid:**
The setup wizard must: (1) Check PHP version and required extensions before proceeding; (2) Verify `storage/` and `bootstrap/cache/` are writable (offer to fix permissions); (3) Generate `APP_KEY` automatically; (4) Test database connection before continuing; (5) Verify document root points to `public/`; (6) Check `upload_max_filesize`, `post_max_size`, `memory_limit`, and `max_execution_time`; (7) Validate `.env` is not inside `public/`. For no-SSH deployments, ship a pre-built `vendor/` directory in the release package.

**Warning signs:**
- 500 errors immediately after upload
- "No application encryption key has been specified" error
- `file_put_contents(): failed to open stream: Permission denied`
- Users see directory listing instead of application

**Phase to address:** Phase 1 (Foundation) — setup wizard must be tested on actual shared hosting

---

### Pitfall 7: IMAP Connection Leak / No Connection Pooling

**What goes wrong:**
Every HTTP request opens a new IMAP connection to the mail server. On a page with 50 messages, this could mean 50+ IMAP connections. Mail servers have connection limits (Dovecot default: 64 per user). The application exhausts the server's connection pool, causing "Too many login failures" or "Connection rejected" errors for all users.

**Why it happens:**
IMAP connections are expensive to establish (TLS handshake + authentication) and developers treat them as stateless HTTP-style calls. Without connection pooling or persistent connections, each request creates and destroys a connection. The `webklex/php-imap` library creates a new connection per Client instance.

**How to avoid:**
Implement connection management: (1) Use a singleton or request-scoped IMAP connection pool; (2) Reuse connections across Livewire component updates within the same request; (3) Set explicit connection timeouts (10-15 seconds); (4) Implement graceful connection failure handling — don't crash the entire page if one IMAP call fails; (5) Cache folder metadata and message lists with short TTL (30-60 seconds) to reduce IMAP round trips; (6) Monitor connection count in logs for debugging.

**Warning signs:**
- "Connection rejected" or "Too many connections" from IMAP server
- Page load times increase linearly with message count
- Mail server logs showing connection storms
- Shared hosting hitting IMAP connection limits

**Phase to address:** Phase 2 (Mailbox) — connection management must be architected before building message list

---

### Pitfall 8: Thread Reconstruction Fails on Subject-Only Fallback

**What goes wrong:**
Message threading relies on subject line normalization (stripping "Re:", "Fwd:", etc.) as the primary threading mechanism. This causes unrelated messages with similar subjects to be grouped together, and legitimate reply chains with changed subjects to be split apart. Users see conversations mixed with unrelated messages.

**Why it happens:**
Subject-based threading is the easiest to implement and works for simple cases. But real email chains frequently change subjects ( users edit reply subjects), and automated messages share subjects across unrelated conversations. The RFC 2822 threading algorithm (Message-ID, In-Reply-To, References) is more complex but far more accurate.

**How to avoid:**
Implement standards-based threading first: (1) Parse Message-ID, In-Reply-To, and References headers; (2) Build a thread tree using these headers; (3) Use subject normalization ONLY as a fallback when header-based threading yields no results; (4) Handle edge cases: missing Message-ID (generate a deterministic one), duplicate Message-ID across folders, malformed In-Reply-To headers; (5) Test with real-world mailboxes including Outlook, Gmail, and Apple Mail exports.

**Warning signs:**
- Threads showing messages from different senders/dates mixed together
- Reply chains split across multiple threads
- "Re: Re: Re: Re:" subject lines not being collapsed
- Thread count significantly different from what users see in other clients

**Phase to address:** Phase 4 (Organization) — threading must be built on top of correct identity model from Phase 1

---

### Pitfall 9: Setup Wizard Re-Entrancy / Installation Lock Bypass

**What goes wrong:**
After installation completes, an attacker can re-run the setup wizard by visiting `/setup` directly. This allows them to: reconfigure the database connection, change the administrator account, modify mail server settings, or expose configuration errors that leak credentials. On shared hosting, the installation lock file may be inside the web root and deletable.

**Why it happens:**
The wizard is built as a simple multi-step form without checking installation state on every request. The lock mechanism uses a file that can be deleted or a database flag that can be reset. Developers focus on the "happy path" of first-time installation and forget to harden the post-install state.

**How to avoid:**
The installation lock must: (1) Be stored OUTSIDE the web root (e.g., `storage/framework/installed`); (2) Be checked on every request to any `/setup` route — return 404 or redirect to login if already installed; (3) Never be deletable through the web application; (4) Include a cryptographic hash of the installation timestamp and APP_KEY so it can't be forged; (5) The wizard must detect existing `.env` and refuse to proceed if `APP_KEY` is already set; (6) Log all setup wizard access attempts for audit.

**Warning signs:**
- `/setup` route accessible after installation
- No installation state check in setup middleware
- Lock file inside `public/` directory
- No audit logging of setup wizard access

**Phase to address:** Phase 1 (Foundation) — installation lock must be part of the wizard architecture

---

### Pitfall 10: CSS Injection Attacks Bypassing Email Sanitization

**What goes wrong:**
August 2026 Black Hat research demonstrated that sanitized CSS can still be weaponized. Attackers use CSS properties that sanitizers allow but browsers interpret in dangerous ways: overlaying fake login forms on top of the real UI, exfiltrating tokens through CSS selectors, and creating phishing overlays that look identical to the application's own interface.

**Why it happens:**
Most HTML sanitizers focus on JavaScript and event handlers but allow CSS as "safe." Modern browsers interpret CSS in ways that create security boundaries the sanitizer didn't anticipate. The gap between what a sanitizer allows and what a browser does with those properties is the attack surface.

**How to avoid:**
Render email content in a sandboxed iframe with `sandbox="allow-same-origin"` — this creates a genuine security boundary even if CSS injection slips through sanitization. Apply strict CSP inside the iframe to block external resource loading. Strip `<style>` tags entirely and only allow inline `style` attributes with a strict whitelist of safe properties (font-*, color, margin, padding — no position, z-index, opacity, display, background). Test with the actual attack payloads from the Black Hat research.

**Warning signs:**
- Email CSS allows `position`, `z-index`, `opacity`, `display`, or `background` properties
- No iframe sandbox wrapping email content
- CSP allows `unsafe-inline` for styles in email context
- Email HTML not isolated from application DOM

**Phase to address:** Phase 5 (Security Hardening) — CSS sanitization rules must be established alongside HTML sanitization

---

## Technical Debt Patterns

Shortcuts that seem reasonable but create long-term problems.

| Shortcut | Immediate Benefit | Long-term Cost | When Acceptable |
|----------|-------------------|----------------|-----------------|
| Store IMAP connection state in session | Avoids reconnection per request | Session bloat, stale connections, serialization failures | Never — use request-scoped connections |
| Use subject-line threading only | Simple implementation, works for 80% of cases | Broken threads for 20% of real email, user trust erosion | Never — implement header-based threading from the start |
| Trust Content-Type headers from email | No magic byte parsing needed | MIME spoofing, attachment path traversal CVEs | Never — always validate with finfo_file() |
| Store attachments in public/ directory | Direct URL access, simple implementation | RCE via malicious attachment, web shell risk | Never — store outside web root, serve via endpoint |
| Cache IMAP results indefinitely | Fast page loads | Stale mailbox state, users see old data | Use 30-60 second TTL, invalidate on write operations |
| Skip UIDVALIDITY checks | Simpler sync logic | UID reuse causes phantom messages or lost state | Never — check UIDVALIDITY before every UID operation |

## Integration Gotchas

Common mistakes when connecting to external services.

| Integration | Common Mistake | Correct Approach |
|-------------|----------------|------------------|
| IMAP server | Open new connection per request | Pool connections per request, reuse across Livewire updates |
| IMAP server | Ignore UIDVALIDITY | Check UIDVALIDITY on every folder select, reconcile on change |
| IMAP server | Use `SELECT` instead of `EXAMINE` for read operations | Use `EXAMINE` (read-only) when you don't need to modify flags |
| SMTP server | Configure port 587 with `ssl` encryption | Port 587 requires STARTTLS (`tls`), not implicit SSL |
| SMTP server | Send test email during setup wizard | Test connection only; send test email only on explicit user action |
| SMTP server | Ignore STARTTLS downgrade attacks | Validate TLS certificate CN matches expected hostname |
| Shared hosting (cPanel) | Expect Composer/CLI availability | Ship pre-built vendor/ directory, no-SSH deployment path |
| Shared hosting (cPanel) | Use Redis for sessions/cache | Use file-based sessions and cache for shared hosting compatibility |
| HTML sanitizer | Allow `<style>` tags in email | Strip `<style>` tags; only allow safe inline style properties |
| MIME parser | Trust attachment filename from headers | Generate UUID-based filenames, store original name in DB only |

## Performance Traps

Patterns that work at small scale but fail as usage grows.

| Trap | Symptoms | Prevention | When It Breaks |
|------|----------|------------|----------------|
| Fetch all messages on folder open | Page load >5s with 1000+ messages | Paginate with IMAP FETCH (SEQ range), load 25-50 per page | 500+ messages in any folder |
| No IMAP connection pooling | "Connection rejected" errors, slow page loads | Singleton connection per request, reuse across operations | 10+ concurrent users |
| Full-text search via IMAP on every keystroke | 2-5 second search latency | Index messages in database, search locally | Any mailbox with 1000+ messages |
| Synchronous attachment download | Timeout on large attachments | Stream attachments, chunk downloads, set appropriate PHP limits | Attachments >10MB |
| Cache entire mailbox in session | Session size >1MB, slow serialization | Cache only folder metadata and recent message headers | 50+ folders or 1000+ messages |
| No pagination for message list | Memory exhaustion, slow renders | IMAP FETCH with windowing (e.g., 50 messages at a time) | 200+ messages in folder |

## Security Mistakes

Domain-specific security issues beyond general web security.

| Mistake | Risk | Prevention |
|---------|------|------------|
| Render email HTML without iframe sandbox | XSS, session theft, phishing | Always render in sandboxed iframe with strict CSP |
| Allow `javascript:` URLs in email links | Script execution in application context | Strip all `javascript:` and `data:` URLs from email content |
| Store IMAP password in session data | Password exposed in session file/storage | Use request-scoped IMAP connections, never persist passwords |
| No rate limiting on IMAP operations | Mail server exhaustion, DoS | Rate limit IMAP operations per user (e.g., 100/minute) |
| Trust email `From` header for display | Spoofed sender names for phishing | Display actual authenticated sender, flag suspicious From mismatches |
| Allow SVG attachments without sanitization | Stored XSS via embedded JavaScript in SVG | Strip SVG files or sanitize with DOMPurify; prefer blocking SVG |
| No CSP headers on webmail pages | Enables XSS exploitation | Implement strict CSP: `default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'` |
| Expose IMAP error details to users | Information disclosure about mail server | Log detailed errors server-side; show generic "Connection failed" to users |
| No CSRF protection on compose/send | Attacker can send email as user | CSRF tokens on all state-changing operations including compose |
| Web-accessible installation routes | Attacker reconfigures application | Lock `/setup` after installation, never serve from public root |

## UX Pitfalls

Common user experience mistakes in this domain.

| Pitfall | User Impact | Better Approach |
|---------|-------------|-----------------|
| Show raw IMAP errors to users | Confusing "NO [AUTHENTICATIONFAILED]" messages | Translate to "Login failed — check your email and password" |
| No loading states during IMAP operations | Users click repeatedly, trigger duplicate operations | Show spinners for every IMAP call, disable buttons during load |
| Autoload all messages on folder switch | Slow initial load, bandwidth waste | Load first 25 messages, infinite scroll or pagination for rest |
| Block entire UI during SMTP send | Users think app is frozen | Show progress indicator, allow cancel, send in background |
| No offline/error state | Users don't know if message was sent | Clear success/failure notification, retry option |
| Assume all mailboxes have same folder structure | Missing Sent/Drafts/Trash for non-standard configs | Auto-detect folder names via IMAP NAMESPACE, allow manual mapping |
| Display all email headers by default | Information overload for non-technical users | Collapse headers behind "Show details" toggle |
| No keyboard shortcuts | Power users slowed down | Implement Gmail-compatible shortcuts (c=compose, r=reply, etc.) |

## "Looks Done But Isn't" Checklist

Things that appear complete but are missing critical pieces.

- [ ] **IMAP Connection:** Works for INBOX but fails on folders with special characters (e.g., `[Gmail]/Sent Mail`) — verify IMAP NAMESPACE handling
- [ ] **Message Display:** Shows plain text but HTML emails render with broken styles or missing images — verify CSS isolation and image proxy
- [ ] **Compose:** Sends email but doesn't save to Sent folder — verify IMAP APPEND to Sent after SMTP send
- [ ] **Threading:** Groups obvious replies but breaks on cross-client threading (Outlook vs Gmail vs Apple Mail) — verify with real multi-client test data
- [ ] **Search:** Returns results but misses messages with encoded headers (RFC 2047) — verify UTF-8 and MIME-encoded header search
- [ ] **Attachments:** Downloads small files but fails on large attachments or binary files — verify streaming, chunked transfer, and MIME type handling
- [ ] **Setup Wizard:** Works on VPS but fails on shared hosting — verify on actual cPanel with limited PHP extensions
- [ ] **Session:** Login works but session expires prematurely or doesn't rotate — verify session regeneration and timeout configuration
- [ ] **Flags:** Mark as read works but star/flag doesn't sync back to IMAP server — verify bidirectional flag sync
- [ ] **Draft Autosave:** Saves draft locally but doesn't create IMAP draft — verify IMAP APPEND to Drafts folder with \Draft flag

## Recovery Strategies

When pitfalls occur despite prevention, how to recover.

| Pitfall | Recovery Cost | Recovery Steps |
|---------|---------------|----------------|
| XSS via email HTML | HIGH | Patch sanitizer, audit all email rendering paths, force cache clear, notify affected users |
| IMAP UID identity mismatch | MEDIUM | Rebuild message index from Message-ID, reconcile UIDs, update foreign key references |
| SMTP port/encryption mismatch | LOW | Update wizard validation, add port/encryption pair testing, update documentation |
| Attachment path traversal | HIGH | Audit all stored attachments, scan for malicious files, regenerate filenames, force re-download |
| Plaintext credential exposure | CRITICAL | Rotate all affected passwords immediately, audit access logs, notify users, implement encryption |
| Shared hosting 500 errors | LOW | Fix permissions, verify document root, check PHP version, validate .env location |
| IMAP connection exhaustion | MEDIUM | Implement connection pooling, add rate limiting, restart mail server, monitor connection count |
| Broken threading | MEDIUM | Rebuild thread tree from headers, clear thread cache, validate with test dataset |

## Pitfall-to-Phase Mapping

How roadmap phases should address these pitfalls.

| Pitfall | Prevention Phase | Verification |
|---------|------------------|--------------|
| Email HTML XSS | Phase 2 (Mailbox) + Phase 5 (Security) | Security test: send email with `<script>alert(1)</script>`, verify no execution |
| IMAP UID identity | Phase 1 (Foundation) | Unit test: UID change detection, Message-ID cross-reference |
| SMTP port/encryption | Phase 1 (Foundation) | Setup wizard test: invalid port/encryption pair rejected |
| Attachment path traversal | Phase 1 (Foundation) | Security test: email with `../../` filename, verify stored safely |
| Credential storage | Phase 1 (Foundation) | Audit: grep database dumps for plaintext passwords |
| Shared hosting deployment | Phase 1 (Foundation) | Integration test: deploy on actual cPanel, verify all checks pass |
| IMAP connection leak | Phase 2 (Mailbox) | Load test: 50 concurrent requests, verify connection count stays bounded |
| Thread reconstruction | Phase 4 (Organization) | Test with real mailboxes from Outlook, Gmail, Apple Mail exports |
| Setup wizard re-entrancy | Phase 1 (Foundation) | Security test: access /setup after installation, verify 404 |
| CSS injection | Phase 5 (Security) | Security test: send email with CSS overlay attack, verify iframe isolation |

## Sources

- AgentMail engineering blog: "Rendering Email Safely: Preventing Phishing Attacks" (2026-02)
- Close.com engineering: "Rendering untrusted HTML email, safely" (2021-09)
- Black Hat USA 2026: Gareth Heyes CSS attacks research (2026-08)
- PortSwigger Research: "CSS: the bomb inside your inbox" (2026-08)
- The Hacker News: "New CSS Attacks Can Break Webmail Defenses" (2026-08)
- CVE-2023-35169: php-imap path traversal → RCE via attachment filename
- CVE-2023-43770: Roundcube XSS through plain text email links
- Stack Overflow: IMAP synchronization strategy discussions
- Microsoft Learn: IMAP UID change behavior with Exchange
- DeployHQ: "Deploy a Modern Laravel App to cPanel Shared Hosting" (2026-07)
- Domain India: "Laravel on cPanel and DirectAdmin" (2026-09)
- OWASP File Upload Cheat Sheet
- OWASP Session Fixation documentation
- Laravel OWASP Cheat Sheet
- StackShield: Laravel Session Security Configuration (2026-05)
- Canadian Web Hosting: "SMTP Troubleshooting for Self-Hosted Apps" (2026-06)
- MailSlurp: SMTP Authentication guide (2026-06)

---
*Pitfalls research for: Self-hosted Laravel IMAP/SMTP webmail application (OpenMail)*
*Researched: 2026-09-05*
