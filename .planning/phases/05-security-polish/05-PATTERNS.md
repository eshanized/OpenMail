# Phase 5: Security & Polish - Pattern Map

**Mapped:** 2026-09-08
**Files analyzed:** 48
**Analogs found:** 42 / 48

---

## File Classification

| New/Modified File | Role | Data Flow | Closest Analog | Match Quality |
|-------------------|------|-----------|----------------|---------------|
| `config/csp.php` | config | request-response | `config/session.php` | role-match |
| `app/Support/Csp/LaravelViteNonceGenerator.php` | utility | request-response | `app/Services/MessageSanitizer.php` | role-match |
| `app/Support/Csp/OpenMailPreset.php` | utility | request-response | `app/Services/MessageSanitizer.php` | role-match |
| `app/Support/Csp/OpenMailReportOnlyPreset.php` | utility | request-response | `app/Support/Csp/OpenMailPreset.php` | exact |
| `app/Http/Controllers/CspReportController.php` | controller | request-response | `app/Http/Controllers/Auth/LoginController.php` | role-match |
| `app/Http/Middleware/SecurityHeaders.php` | middleware | request-response | `app/Http/Middleware/InstallLock.php` | exact |
| `app/Http/Middleware/SsrfProtection.php` | middleware | request-response | `app/Http/Middleware/InstallLock.php` | exact |
| `app/Services/AuditService.php` | service | CRUD | `app/Services/MessageSanitizer.php` | role-match |
| `app/Services/SignatureService.php` | service | CRUD | `app/Services/ComposerService.php` | role-match |
| `app/Console/Commands/PruneAuditLogs.php` | command | batch | `app/Console/Commands/ProcessPendingSends.php` | exact |
| `app/Models/AuditLog.php` | model | CRUD | `app/Models/Setting.php` | role-match |
| `app/Models/Signature.php` | model | CRUD | `app/Models/Contact.php` | role-match |
| `app/Http/Requests/SignatureRequest.php` | request | request-response | `app/Http/Controllers/Auth/LoginController.php` | role-match |
| `app/Http/Requests/SettingsRequest.php` | request | request-response | `app/Http/Controllers/Auth/LoginController.php` | role-match |
| `app/Http/Requests/AttachmentRequest.php` | request | request-response | `app/Livewire/Mailbox/Composer.php` (validation rules) | role-match |
| `app/Livewire/Settings/SettingsPage.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `app/Livewire/Settings/ProfileTab.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `app/Livewire/Settings/MailTab.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `app/Livewire/Settings/AppearanceTab.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `app/Livewire/Settings/SecurityTab.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `app/Livewire/Settings/SignaturesTab.php` | component | request-response | `app/Livewire/Mailbox/Composer.php` | role-match |
| `resources/views/livewire/settings/settings-page.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/livewire/settings/profile-tab.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/livewire/settings/mail-tab.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/livewire/settings/appearance-tab.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/livewire/settings/security-tab.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/livewire/settings/signatures-tab.blade.php` | view | request-response | `resources/views/livewire/mailbox/composer.blade.php` | role-match |
| `resources/views/components/signature-dropdown.blade.php` | component | request-response | `resources/views/components/composer-recipient-chips.blade.php` | role-match |
| `resources/views/components/tiptap-editor.blade.php` | component | request-response | `resources/views/livewire/mailbox/composer.blade.php` (Tiptap section) | role-match |
| `database/migrations/create_audit_logs_table.php` | migration | batch | `database/migrations/..._create_settings_table.php` (implied) | role-match |
| `database/migrations/create_signatures_table.php` | migration | batch | `database/migrations/..._create_contacts_table.php` (implied) | role-match |
| `database/migrations/add_profile_fields_to_users.php` | migration | batch | `database/migrations/..._create_users_table.php` (implied) | role-match |
| `config/openmail.php` | config | request-response | `config/openmail.php` (existing) | exact |
| `config/session.php` | config | request-response | `config/session.php` (existing) | exact |
| `resources/css/app.css` | asset | transform | `resources/css/app.css` (existing) | exact |
| `resources/js/app.js` | asset | transform | `resources/js/app.js` (existing) | exact |
| `resources/views/layouts/app.blade.php` | layout | request-response | `resources/views/layouts/app.blade.php` (existing) | exact |
| `resources/views/layouts/mailbox.blade.php` | layout | request-response | `resources/views/layouts/mailbox.blade.php` (existing) | exact |
| `bootstrap/app.php` | bootstrap | request-response | `bootstrap/app.php` (existing) | exact |
| `app/Providers/AppServiceProvider.php` | provider | request-response | `app/Providers/AppServiceProvider.php` (existing) | exact |
| `app/Http/Controllers/Auth/LoginController.php` | controller | request-response | `app/Http/Controllers/Auth/LoginController.php` (existing) | exact |
| `app/Http/Controllers/Auth/LogoutController.php` | controller | request-response | `app/Http/Controllers/Auth/LogoutController.php` (existing) | exact |
| `app/Services/MessageSanitizer.php` | service | transform | `app/Services/MessageSanitizer.php` (existing) | exact |
| `app/Services/ComposerService.php` | service | CRUD | `app/Services/ComposerService.php` (existing) | exact |
| `app/Models/User.php` | model | CRUD | `app/Models/User.php` (existing) | exact |
| `app/Models/Setting.php` | model | CRUD | `app/Models/Setting.php` (existing) | exact |
| `routes/web.php` | route | request-response | `routes/web.php` (existing) | exact |
| `config/logging.php` | config | request-response | `config/logging.php` (existing) | exact |

---

## Pattern Assignments

### `config/csp.php` (config, request-response)

**Analog:** `config/session.php`

**Config structure pattern** (lines 1-217 of session.php):
```php
<?php

use Illuminate\Support\Str;

