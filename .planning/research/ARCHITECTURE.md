# Architecture Research

**Domain:** Self-hosted webmail application
**Researched:** 2026-09-05
**Confidence:** MEDIUM

## Standard Architecture

### System Overview

Self-hosted webmail systems follow a consistent three-tier architecture. The key insight from studying Roundcube, SnappyMail, and SOGo is that the mail server (IMAP/SMTP) is always external — the webmail application is a **client**, not a server.

```
┌─────────────────────────────────────────────────────────────┐
│                     Presentation Layer                      │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   Blade     │  │  Livewire   │  │  Alpine.js  │         │
│  │  Templates  │  │ Components  │  │  Interactivity│        │
│  └──────┬──────┘  └──────┬──────┘  └──────┬──────┘         │
├─────────┴────────────────┴────────────────┴─────────────────┤
│                     Application Layer                        │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  Auth    │  │  Mailbox │  │ Composer │  │  Search  │   │
│  │ Service  │  │ Service  │  │ Service  │  │  Service │   │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘   │
├───────┴──────────────┴──────────────┴──────────────┴─────────┤
│                     Domain Layer                              │
│  ┌─────────────────────────────────────────────────────┐    │
│  │  MailProvider Interface │ Entities │ Value Objects   │    │
│  └─────────────────────────────────────────────────────┘    │
├─────────────────────────────────────────────────────────────┤
│                     Infrastructure Layer                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │  IMAP    │  │  SMTP    │  │ Database │  │  Cache   │   │
│  │ Client   │  │ Transport│  │ (MySQL)  │  │ (File)   │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
         │                │
         ▼                ▼
   ┌───────────┐    ┌───────────┐
   │  IMAP     │    │  SMTP     │
   │  Server   │    │  Server   │
   │ (External)│    │ (External)│
   └───────────┘    └───────────┘
```

### Component Responsibilities

| Component | Responsibility | Typical Implementation |
|-----------|----------------|------------------------|
| **Presentation** | UI rendering, user interaction, state management | Blade templates, Livewire components, Alpine.js |
| **Application** | Orchestrate use cases, validate input, coordinate services | Service classes, Action classes, Form Requests |
| **Domain** | Business rules, entities, provider contracts | Pure PHP interfaces, value objects, domain exceptions |
| **Infrastructure** | External system integration, persistence | Eloquent models, IMAP/SMTP clients, cache stores |

## Recommended Project Structure

```
app/
├── Auth/                          # Authentication domain
│   ├── Actions/                   # Use cases (Login, Logout)
│   ├── Contracts/                 # Interfaces (AuthProvider)
│   └── Services/                  # Business logic
│
├── Mail/                          # Core mail domain
│   ├── Actions/                   # Use cases (ReadMessage, SendMessage, etc.)
│   ├── Contracts/                 # MailProvider interface
│   ├── Entities/                  # Message, Folder, Thread entities
│   ├── Events/                    # Domain events (MessageReceived, etc.)
│   ├── Exceptions/                # Domain exceptions
│   ├── Services/                  # MailReader, MailSender, MailSearch
│   └── ValueObjects/              # EmailAddress, MessageId, etc.
│
├── Install/                       # Setup wizard subsystem
│   ├── Actions/                   # Use cases (RunInstallation, TestConnection)
│   ├── Contracts/                 # Interfaces
│   ├── Services/                  # RequirementChecker, DatabaseInstaller, etc.
│   └── ValueObjects/              # InstallationState, Requirement
│
├── Contacts/                      # Contacts domain
│   ├── Actions/
│   ├── Contracts/
│   └── Services/
│
├── Settings/                      # User settings domain
│   ├── Actions/
│   ├── Contracts/
│   └── Services/
│
├── Security/                      # Cross-cutting security concerns
│   ├── Sanitizer/                 # HTML email sanitization
│   ├── Middleware/                 # Security middleware
│   └── Services/                  # Security-related services
│
├── Http/                          # Laravel HTTP layer
│   ├── Controllers/               # Thin controllers only
│   ├── Livewire/                  # Livewire components
│   ├── Middleware/                 # HTTP middleware
│   └── Requests/                  # Form Request validation
│
├── Models/                        # Eloquent models (single directory)
│
├── Support/                       # Shared utilities
│   ├── Traits/
│   └── Helpers/
│
└── Providers/                     # Service providers

config/
├── mail.php                       # Mail provider configuration
├── install.php                    # Installer configuration
└── security.php                   # Security configuration

database/
├── migrations/                    # All migrations (or per-domain)
└── seeders/

resources/
├── views/
│   ├── install/                   # Setup wizard views
│   │   ├── layout.blade.php
│   │   ├── welcome.blade.php
│   │   ├── requirements.blade.php
│   │   ├── database.blade.php
│   │   ├── mail.blade.php
│   │   ├── application.blade.php
│   │   ├── administrator.blade.php
│   │   ├── security.blade.php
│   │   ├── verify.blade.php
│   │   └── complete.blade.php
│   ├── mail/                      # Mail views
│   │   ├── layout.blade.php
│   │   ├── inbox.blade.php
│   │   ├── message.blade.php
│   │   ├── compose.blade.php
│   │   └── search.blade.php
│   ├── components/                # Reusable Blade components
│   └── layouts/                   # Base layouts
├── css/
└── js/

routes/
├── web.php                        # Web routes
└── console.php                    # Console commands
```