return [
    'driver' => env('SESSION_DRIVER', 'database'),
    'lifetime' => (int) env('SESSION_LIFETIME', 120),
    'encrypt' => env('SESSION_ENCRYPT', false),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'same_site' => env('SESSION_SAME_SITE', 'lax'),
    // ... more env-driven config with sensible defaults
];
```

**Apply to CSP config:**
- Use `env()` for all settings with sensible defaults
- Support `CSP_ENABLED`, `CSP_REPORT_ONLY`, `CSP_REPORT_URI` env vars
- Return array with `enabled`, `report_only`, `report_uri`, `nonce_generator`, `presets`, `report_only_presets`

---

### `app/Support/Csp/LaravelViteNonceGenerator.php` (utility, request-response)

**Analog:** `app/Services/MessageSanitizer.php` (service pattern with lazy initialization)

**Imports pattern** (lines 1-7):
```php
<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
```

**Core pattern - implement interface with single method** (lines 12-16, 54-57):
```php
class MessageSanitizer
{
    private ?HTMLPurifier $purifier = null;

    private function getPurifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }
        // ... configure and return
    }

    public function sanitizeHtml(string $html): string
    {
        return $this->getPurifier()->purify($html);
    }
}
```

**Apply to NonceGenerator:**
```php
<?php

namespace App\Support\Csp;

use Illuminate\Support\Facades\Vite;
use Spatie\Csp\Nonce\NonceGenerator;

class LaravelViteNonceGenerator implements NonceGenerator
{
    public function generate(): string
    {
        return Vite::useCspNonce();
    }
}
```

---

### `app/Support/Csp/OpenMailPreset.php` (utility, request-response)

**Analog:** `app/Services/MessageSanitizer.php` (configuration builder pattern)

**Config builder pattern** (lines 18-48):
```php
$config = HTMLPurifier_Config::createDefault();
$config->set('HTML.Doctype', 'HTML 4.01 Transitional');
$config->set('HTML.AllowedElements', [...]);
$config->set('HTML.AllowedAttributes', [...]);
$config->set('CSS.AllowedProperties', [...]);
$config->set('URI.AllowedSchemes', ['http', 'https', 'mailto']);
```

**Apply to CSP Preset (implements `Spatie\Csp\Preset`):**
```php
<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

class OpenMailPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE)  // Livewire 3 fallback
            ->add(Directive::STYLE, Keyword::SELF)
            ->addNonce(Directive::STYLE)
            ->add(Directive::STYLE, Keyword::UNSAFE_INLINE)   // Tailwind/Alpine fallback
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'https:')
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FONT, 'fonts.gstatic.com')
            ->add(Directive::MEDIA, Keyword::SELF);
    }
}
```

---

### `app/Http/Controllers/CspReportController.php` (controller, request-response)

**Analog:** `app/Http/Controllers/Auth/LoginController.php`

**Imports pattern** (lines 1-6):
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
```

**Controller structure** (lines 8-18):
```php
class LoginController extends Controller
{
    public function show(Request $request)
    {
        if (!file_exists(storage_path('installed'))) {
            return redirect('/install');
        }
        return view('auth.login');
    }
}
```

**Apply to CSP Report Controller (with logging pattern from AppServiceProvider lines 59-73):**
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CspReportController extends Controller
{
    public function store(Request $request)
    {
        $report = $request->json()->all();
        
        Log::channel('security')->warning('CSP Violation', [
            'blocked_uri' => $report['csp-report']['blocked-uri'] ?? null,
            'violated_directive' => $report['csp-report']['violated-directive'] ?? null,
            'source_file' => $report['csp-report']['source-file'] ?? null,
            'line_number' => $report['csp-report']['line-number'] ?? null,
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
        ]);
        
        return response()->noContent(204);
    }
}
```

---

### `app/Http/Middleware/SecurityHeaders.php` (middleware, request-response)

**Analog:** `app/Http/Middleware/InstallLock.php` (exact match - middleware structure)

**Middleware structure** (lines 1-30):
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallLock
{
    public function handle(Request $request, Closure $next): Response
    {
        // ... logic
        return $next($request);
    }
}
```

**Apply to SecurityHeaders:**
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // SEC-10: Security headers
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        
        if ($request->isSecure() || config('app.force_https')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        
        return $response;
    }
}
```

---

### `app/Http/Middleware/SsrfProtection.php` (middleware, request-response)

**Analog:** `app/Http/Middleware/InstallLock.php` (exact match)

**Apply same middleware structure with URL validation logic from RESEARCH.md Pattern 5 (lines 894-931):**
```php
private function isDangerousUrl(string $url): bool
{
    // Block javascript:, data:, vbscript:, file: schemes
    if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
        return true;
    }
    // Block localhost/private IPs (SSRF prevention)
    if (preg_match('/^https?:\/\/(localhost|127\.|10\.|192\.168\.|169\.254\.|\[::1\])/i', $url)) {
        return true;
    }
    return false;
}
```

---

### `app/Services/AuditService.php` (service, CRUD)

**Analog:** `app/Services/MessageSanitizer.php` (service pattern with static methods)

**Static method pattern** (lines 54-62):
```php
public function sanitizeHtml(string $html): string
{
    return $this->getPurifier()->purify($html);
}