### Structure Rationale

- **Domain-organized, not type-organized:** Each business capability (Mail, Auth, Contacts) owns its own services, contracts, and value objects. This prevents the "scattered model" problem where understanding one feature requires jumping between 5 directories.
- **Livewire in Http/:** Livewire components are presentation-layer controllers. They belong in Http/, not alongside domain logic.
- **Services layer:** Business logic lives in dedicated service/action classes, never inside Livewire components or Eloquent models.
- **Contracts directory:** Each domain exposes interfaces, not implementations. This enables provider swapping (IMAP → OAuth) without rewriting consumers.

## Architectural Patterns

### Pattern 1: Provider Abstraction (Strategy Pattern)

**What:** Define a `MailProvider` interface that abstracts all mail operations. The IMAP/SMTP implementation is one concrete provider. Future providers (OAuth, API) implement the same interface.

**When to use:** Always — this is the core architectural decision for OpenMail.

**Trade-offs:**
- **Pros:** Provider-independent business logic, testable without real mail servers, future-proof
- **Cons:** Slight indirection overhead, interface design requires foresight

**Example:**
```php
interface MailProvider
{
    public function folders(): Collection;
    public function messages(string $folder, int $page, int $perPage): MessagePage;
    public function message(string $folder, string $uid): Message;
    public function send(OutgoingMessage $message): void;
    public function delete(string $folder, string $uid): void;
    public function markRead(string $folder, string $uid): void;
    public function markUnread(string $folder, string $uid): void;
    public function move(string $folder, string $uid, string $destination): void;
    public function search(string $folder, string $query): Collection;
}

class ImapSmtpProvider implements MailProvider
{
    private ImapConnection $imap;
    private SmtpTransport $smtp;

    public function folders(): Collection
    {
        return $this->imap->listFolders();
    }

    public function send(OutgoingMessage $message): void
    {
        $this->smtp->send($message);
    }
}
```

### Pattern 2: Thin Livewire Components

**What:** Livewire components handle only UI state, validation, and delegation to services. They never contain business logic, database queries, or mail protocol handling.

**When to use:** For every Livewire component.

**Trade-offs:**
- **Pros:** Testable, maintainable, clear separation of concerns
- **Cons:** More files, requires discipline

**Example:**
```php
class Inbox extends Component
{
    public string $folder = 'INBOX';
    public int $page = 1;
    public Collection $messages;

    public function boot(MailReader $reader): void
    {
        $this->messages = $reader->listMessages(
            folder: $this->folder,
            page: $this->page,
            perPage: 25
        );
    }

    public function refresh(MailReader $reader): void
    {
        $this->messages = $reader->listMessages(
            folder: $this->folder,
            page: $this->page,
            perPage: 25
        );
    }

    public function render(): View
    {
        return view('mail.inbox');
    }
}
```

### Pattern 3: Action/Use Case Classes

**What:** Each user-facing operation (read message, send reply, search) is encapsulated in a single-responsibility Action class. Actions orchestrate services and domain logic.

**When to use:** For every distinct user operation that involves more than one service call.

**Trade-offs:**
- **Pros:** Single responsibility, testable in isolation, can be invoked from CLI or API later
- **Cons:** More classes, requires understanding the pattern

**Example:**
```php
class ReadMessageAction
{
    public function __construct(
        private MailReader $reader,
        private Sanitizer $sanitizer,
    ) {}

    public function execute(string $folder, string $uid): MessageView
    {
        $message = $this->reader->getMessage($folder, $uid);

        // Sanitize HTML content
        $message->htmlBody = $this->sanitizer->sanitize(
            $message->htmlBody
        );

        return $message;
    }
}
```

### Pattern 4: Event-Driven Cross-Module Communication

**What:** When one domain needs to notify another (e.g., new message triggers contact update), use Laravel events. The producing module fires an event; consuming modules listen independently.

**When to use:** For cross-domain side effects.

**Trade-offs:**
- **Pros:** Loose coupling, extensible, follows Laravel conventions
- **Cons:** Harder to trace execution flow, eventual consistency

**Example:**
```php
// In Mail domain
event(new MessageReceived($message));

// In Contacts domain listener
class UpdateRecentRecipients
{
    public function handle(MessageReceived $event): void
    {
        $this->contactService->updateRecentRecipients(
            $event->message->from,
            $event->message->to
        );
    }
}
```

## Data Flow

### Request Flow (Read Message)

```
User clicks message
    ↓
Livewire Component (Inbox)
    ↓ (calls service)
ReadMessageAction
    ↓ (uses interface)
MailProvider::message()
    ↓ (implementation)
ImapSmtpProvider
    ↓ (protocol)
IMAP Server
    ↓ (response)
Message Entity
    ↓ (sanitized)
SanitizedMessageView
    ↓ (rendered)
Blade Template
```

### Request Flow (Send Message)

```
User clicks Send
    ↓
Livewire Component (Composer)
    ↓ (validates)
FormRequest
    ↓ (calls action)
SendMessageAction
    ↓ (builds message)
OutgoingMessage Entity
    ↓ (uses interface)
MailProvider::send()
    ↓ (implementation)
ImapSmtpProvider
    ↓ (protocol)
SMTP Server
    ↓ (confirmation)
SentMessageView
```

### State Management

```
┌─────────────────────────────────────────────────────────────┐
│                    Server State (Livewire)                    │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ Current  │  │ Selected │  │ Compose  │  │ Search   │   │
│  │ Folder   │  │ Message  │  │ Draft    │  │ Query    │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
         ↓ (synced via Livewire)
┌─────────────────────────────────────────────────────────────┐
│                    Database State (MySQL)                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐   │
│  │ Sessions │  │ User     │  │ Settings │  │ Contacts │   │
│  │          │  │ Accounts │  │          │  │          │   │
│  └──────────┘  └──────────┘  └──────────┘  └──────────┘   │
└─────────────────────────────────────────────────────────────┘
         ↓ (cached)
┌─────────────────────────────────────────────────────────────┐
│                    Cache State (File-based)                   │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐                  │
│  │ Folder   │  │ Message  │  │ User     │                  │
│  │ Metadata │  │ Counts   │  │ Prefs    │                  │
│  └──────────┘  └──────────┘  └──────────┘                  │
└─────────────────────────────────────────────────────────────┘
```

### Key Data Flows

1. **Mailbox Sync:** IMAP → ImapConnection → MailReader → Livewire Component → Blade
   - On-demand: User opens folder → fetch messages from IMAP → render
   - No background worker needed for core flow

2. **Message Send:** Composer → SendMessageAction → SmtpTransport → SMTP Server
   - Draft autosave: Composer → DraftService → Database (temporary) → IMAP (on send)

3. **Search:** SearchQuery → MailSearch Service → IMAP SEARCH (primary) → Database index (future enhancement)

4. **Threading:** Message Headers → ThreadingService → Thread Groups → MailboxView
   - JWZ algorithm for Message-ID/References-based threading
   - Subject normalization as fallback only

## Scaling Considerations

| Scale | Architecture Adjustments |
|-------|--------------------------|
| 1-10 users | Standard monolith, file-based cache, IMAP on-demand |
| 10-100 users | Add database caching for folder metadata, connection pooling |
| 100-1000 users | Consider Redis for sessions/cache, background job queue for indexing |
| 1000+ users | Multi-server deployment, dedicated IMAP proxy, read replicas |