public function sanitizeText(string $text): string
{
    return e($text);
}
```

**Apply with explicit logging pattern from RESEARCH.md (lines 372-426):**
```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public static function log(
        string $eventType,
        string $description,
        array $metadata = [],
        ?Request $request = null
    ): void {
        $request = $request ?? request();
        $user = Auth::user();
        
        AuditLog::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'description' => $description,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }
    
    // Convenience methods matching SEC-05 scope (D-06)
    public static function loginSuccess(?Request $request = null): void {
        self::log('auth.login.success', 'User logged in successfully', [], $request);
    }
    
    public static function loginFailed(string $email, string $reason, ?Request $request = null): void {
        self::log('auth.login.failed', "Login failed: {$reason}", ['email' => $email], $request);
    }
    
    public static function lockout(string $email, ?Request $request = null): void {
        self::log('auth.lockout', 'Account locked due to failed attempts', ['email' => $email], $request);
    }
    
    public static function send(string $action, array $metadata = [], ?Request $request = null): void {
        self::log("mail.send.{$action}", "Message {$action}", $metadata, $request);
    }
}
```

---

### `app/Services/SignatureService.php` (service, CRUD)

**Analog:** `app/Services/ComposerService.php` (service with dependency injection)

**Constructor injection pattern** (lines 16-19):
```php
public function __construct(
    protected ImapMailboxService $imapService,
    protected MessageSanitizer $messageSanitizer
) {}
```

**CRUD methods pattern** (lines 28-84, 93-140, 150-158, 167-192):
```php
public function buildMimeMessage(array $data, ?string $userId = null): Email { ... }
public function sendMessage(int $userId, array $data): array { ... }
public function saveDraft(int $userId, array $data, ?string $existingDraftUid = null): array { ... }
public function undoSend(int $pendingSendId, int $userId): bool { ... }
```

**Apply to SignatureService:**
```php
<?php

namespace App\Services;

use App\Models\Signature;
use App\Models\User;
use App\Services\MessageSanitizer;

class SignatureService
{
    public function __construct(
        protected MessageSanitizer $sanitizer
    ) {}
    
    public function getDefault(User $user): ?Signature {
        return $user->signatures()->where('is_default', true)->first();
    }
    
    public function create(array $data, User $user): Signature {
        return $user->signatures()->create($data);
    }
    
    public function update(Signature $signature, array $data): Signature {
        $signature->update($data);
        return $signature->fresh();
    }
    
    public function delete(Signature $signature): void {
        $wasDefault = $signature->is_default;
        $signature->delete();
        if ($wasDefault) {
            $signature->user->signatures()->latest()->first()?->update(['is_default' => true]);
        }
    }
    
    public function setDefault(Signature $signature): void {
        $signature->user->signatures()->update(['is_default' => false]);
        $signature->update(['is_default' => true]);
    }
}
```

---

### `app/Console/Commands/PruneAuditLogs.php` (command, batch)

**Analog:** `app/Console/Commands/ProcessPendingSends.php`

**Command structure pattern** (check existing command):
```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\AuditLog;

class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune';
    protected $description = 'Prune audit logs older than 90 days';
    
    public function handle(): int
    {
        $deleted = AuditLog::where('created_at', '<', now()->subDays(90))->delete();
        $this->info("Pruned {$deleted} audit log entries older than 90 days.");
        return self::SUCCESS;
    }
}
```

---

### `app/Models/AuditLog.php` (model, CRUD)

**Analog:** `app/Models/Setting.php` (model with static accessor methods)

**Model structure** (lines 1-40):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;
    
    protected $fillable = ['key', 'value', 'group'];
    protected $casts = ['value' => 'array'];
    
    public static function get(string $key, mixed $default = null): mixed { ... }
    public static function set(string $key, mixed $value, string $group = 'general'): void { ... }
}
```

**Apply to AuditLog:**
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'user_id', 'event_type', 'description', 
        'ip', 'user_agent', 'metadata'
    ];
    
    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

### `app/Models/Signature.php` (model, CRUD)

**Analog:** `app/Models/Contact.php` (model with user relationship)

**Model with user relation and boot method** (check Contact model pattern):
```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Signature extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'content_json', 'content_html', 'is_default'];
    protected $casts = ['content_json' => 'array', 'is_default' => 'boolean'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    protected static function booted(): void
    {
        static::saving(function ($signature) {
            // Sanitize HTML before save (reuse MessageSanitizer)
            $sanitizer = app(\App\Services\MessageSanitizer::class);
            $editor = new \Tiptap\Editor();
            $html = $editor->setContent($signature->content_json)->getHTML();
            $signature->content_html = $sanitizer->sanitizeHtml($html);
            
            // Enforce single default per user
            if ($signature->is_default) {
                static::where('user_id', $signature->user_id)
                    ->where('id', '!=', $signature->id)
                    ->update(['is_default' => false]);
            }
        });
    }
}
```

---

### `app/Http/Requests/SignatureRequest.php` (request, request-response)

**Analog:** `app/Livewire/Mailbox/Composer.php` validation rules (lines 42-47)

**Validation rules pattern:**
```php
protected $rules = [
    'to' => 'required|email',
    'subject' => 'required|max:255',
    'body' => 'required',
    'attachments.*' => 'file|max:25600',
];
```

**Apply to SignatureRequest:**
```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'content_json' => 'required|array',
            'is_default' => 'boolean',
        ];
    }
}
```

---

### `app/Http/Requests/SettingsRequest.php` (request, request-response)

**Analog:** `app/Livewire/Mailbox/Composer.php` validation rules

**Apply with theme/density validation:**
```php
public function rules(): array
{
    return [
        'theme' => 'required|in:light,dark,system',
        'density' => 'required|in:compact,regular,comfortable',
        'page_size' => 'required|integer|min:10|max:100',
        'default_folder' => 'string|max:255',
        'reply_behavior' => 'required|in:reply,reply_all',
    ];
}
```

---

### `app/Http/Requests/AttachmentRequest.php` (request, request-response)

**Analog:** `app/Livewire/Mailbox/Composer.php` attachment validation (lines 229-262)

**MIME validation pattern:**
```php
$allowedMimes = [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp',
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    // ... more types
];

foreach ($this->attachments as $attachment) {
    $mime = $attachment->getMimeType();
    if (!in_array($mime, $allowedMimes)) {
        $this->addError('attachments', "File type not allowed: {$mime}");
    }
}
```

**Apply using Laravel File validation (RESEARCH.md lines 871-892):**
```php
use Illuminate\Validation\Rules\File;

public function rules(): array
{
    return [
        'attachment' => [
            'required',
            File::types(config('openmail.attachments.allowed_mimes'))
                ->min(1)
                ->max(config('openmail.attachments.max_size') / 1024),
        ],
    ];
}
```

---

### `app/Livewire/Settings/SettingsPage.php` (component, request-response)

**Analog:** `app/Livewire/Mailbox/Composer.php` (Livewire component with modal, state management)

**Component structure** (lines 12-41):
```php
class Composer extends Component
{
    use WithFileUploads;
    
    public bool $isOpen = false;
    public string $mode = 'compose';
    public string $to = '';
    // ... more public properties
    
    protected $listeners = [
        'openComposer' => 'openComposer',
    ];
    
    protected $rules = [ ... ];
    
    public function mount(): void { ... }
    public function openComposer(array $params = []): void { ... }
    public function send(): void { ... }
    public function render() { return view('livewire.mailbox.composer'); }
}
```

**Apply to SettingsPage (tabbed container):**
```php
<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.mailbox')]
class SettingsPage extends Component
{
    public string $activeTab = 'profile';
    public array $tabs = [
        'profile' => 'Profile',
        'mail' => 'Mail',
        'appearance' => 'Appearance',
        'security' => 'Security',
        'signatures' => 'Signatures',
    ];

    public function setTab(string $tab): void
    {
        if (array_key_exists($tab, $this->tabs)) {
            $this->activeTab = $tab;
        }
    }

    public function render()
    {
        return view('livewire.settings.settings-page', [
            'activeTab' => $this->activeTab,
        ]);
    }
}
```

---

### `app/Livewire/Settings/ProfileTab.php` et al. (component, request-response)

**Analog:** `app/Livewire/Mailbox/Composer.php` (Livewire component pattern)

**Each tab component follows same pattern:**
```php
<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Validate;

class ProfileTab extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';
    
    #[Validate('required|email|max:255')]
    public string $email = '';
    
    public function mount(): void
    {
        $this->name = auth()->user()->name;
        $this->email = auth()->user()->email;
    }
    
    public function save(): void
    {
        $this->validate();
        auth()->user()->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);
        $this->dispatch('toast', 'Profile updated', 'success');
    }
    
    public function render()
    {
        return view('livewire.settings.profile-tab');
    }
}
```

---

### `resources/views/livewire/settings/settings-page.blade.php` (view, request-response)

**Analog:** `resources/views/livewire/mailbox/composer.blade.php` (modal with Alpine.js, Livewire integration)

**Alpine.js + Livewire integration pattern** (lines 2-156):
```blade
<div
    x-data="{
        open: false,
        init() {
            this.$watch('$wire.isOpen', (value) => { this.open = value; });
            Livewire.on('event-name', (data) => { ... });
        },
        destroy() { ... }
    }"
    x-show="open"
    class="fixed inset-0 z-40"
    wire:ignore.self
>
```

**Tab navigation pattern** (from RESEARCH.md lines 969-1010):
```blade
<div class="max-w-4xl mx-auto">
    {{-- Tab navigation --}}
    <nav class="mb-6 border-b border-gray-200" role="tablist">
        @foreach($tabs as $key => $label)
            <button
                role="tab"
                wire:click="setTab('{{ $key }}')"
                class="px-4 py-3 border-b-2 font-medium text-sm transition-colors
                    {{ $activeTab === $key 
                        ? 'border-blue-500 text-blue-600' 
                        : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                :aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- Tab panels --}}
    <div role="tabpanel">
        @switch($activeTab)
            @case('profile')
                @livewire('settings.profile-tab')
                @break
            @case('mail')
                @livewire('settings.mail-tab')
                @break
            @case('appearance')
                @livewire('settings.appearance-tab')
                @break
            @case('security')
                @livewire('settings.security-tab')
                @break
            @case('signatures')
                @livewire('settings.signatures-tab')
                @break
        @endswitch
    </div>
</div>
```

---

### `resources/views/components/signature-dropdown.blade.php` (component, request-response)

**Analog:** `resources/views/components/composer-recipient-chips.blade.php` (Blade component with Alpine.js)

**Component structure:**
```blade
<div x-data="{
    open: false,
    signatures: @entangle('signatures'),
    defaultSignature: @entangle('defaultSignature'),
    selectSignature(id) {
        this.$wire.setSignature(id);
        this.open = false;
    }
}">
    <button @click="open = !open" class="flex items-center gap-2 px-3 py-1.5 border rounded">
        <span x-text="defaultSignature?.name || 'No signature'"></span>
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
    </button>
    
    <div x-show="open" x-transition class="absolute z-10 mt-1 w-56 bg-white border rounded shadow-lg">
        <template x-for="sig in signatures" :key="sig.id">
            <button
                @click="selectSignature(sig.id)"
                class="w-full px-4 py-2 text-left hover:bg-gray-100 flex items-center gap-2"
            >
                <span x-text="sig.name"></span>
                <span x-show="sig.is_default" class="text-xs text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded">Default</span>
            </button>
        </template>
        <hr class="my-1">
        <button @click="$wire.openSignatureModal()" class="w-full px-4 py-2 text-left text-blue-600 hover:bg-blue-50">
            Manage signatures…
        </button>
    </div>
</div>
```

---

### `database/migrations/create_audit_logs_table.php` (migration, batch)

**Analog:** Laravel standard migration pattern (check existing migrations)

**Migration pattern with indexes for 90-day prune:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 100)->index();
            $table->text('description');
            $table->ipAddress('ip')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
            
            // Composite index for pruning + user queries
            $table->index(['user_id', 'created_at']);
            $table->index(['event_type', 'created_at']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

---

### `database/migrations/create_signatures_table.php` (migration, batch)

**Migration with default enforcement index:**
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->json('content_json');
            $table->text('content_html')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['user_id', 'is_default']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('signatures');
    }
};
```

---

### `config/openmail.php` (config, request-response) — EXTEND

**Existing pattern** (lines 1-69):
```php
return [
    'imap' => [ ... ],
    'smtp' => [ ... ],
    'default_encryption' => 'ssl',
    'undo_send_delay' => env('OPENMAIL_UNDO_SEND_DELAY', 10),
    // ...
];
```

**Add to config:**
```php
'attachments' => [
    'allowed_mimes' => [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'text/csv',
        'application/zip', 'application/x-rar-compressed', 'application/x-7z-compressed',
    ],
    'max_size' => 25 * 1024 * 1024,
],