### Scaling Priorities

1. **First bottleneck:** IMAP connection limits. Each user session opens IMAP connections. Mitigate with connection pooling and session reuse.
2. **Second bottleneck:** Memory usage with large mailboxes. Mitigate with lazy loading, pagination, and metadata-only caching.

## Anti-Patterns

### Anti-Pattern 1: Fat Livewire Components

**What people do:** Put business logic, database queries, and IMAP calls directly inside Livewire components.

**Why it's wrong:** Components become untestable, unmaintainable, and tightly coupled to infrastructure. Changing the mail provider requires rewriting UI components.

**Do this instead:** Keep Livewire as a thin presentation layer. Delegate all logic to services and actions.

### Anti-Pattern 2: Storing Mail in Database

**What people do:** Copy every email and attachment into the application database for "reliability."

**Why it's wrong:** Massive storage duplication, sync complexity, the mail server remains source of truth anyway. OpenMail is a client, not a mail server.

**Do this instead:** Store only application metadata (preferences, contacts, search indexes). The IMAP server is the authoritative mailbox store.

### Anti-Pattern 3: Tightly Coupling to IMAP Library

**What people do:** Use php-imap functions directly in controllers and services.

**Why it's wrong:** Impossible to swap providers, hard to test, protocol details leak into business logic.

**Do this instead:** Wrap IMAP operations behind the `MailProvider` interface. All consumers depend on the interface, not the implementation.

### Anti-Pattern 4: Rendering Raw Email HTML

**What people do:** Display email HTML directly without sanitization, trusting the sender.

**Why it's wrong:** XSS attacks, phishing, tracking pixels, malicious JavaScript execution in the user's browser.

**Do this instead:** Always sanitize email HTML through a dedicated sanitizer (HTMLPurify, DOMPurify, or Symfony HTML Sanitizer). Never trust email content.

## Integration Points

### External Services

| Service | Integration Pattern | Notes |
|---------|---------------------|-------|
| IMAP Server | MailProvider interface → ImapConnection | TLS required, connection pooling needed |
| SMTP Server | MailProvider interface → SmtpTransport | STARTTLS/SSL, authentication required |
| MySQL/MariaDB | Eloquent ORM | Application metadata only, not mail storage |
| File System | Laravel Filesystem | Attachments (temp), cache, sessions |

### Internal Boundaries

| Boundary | Communication | Notes |
|----------|---------------|-------|
| Mail ↔ Auth | Service injection | Auth validates credentials against IMAP |
| Mail ↔ Contacts | Domain events | MessageReceived triggers contact update |
| Mail ↔ Settings | Service injection | Settings affect mail behavior |
| Install ↔ All | Middleware guard | Installation lock prevents access before setup |

## Build Order Implications

Based on the architecture, the recommended build order is:

1. **Foundation** — Laravel project, database, auth, MailProvider interface, IMAP/SMTP implementation
   - Everything depends on this foundation
   - MailProvider interface must be designed before any UI work

2. **Mailbox** — Folder navigation, message list, message viewer
   - Depends on MailProvider being functional
   - Core user-facing feature

3. **Composer** — Send, reply, forward, drafts
   - Depends on MailProvider::send()
   - Can be built after mailbox viewing works

4. **Organization** — Threading, search, contacts, labels
   - Depends on message data flowing correctly
   - Enhances the core experience

5. **Security** — HTML sanitization, CSP, rate limiting
   - Should be layered on after core features work
   - Never ship without it

6. **UX Polish** — Mobile, keyboard shortcuts, performance
   - Final refinement layer

## Sources

- Roundcube architecture: DeepWiki (roundcube/roundcubemail) — HIGH confidence
- Roundcube vs SnappyMail vs SOGo: Panelica.com comparison — MEDIUM confidence
- Laravel modular monolith: Multiple community sources (Laracasts, 200OK, WireFuture) — MEDIUM confidence
- Email threading (JWZ algorithm): jwz.org/doc/threading.html — HIGH confidence
- HTML sanitization: HTMLPurify, DOMPurify, Symfony HTML Sanitizer docs — HIGH confidence
- Self-hosted webmail comparison: Panelica.com, FreeBSDSoftware.org — MEDIUM confidence

---
*Architecture research for: OpenMail*
*Researched: 2026-09-05*