'security' => [
    'csp_enabled' => env('CSP_ENABLED', false),
    'csp_report_only' => env('CSP_REPORT_ONLY', true),
    'csp_report_uri' => env('CSP_REPORT_URI', '/csp-report'),
    'session_timeout' => env('SESSION_LIFETIME', 120),
],
```

---

### `config/session.php` (config, request-response) — EXTEND

**Existing pattern** (lines 21-202):
```php
'lifetime' => (int) env('SESSION_LIFETIME', 120),
'expire_on_close' => env('SESSION_EXPIRE_ON_CLOSE', false),
'secure' => env('SESSION_SECURE_COOKIE'),
'same_site' => env('SESSION_SAME_SITE', 'lax'),
```

**Add/verify for SEC-06:**
```php
'lifetime' => (int) env('SESSION_LIFETIME', 120),
'expire_on_close' => true,  // SEC-06: expire on browser close
'secure' => env('SESSION_SECURE_COOKIE', true),  // SEC-06: HTTPS only
'same_site' => 'lax',  // CSRF protection
'http_only' => true,   // XSS protection
```

---

### `resources/css/app.css` (asset, transform) — EXTEND

**Existing pattern** (lines 1-173):
```css
@import 'tailwindcss';

@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';
@source '../../storage/framework/views/*.php';
@source '../**/*.blade.php';
@source '../**/*.js';

@theme {
    --font-sans: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* Tiptap Editor Styles */
.tiptap-editor .ProseMirror { ... }
```

**Add CSS custom properties for density (RESEARCH.md lines 434-494):**
```css
@theme {
    /* Density CSS custom properties */
    --spacing-unit: 1rem;        /* regular (default) */
    --font-size-base: 0.875rem;  /* regular (default) */
    --line-height-base: 1.5;
    --border-radius: 0.375rem;
}

/* Density variants — applied via class on mailbox container */
.density-compact {
    --spacing-unit: 0.5rem;
    --font-size-base: 0.8125rem;
    --line-height-base: 1.4;
}

.density-comfortable {
    --spacing-unit: 1.5rem;
    --font-size-base: 1rem;
    --line-height-base: 1.6;
}

/* Dark mode — Tailwind 4 class strategy (D-10) */
@custom-variant dark (&:where(.dark, .dark *));

/* Density utilities using @apply with CSS vars */
.message-row {
    @apply px-4 py-2;
    padding: var(--spacing-unit) 1rem;
    font-size: var(--font-size-base);
    line-height: var(--line-height-base);
}

/* Density utility classes for Tailwind @apply */
@utility density-compact {
    --spacing-unit: 0.5rem;
    --font-size-base: 0.8125rem;
    --line-height-base: 1.4;
}

@utility density-regular {
    --spacing-unit: 1rem;
    --font-size-base: 0.875rem;
    --line-height-base: 1.5;
}

@utility density-comfortable {
    --spacing-unit: 1.5rem;
    --font-size-base: 1rem;
    --line-height-base: 1.6;
}
```

---

### `resources/js/app.js` (asset, transform) — EXTEND

**Existing pattern** (lines 1-4):
```js
import './bootstrap';
import DOMPurify from 'dompurify';

window.DOMPurify = DOMPurify;
```

**Add theme/density initializer (RESEARCH.md lines 496-572):**
```js
import './bootstrap';
import DOMPurify from 'dompurify';

window.DOMPurify = DOMPurify;

// Theme/Density initializer - runs before body renders to prevent flash
document.addEventListener('DOMContentLoaded', () => {
    const html = document.documentElement;
    const theme = localStorage.getItem('theme') || 'system';
    const density = localStorage.getItem('density') || 'regular';
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    // Apply theme
    if (theme === 'dark' || (theme === 'system' && prefersDark)) {
        html.classList.add('dark');
    } else {
        html.classList.remove('dark');
    }
    
    // Apply density
    html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
    html.classList.add(`density-${density}`);
});
```

---

### `resources/views/layouts/app.blade.php` (layout, request-response) — EXTEND

**Existing pattern** (lines 1-40):
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenMail') }} - {{ $title ?? 'Mailbox' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
```

**Add CSP nonce meta tag and inline theme initializer (RESEARCH.md lines 496-572):**
```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data="themeInitializer()"
      x-init="initTheme()"
      :class="theme === 'dark' || (theme === 'system' && prefersDark) ? 'dark' : ''"
      :class="densityClass">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'OpenMail') }} - {{ $title ?? 'Mailbox' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    {{-- CSP nonce for inline scripts (spatie/laravel-csp injects automatically) --}}
    @cspNonceMetaTag
    
    {{-- Inline theme initializer to prevent flash --}}
    <script @cspNonceAttribute>
        function themeInitializer() {
            return {
                theme: 'system',
                density: 'regular',
                prefersDark: window.matchMedia('(prefers-color-scheme: dark)').matches,
                
                initTheme() {
                    this.theme = @json(auth()->user()?->setting('theme', 'system')) ?? 'system';
                    this.density = @json(auth()->user()?->setting('density', 'regular')) ?? 'regular';
                    this.applyTheme();
                    this.applyDensity();
                },
                
                applyTheme() {
                    const html = document.documentElement;
                    if (this.theme === 'dark' || (this.theme === 'system' && this.prefersDark)) {
                        html.classList.add('dark');
                    } else {
                        html.classList.remove('dark');
                    }
                },
                
                applyDensity() {
                    const html = document.documentElement;
                    html.classList.remove('density-compact', 'density-regular', 'density-comfortable');
                    html.classList.add(`density-${this.density}`);
                },
                
                setTheme(value) {
                    this.theme = value;
                    this.applyTheme();
                    this.persist();
                },
                
                setDensity(value) {
                    this.density = value;
                    this.applyDensity();
                    this.persist();
                },
                
                persist() {
                    @if(auth()->check())
                        @this.call('updateSetting', ['theme' => this.theme, 'density' => this.density])
                    @else
                        localStorage.setItem('theme', this.theme);
                        localStorage.setItem('density', this.density);
                    @endif
                },
                
                get densityClass() {
                    return `density-${this.density}`;
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">
```

---

### `resources/views/layouts/mailbox.blade.php` (layout, request-response) — EXTEND

**Existing pattern** (lines 1-75):
```blade
@extends('layouts.app')

@section('content')
<div class="h-[calc(100vh-4rem)] flex overflow-hidden"
     x-data="{ sidebarOpen: false, openPanel: null, ... }"
     x-init="initGlobalKeyboardShortcuts()">
```

**Add density class binding to main container:**
```blade
<div class="h-[calc(100vh-4rem)] flex overflow-hidden"
     x-data="{ sidebarOpen: false, openPanel: null, ... }"
     x-init="initGlobalKeyboardShortcuts()"
     :class="densityClass">
```

---

### `bootstrap/app.php` (bootstrap, request-response) — EXTEND

**Existing pattern** (lines 1-20):
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\InstallLock::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
```

**Add SecurityHeaders middleware globally:**
```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [
        \App\Http\Middleware\InstallLock::class,
        \App\Http\Middleware\SecurityHeaders::class,
    ]);
})
```

---

### `app/Providers/AppServiceProvider.php` (provider, request-response) — EXTEND

**Existing pattern** (lines 1-91):
```php
public function boot(): void
{
    // Register IMAP auth guard
    Auth::viaRequest('imap', function ($request) { ... });
    
    // Configure rate limiter for login
    RateLimiter::for('login', function ($request) {
        $email = Str::lower($request->input('email', ''));
        return [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perMinutes(5, 5)->by($email),
        ];
    });
}
```

**Add API rate limiters (RESEARCH.md lines 814-848):**
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    // ... existing login limiter ...
    
    // API endpoints: compose, search, settings
    RateLimiter::for('api.compose', function (Request $request) {
        return Limit::perMinute(30)->by($request->user()?->id ?: $request->ip());
    });
    
    RateLimiter::for('api.search', function (Request $request) {
        return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
    });
    
    RateLimiter::for('api.settings', function (Request $request) {
        return Limit::perMinute(120)->by($request->user()?->id ?: $request->ip());
    });
    
    // Authenticated API: stricter per-user limits
    RateLimiter::for('api.authenticated', function (Request $request) {
        return [
            Limit::perMinute(100)->by('minute:'.$request->user()->id),
            Limit::perHour(1000)->by('hour:'.$request->user()->id),
        ];
    });
}
```

---

### `app/Http/Controllers/Auth/LoginController.php` (controller, request-response) — EXTEND

**Existing pattern** (lines 1-19):
```php
public function show(Request $request)
{
    if (!file_exists(storage_path('installed'))) {
        return redirect('/install');
    }
    return view('auth.login');
}
```

**The actual login is in `LoginForm.php` Livewire component. Add audit logging there (lines 40-55):**
```php
if (Auth::guard('imap')->attempt($credentials, $remember)) {
    // Login successful
    $request = request();
    $request->session()->regenerate();
    RateLimiter::clear($throttleKey);
    $request->session()->put('openmail:imap_password', Crypt::encrypt($this->password));
    
    // AUDIT LOG: Login success
    AuditService::loginSuccess($request);
    
    $this->redirect(route('mailbox'), navigate: true);
    return;
}

// Login failed
RateLimiter::hit($throttleKey, $this->calculateDecay());
// AUDIT LOG: Login failed
AuditService::loginFailed($this->email, 'invalid_credentials', $request);
```

**Also add lockout logging when attempts >= 10 (line 71-72):**
```php
if ($attempts >= 10) {
    AuditService::lockout($this->email, $request);
    $this->error = 'Too many failed attempts. Account locked for 15 minutes.';
}
```

---

### `app/Http/Controllers/Auth/LogoutController.php` (controller, request-response) — EXTEND

**Existing pattern** (lines 1-19):
```php
public function logout(Request $request)
{
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
}
```

**Add audit logging and session revocation support:**
```php
public function logout(Request $request)
{
    $user = Auth::user();
    AuditService::log('auth.logout', 'User logged out', [], $request);
    
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    
    return redirect()->route('login');
}

public function revokeOtherSessions(Request $request)
{
    $user = $request->user();
    // Delete all sessions except current
    \DB::table('sessions')
        ->where('user_id', $user->id)
        ->where('id', '!=', $request->session()->getId())
        ->delete();
    
    AuditService::log('auth.sessions.revoked', 'Revoked all other sessions', [], $request);
    
    return back()->with('success', 'All other sessions revoked');
}
```

---

### `app/Services/MessageSanitizer.php` (service, transform) — EXTEND

**Existing pattern** (lines 1-86):
```php
class MessageSanitizer
{
    private ?HTMLPurifier $purifier = null;
    
    private function getPurifier(): HTMLPurifier { ... }
    
    public function sanitizeHtml(string $html): string { ... }
    public function sanitizeText(string $text): string { ... }
    
    public function blockRemoteImages(string $html): string { ... }
}
```

**Add signature sanitization and URL sanitization (RESEARCH.md lines 894-931):**
```php
public function sanitizeSignatureHtml(string $html): string
{
    // Reuse existing purifier config (already blocks scripts, event handlers, dangerous URLs)
    return $this->sanitizeHtml($html);
}

public function sanitizeUrls(string $html): string
{
    $dom = new \DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    foreach ($dom->getElementsByTagName('a') as $link) {
        $href = $link->getAttribute('href');
        if ($href && $this->isDangerousUrl($href)) {
            $link->setAttribute('href', '#');
            $link->setAttribute('data-original-href', $href);
            $link->setAttribute('class', 'link-blocked');
        }
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    return $body ? $dom->saveHTML($body) : $dom->saveHTML();
}

private function isDangerousUrl(string $url): bool
{
    if (preg_match('/^(javascript|data|vbscript|file):/i', $url)) {
        return true;
    }
    if (preg_match('/^https?:\/\/(localhost|127\.|10\.|192\.168\.|169\.254\.|\[::1\])/i', $url)) {
        return true;
    }
    return false;
}
```

---

### `app/Services/ComposerService.php` (service, CRUD) — EXTEND

**Existing pattern** (lines 1-227): Constructor injection, `buildMimeMessage`, `sendMessage`, `saveDraft`, `undoSend`

**Add signature auto-insertion in `buildMimeMessage` or new method:**
```php
public function buildMimeMessage(array $data, ?string $userId = null): Email
{
    $user = $userId ? \App\Models\User::find($userId) : Auth::user();
    
    // Auto-insert default signature if not already present
    if (empty($data['signature_id']) && ($data['mode'] ?? 'compose') === 'compose') {
        $defaultSig = app(\App\Services\SignatureService::class)->getDefault($user);
        if ($defaultSig && $defaultSig->content_html) {
            $data['body'] = ($data['body'] ?? '') . '<br><br>' . $defaultSig->content_html;
        }
    }
    
    // ... rest of existing buildMimeMessage logic
}
```

---

### `app/Models/User.php` (model, CRUD) — EXTEND

**Existing pattern** (lines 1-49):
```php
class User extends Authenticatable
{
    use HasFactory, Notifiable;
    
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
```

**Add relationships and settings accessor:**
```php
protected $fillable = ['name', 'email', 'password'];

public function signatures(): HasMany
{
    return $this->hasMany(Signature::class);
}

public function auditLogs(): HasMany
{
    return $this->hasMany(AuditLog::class);
}

public function setting(string $key, mixed $default = null): mixed
{
    return \App\Models\Setting::where('user_id', $this->id)
        ->where('key', $key)
        ->value('value') ?? $default;
}
```

---

### `app/Models/Setting.php` (model, CRUD) — EXTEND

**Existing pattern** (lines 1-40):
```php
class Setting extends Model
{
    use HasFactory;
    protected $fillable = ['key', 'value', 'group'];
    protected $casts = ['value' => 'array'];
    
    public static function get(string $key, mixed $default = null): mixed { ... }
    public static function set(string $key, mixed $value, string $group = 'general'): void { ... }
}
```

**Add user_id and user-scoped methods:**
```php
protected $fillable = ['user_id', 'key', 'value', 'group'];

public function user(): BelongsTo
{
    return $this->belongsTo(User::class);
}

public static function getForUser(int $userId, string $key, mixed $default = null): mixed
{
    return Cache::remember("setting:user:{$userId}:{$key}", 3600, function () use ($userId, $key, $default) {
        $setting = static::where('user_id', $userId)->where('key', $key)->first();
        return $setting ? $setting->value : $default;
    });
}

public static function setForUser(int $userId, string $key, mixed $value, string $group = 'general'): void
{
    static::updateOrCreate(
        ['user_id' => $userId, 'key' => $key],
        ['value' => $value, 'group' => $group]
    );
    Cache::forget("setting:user:{$userId}:{$key}");
}
```

---

### `routes/web.php` (route, request-response) — EXTEND

**Existing pattern** (lines 1-49):
```php
Route::get('/', function () { ... })->name('home');
Route::get('/install', [SetupController::class, 'show'])->name('install');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/mailbox', function () { ... })->name('mailbox');
    Route::get('/mailbox/{folderPath}/{uid}', ...)->name('message.show');
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');
    // ...
});
```

**Add settings routes and CSP report route:**
```php
Route::post('/csp-report', [\App\Http\Controllers\CspReportController::class, 'store'])
    ->name('csp.report')
    ->middleware('throttle:60,1');

Route::middleware(['auth'])->group(function () {
    // ... existing routes ...
    
    Route::get('/settings', \App\Livewire\Settings\SettingsPage::class)->name('settings');
    Route::post('/settings/revoke-sessions', [\App\Http\Controllers\Auth\LogoutController::class, 'revokeOtherSessions'])
        ->name('settings.revoke-sessions');
    
    // API routes with rate limiting
    Route::middleware(['throttle:api.authenticated'])->group(function () {
        Route::post('/composer/send', [\App\Http\Controllers\ComposerController::class, 'send']);
        Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index']);
        Route::put('/settings', [\App\Http\Controllers\SettingsController::class, 'update']);
    });
});
```

---

### `config/logging.php` (config, request-response) — EXTEND

**Existing pattern** (lines 53-130):
```php
'channels' => [
    'stack' => [ ... ],
    'single' => [ ... ],
    'daily' => [ ... ],
    // ...
],
```

**Add security channel for CSP violations and audit logs:**
```php
'channels' => [
    // ... existing channels ...
    
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'warning',
        'days' => 30,
        'replace_placeholders' => true,
    ],
    
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 90,
        'replace_placeholders' => true,
    ],
],
```

---

## Shared Patterns

### Authentication / Authorization Guard Pattern

**Source:** `app/Providers/AppServiceProvider.php` lines 28-77 (IMAP auth guard)

**Apply to:** All new controllers and Livewire components requiring auth
```php
// In bootstrap/app.php or route middleware
Route::middleware(['auth'])->group(function () {
    // All mailbox, settings, composer routes
});

// For API routes with rate limiting
Route::middleware(['auth', 'throttle:api.authenticated'])->group(function () {
    // API endpoints
});
```

### Rate Limiting Pattern (Database-backed)

**Source:** `app/Providers/AppServiceProvider.php` lines 80-89

**Apply to:** All new API endpoints, login, CSP report endpoint
```php
// Dual-key rate limiting (IP + user)
RateLimiter::for('login', function ($request) {
    $email = Str::lower($request->input('email', ''));
    return [
        Limit::perMinute(10)->by($request->ip()),
        Limit::perMinutes(5, 5)->by($email),
    ];
});

// Per-user API limits
RateLimiter::for('api.authenticated', function (Request $request) {
    return [
        Limit::perMinute(100)->by('minute:'.$request->user()->id),
        Limit::perHour(1000)->by('hour:'.$request->user()->id),
    ];
});

// Apply in routes
Route::middleware(['throttle:api.authenticated'])->group(...);
```

### Error Handling / Logging Pattern

**Source:** `app/Providers/AppServiceProvider.php` lines 59-73 (security logging)

**Apply to:** All services, controllers, CSP report endpoint
```php
use Illuminate\Support\Facades\Log;

Log::channel('security')->info('Event description', [
    'email' => $email,
    'ip' => $request->ip(),
    'metadata' => $additionalData,
]);

Log::channel('security')->warning('Event description', [
    'error' => $e->getMessage(),
    'ip' => $request->ip(),
]);
```

### Validation Pattern (FormRequest + Livewire)

**Source:** `app/Livewire/Mailbox/Composer.php` lines 42-47, 229-262

**Apply to:** All new FormRequest classes and Livewire component validation
```php
// Livewire inline validation
protected $rules = [
    'field' => 'required|string|max:255',
];

public function method(): void
{
    $this->validate();
    // ... logic
}

// FormRequest for controllers
public function rules(): array
{
    return [
        'field' => 'required|string|max:255',
    ];
}
```

### Database Session/Cache Pattern (No Redis)

**Source:** `config/session.php` line 21, `config/cache.php` line 18

**Apply to:** All new features requiring session, cache, or rate limiting storage
```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'database'),

// config/cache.php
'default' => env('CACHE_STORE', 'database'),

// RateLimiter automatically uses cache store
// Ensure CACHE_STORE=database in .env for shared hosting
```

### Tiptap Editor Integration Pattern

**Source:** `resources/js/components/TiptapEditor.js` lines 67-181, `resources/views/livewire/mailbox/composer.blade.php` lines 70-96

**Apply to:** Signature editor in Settings, any new rich text editor
```js
// Initialize with shared config
const { initTiptapEditor, createToolbar } = await import('../../js/components/TiptapEditor.js');

this.tiptapEditor = await initTiptapEditor(editorEl, {
    onUpdate: (html) => {
        this.$wire.syncBodyFromEditor(html);
    },
    initialContent: @js($signatureContent),
});

this.tiptapToolbar = createToolbar(this.tiptapEditor.editor, toolbarEl);
```

### HTML Sanitization Pattern (Dual Pipeline)

**Source:** `app/Services/MessageSanitizer.php` lines 54-57, `resources/js/app.js` lines 2-4

**Apply to:** Signature rendering, email rendering, any user-generated HTML
```php
// Server-side (PHP)
$sanitizer = app(\App\Services\MessageSanitizer::class);
$safeHtml = $sanitizer->sanitizeHtml($userHtml);

// Client-side (JS) - DOMPurify already on window
const cleanHtml = DOMPurify.sanitize(dirtyHtml, { 
    ALLOWED_TAGS: [...], 
    ALLOWED_ATTR: [...] 
});
```

### Livewire Component Pattern (Modal + Alpine.js)

**Source:** `resources/views/livewire/mailbox/composer.blade.php` lines 1-156

**Apply to:** Settings modals (signature editor, confirm dialogs)
```blade
<div
    x-data="{
        open: false,
        init() {
            this.$watch('$wire.property', (value) => { this.open = value; });
        },
        destroy() { ... }
    }"
    x-show="open"
    class="fixed inset-0 z-40"
    wire:ignore.self
>
    <!-- Modal content -->
</div>
```

---

## No Analog Found

| File | Role | Data Flow | Reason |
|------|------|-----------|--------|
| `app/Support/Csp/OpenMailReportOnlyPreset.php` | utility | request-response | No report-only preset exists; extend OpenMailPreset |
| `resources/views/components/tiptap-editor.blade.php` | component | request-response | Tiptap is JS-only in composer.blade.php; new Blade component for reuse |
| `config/csp.php` | config | request-response | No CSP config exists; new file following session.php pattern |

---

## Metadata

**Analog search scope:** `app/`, `config/`, `resources/views/`, `resources/css/`, `resources/js/`, `routes/`, `bootstrap/`, `database/migrations/`
**Files scanned:** ~85 PHP/Blade/JS/CSS files
**Pattern extraction date:** 2026-09-08

---

## Key Patterns Identified

1. **All controllers use Laravel's Controller base class** with explicit `Request` injection and minimal logic (delegate to Services)
2. **Livewire 3 components** use public properties for state, `$listeners` for events, `#[Validate]` or `$rules` for validation, and Alpine.js for client-side interactivity
3. **Services use constructor dependency injection** with typed properties (`protected ServiceName $serviceName`)
4. **Rate limiting** configured in `AppServiceProvider::boot()` using `Illuminate\Cache\RateLimiting\Limit` with database cache driver
5. **Security events logged** to dedicated `security` log channel via `Log::channel('security')`
6. **HTML sanitization** uses HTMLPurifier server-side + DOMPurify client-side (dual pipeline)
7. **Settings stored per-user** in `settings` table with `user_id`, `key`, `value`, `group` and cached for 1 hour
8. **Migrations** use `foreignId()->constrained()->cascadeOnDelete()` for user relationships, composite indexes for query patterns
9. **Middleware** registered globally in `bootstrap/app.php` via `$middleware->web(append: [...])`
10. **Tailwind 4 CSS-first config** with `@theme` for design tokens, `@custom-variant dark` for dark mode, `@utility` for reusable CSS var bundles
11. **Vite asset bundling** with `@vite` directive, CSP nonce via `spatie/laravel-csp` Vite integration
12. **No Redis, no queue workers** — all async via scheduler (cron) or synchronous in request