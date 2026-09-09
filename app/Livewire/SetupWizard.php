<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Install\ConfigurationWriter;
use App\Install\DatabaseInstaller;
use App\Install\InstallationLock;
use App\Install\SecurityConfigurator;
use App\Models\Setting;
use App\Models\User;
use App\Services\ImapConnectionTester;
use App\Services\MailConfigDetector;
use App\Services\SmtpConnectionTester;
use App\Services\SystemRequirementsChecker;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Livewire\Component;

/**
 * SetupWizard
 *
 * An 8-step Livewire wizard guiding the user through a fresh OpenMail
 * installation. Session-resumable; does not expose secrets via URL or
 * client-side storage.
 *
 * Steps:
 *   1 — Welcome
 *   2 — System Requirements
 *   3 — Database
 *   4 — Mail (IMAP/SMTP)
 *   5 — Application Settings
 *   6 — Administrator Account
 *   7 — Security
 *   8 — Verification & Finish
 */
class SetupWizard extends Component
{
    // ── Wizard state ──────────────────────────────────────────────────────────
    public int   $currentStep    = 1;
    public int   $totalSteps     = 8;
    public array $completedSteps = [];

    // ── Step 1: Welcome ───────────────────────────────────────────────────────
    public string $appName     = 'OpenMail';
    public string $installMode = 'fresh'; // 'fresh' | 'continue'

    // ── Step 2: Requirements ──────────────────────────────────────────────────
    public array $requirements       = [];
    public bool  $requirementsLoaded = false;

    // ── Step 3: Database ──────────────────────────────────────────────────────
    public string  $dbHost          = '127.0.0.1';
    public string  $dbPort          = '3306';
    public string  $dbDatabase      = '';
    public string  $dbUsername      = 'root';
    public string  $dbPassword      = '';
    public ?array  $dbTestResult    = null;
    public ?array  $migrationResult = null;
    public bool    $migrationsRan   = false;

    // ── Step 4: Mail ──────────────────────────────────────────────────────────
    public string  $emailDomain      = '';
    public string  $detectedProvider = '';
    public string  $imapHost         = '';
    public int     $imapPort         = 993;
    public string  $imapEncryption   = 'ssl';
    public string  $imapUsername     = '';
    public string  $imapPassword     = '';
    public string  $smtpHost         = '';
    public int     $smtpPort         = 465;
    public string  $smtpEncryption   = 'ssl';
    public string  $smtpUsername     = '';
    public string  $smtpPassword     = '';
    public ?array  $imapTestResult   = null;
    public ?array  $smtpTestResult   = null;

    // ── Step 5: Application settings ─────────────────────────────────────────
    public string $appOrg      = '';
    public string $appDomain   = '';
    public string $appUrl      = '';
    public string $appTimezone = 'UTC';

    // ── Step 6: Administrator ─────────────────────────────────────────────────
    public string $adminName                = '';
    public string $adminEmail               = '';
    public string $adminPassword            = '';
    public string $adminPasswordConfirmation = '';

    // ── Step 7: Security ─────────────────────────────────────────────────────
    public bool $httpsEnabled = false;
    public bool $secureCookies = false;

    // ── Step 8: Verification ─────────────────────────────────────────────────
    public array $verificationResults  = [];
    public bool  $verificationRun      = false;
    public bool  $installationComplete = false;

    // ── Validation rules per step ─────────────────────────────────────────────
    /** @var array<int, array<string, string>> */
    protected array $stepRules = [
        1 => [
            'appName' => 'required|string|min:2|max:100',
        ],
        2 => [],
        3 => [
            'dbHost'     => 'required|string',
            'dbPort'     => 'required|integer|min:1|max:65535',
            'dbDatabase' => 'required|string|min:1',
            'dbUsername' => 'nullable|string',
            'dbPassword' => 'nullable|string',
        ],
        4 => [
            'imapHost'       => 'required|string',
            'imapPort'       => 'required|integer|min:1|max:65535',
            'imapEncryption' => 'required|in:ssl,tls,none',
            'imapUsername'   => 'required|string',
            'imapPassword'   => 'required|string',
            'smtpHost'       => 'required|string',
            'smtpPort'       => 'required|integer|min:1|max:65535',
            'smtpEncryption' => 'required|in:ssl,tls,none',
            'smtpUsername'   => 'required|string',
            'smtpPassword'   => 'required|string',
        ],
        5 => [
            'appOrg'      => 'required|string|min:2|max:100',
            'appDomain'   => 'required|string|min:3|max:255',
            'appTimezone' => 'required|timezone',
        ],
        6 => [
            'adminName'                 => 'required|string|min:2|max:100',
            'adminEmail'                => 'required|email',
            'adminPassword'             => 'required|string|min:8|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/|confirmed',
            'adminPasswordConfirmation' => 'required|string',
        ],
        7 => [],
        8 => [],
    ];

    // ──────────────────────────────────────────────────────────────────────────
    // Lifecycle
    // ──────────────────────────────────────────────────────────────────────────

    public function mount(): void
    {
        $this->restoreFromSession();
        $this->requirements       = app(SystemRequirementsChecker::class)->check();
        $this->requirementsLoaded = true;
        $this->detectInstallMode();

        // Pre-populate from request context
        if (! $this->httpsEnabled) {
            $this->httpsEnabled  = request()->isSecure();
            $this->secureCookies = request()->isSecure();
        }

        if (empty($this->appUrl)) {
            $this->appUrl = rtrim(request()->getSchemeAndHttpHost(), '/');
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Navigation
    // ──────────────────────────────────────────────────────────────────────────

    public function nextStep(): void
    {
        // Step 3 guard: DB must be tested and migrations run
        if ($this->currentStep === 3) {
            if (! $this->dbTestResult || ! $this->dbTestResult['success']) {
                $this->addError('dbConnection', 'Test the database connection successfully before proceeding.');
                return;
            }
            if (! $this->migrationsRan) {
                $this->addError('dbMigrations', 'Run database migrations successfully before proceeding.');
                return;
            }
        }

        $this->validate($this->stepRules[$this->currentStep] ?? []);

        if (! in_array($this->currentStep, $this->completedSteps, true)) {
            $this->completedSteps[] = $this->currentStep;
        }

        $this->currentStep++;
        $this->saveToSession();
    }

    public function previousStep(): void
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
            $this->saveToSession();
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
            $this->saveToSession();
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 3: Database
    // ──────────────────────────────────────────────────────────────────────────

    public function testDatabaseConnection(): void
    {
        $this->validate([
            'dbHost'     => 'required|string',
            'dbPort'     => 'required|integer|min:1|max:65535',
            'dbDatabase' => 'required|string|min:1',
            'dbUsername' => 'nullable|string',
            'dbPassword' => 'nullable|string',
        ]);

        $this->dbTestResult   = app(DatabaseInstaller::class)->testConnection(
            $this->dbHost,
            (int) $this->dbPort,
            $this->dbDatabase,
            $this->dbUsername,
            $this->dbPassword,
        );

        // Reset migration state whenever connection config changes
        $this->migrationsRan   = false;
        $this->migrationResult = null;

        $this->saveToSession();
    }

    public function runMigrations(): void
    {
        if (! $this->dbTestResult || ! $this->dbTestResult['success']) {
            $this->addError('dbConnection', 'Test the database connection successfully first.');
            return;
        }

        // Write DB config to .env so the default connection picks it up
        $this->writeDbConfigToEnv();
        Artisan::call('config:clear');

        $this->migrationResult = app(DatabaseInstaller::class)->runMigrations();
        $this->migrationsRan   = $this->migrationResult['success'];

        $this->saveToSession();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 4: Mail
    // ──────────────────────────────────────────────────────────────────────────

    public function updatedEmailDomain(): void
    {
        if (! empty($this->emailDomain) && str_contains($this->emailDomain, '@')) {
            $detected = app(MailConfigDetector::class)->detect($this->emailDomain);

            if ($detected) {
                $this->detectedProvider = $detected['name'];
                $this->imapHost         = $detected['imap_host'];
                $this->imapPort         = $detected['imap_port'];
                $this->imapEncryption   = $detected['imap_encryption'];
                $this->smtpHost         = $detected['smtp_host'];
                $this->smtpPort         = $detected['smtp_port'];
                $this->smtpEncryption   = $detected['smtp_encryption'];
                $this->imapUsername     = $this->emailDomain;
                $this->smtpUsername     = $this->emailDomain;
            }
        }
    }

    public function testImapConnection(): void
    {
        $this->validate([
            'imapHost'       => 'required|string',
            'imapPort'       => 'required|integer|min:1|max:65535',
            'imapEncryption' => 'required|in:ssl,tls,none',
            'imapUsername'   => 'required|string',
            'imapPassword'   => 'required|string',
        ]);

        $this->imapTestResult = app(ImapConnectionTester::class)->test(
            $this->imapHost,
            (int) $this->imapPort,
            $this->imapEncryption,
            $this->imapUsername,
            $this->imapPassword,
        );

        $this->saveToSession();
    }

    public function testSmtpConnection(): void
    {
        $this->validate([
            'smtpHost'       => 'required|string',
            'smtpPort'       => 'required|integer|min:1|max:65535',
            'smtpEncryption' => 'required|in:ssl,tls,none',
            'smtpUsername'   => 'required|string',
            'smtpPassword'   => 'required|string',
        ]);

        $this->smtpTestResult = app(SmtpConnectionTester::class)->test(
            $this->smtpHost,
            (int) $this->smtpPort,
            $this->smtpEncryption,
            $this->smtpUsername,
            $this->smtpPassword,
        );

        $this->saveToSession();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Step 8: Verification
    // ──────────────────────────────────────────────────────────────────────────

    public function runVerification(): void
    {
        $checks = [];

        // 1. APP_KEY
        $appKey = config('app.key');
        $hasKey = ! empty($appKey) && strlen($appKey) > 10;
        $checks[] = [
            'name'    => 'app_key',
            'label'   => 'Application key configured',
            'passed'  => $hasKey,
            'error'   => $hasKey ? null : 'APP_KEY is not set. It will be generated automatically.',
            'fixable' => false,
            'fixStep' => null,
        ];

        // 2. Database connection
        $dbPassed = false;
        $dbError  = null;
        try {
            DB::connection()->getPdo();
            $dbPassed = true;
        } catch (\Exception $e) {
            $dbError = 'Database connection failed: ' . $e->getMessage();
        }
        $checks[] = [
            'name'    => 'db_connection',
            'label'   => 'Database connected',
            'passed'  => $dbPassed,
            'error'   => $dbError,
            'fixable' => true,
            'fixStep' => 3,
        ];

        // 3. Migrations
        $checks[] = [
            'name'    => 'migrations',
            'label'   => 'Database tables created',
            'passed'  => $this->migrationsRan,
            'error'   => $this->migrationsRan ? null : 'Migrations have not been run. Return to the Database step.',
            'fixable' => true,
            'fixStep' => 3,
        ];

        // 4. IMAP
        if (! empty($this->imapUsername) && ! empty($this->imapPassword)) {
            $imapResult = app(ImapConnectionTester::class)->test(
                $this->imapHost,
                (int) $this->imapPort,
                $this->imapEncryption,
                $this->imapUsername,
                $this->imapPassword,
            );
            $checks[] = [
                'name'      => 'imap_connection',
                'label'     => 'IMAP (incoming mail) connected',
                'passed'    => $imapResult['success'],
                'error'     => $imapResult['success'] ? null : $imapResult['error'],
                'technical' => $imapResult['technical'] ?? null,
                'fixable'   => true,
                'fixStep'   => 4,
            ];
        } else {
            $checks[] = [
                'name'    => 'imap_connection',
                'label'   => 'IMAP (incoming mail) connected',
                'passed'  => false,
                'error'   => 'IMAP credentials not configured.',
                'fixable' => true,
                'fixStep' => 4,
            ];
        }

        // 5. SMTP
        if (! empty($this->smtpUsername) && ! empty($this->smtpPassword)) {
            $smtpResult = app(SmtpConnectionTester::class)->test(
                $this->smtpHost,
                (int) $this->smtpPort,
                $this->smtpEncryption,
                $this->smtpUsername,
                $this->smtpPassword,
            );
            $checks[] = [
                'name'      => 'smtp_connection',
                'label'     => 'SMTP (outgoing mail) connected',
                'passed'    => $smtpResult['success'],
                'error'     => $smtpResult['success'] ? null : $smtpResult['error'],
                'technical' => $smtpResult['technical'] ?? null,
                'fixable'   => true,
                'fixStep'   => 4,
            ];
        } else {
            $checks[] = [
                'name'    => 'smtp_connection',
                'label'   => 'SMTP (outgoing mail) connected',
                'passed'  => false,
                'error'   => 'SMTP credentials not configured.',
                'fixable' => true,
                'fixStep' => 4,
            ];
        }

        // 6. Filesystem
        $storagePath    = storage_path();
        $bootstrapPath  = base_path('bootstrap/cache');
        $storageOk      = is_writable($storagePath);
        $bootstrapOk    = is_writable($bootstrapPath);
        $fsOk           = $storageOk && $bootstrapOk;
        $checks[] = [
            'name'    => 'filesystem',
            'label'   => 'Filesystem writable',
            'passed'  => $fsOk,
            'error'   => $fsOk ? null : sprintf(
                'Directories not writable: %s',
                implode(', ', array_filter([
                    $storageOk   ? null : 'storage/',
                    $bootstrapOk ? null : 'bootstrap/cache/',
                ])),
            ),
            'fixable' => false,
            'fixStep' => null,
        ];

        // 7. Admin account ready
        $adminReady = ! empty($this->adminEmail) && ! empty($this->adminPassword);
        $checks[] = [
            'name'    => 'admin_account',
            'label'   => 'Administrator account ready',
            'passed'  => $adminReady,
            'error'   => $adminReady ? null : 'Administrator credentials not provided.',
            'fixable' => true,
            'fixStep' => 6,
        ];

        $this->verificationResults = $checks;
        $this->verificationRun     = true;
        $this->saveToSession();
    }

    public function getAllVerificationPassedProperty(): bool
    {
        return ! empty($this->verificationResults)
            && collect($this->verificationResults)->every(fn ($c) => $c['passed']);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Finish
    // ──────────────────────────────────────────────────────────────────────────

    /** @return mixed Livewire redirect or null */
    public function finish(): mixed
    {
        if (! $this->verificationRun) {
            $this->runVerification();
        }

        if (! $this->getAllVerificationPassedProperty()) {
            session()->flash('error', 'Some verification checks failed. Fix the issues above before finishing.');
            return null;
        }

        $lock = app(InstallationLock::class);
        $lock->markInstalling();

        try {
            // Persist configuration
            $this->writeAppConfigToEnv();
            $this->writeMailConfigToEnv();
            $this->writeSecurityConfigToEnv();

            // Ensure APP_KEY is set (won't overwrite existing)
            app(SecurityConfigurator::class)->ensureAppKey();

            Artisan::call('config:clear');

            // Create administrator (idempotent)
            if (! User::where('email', $this->adminEmail)->exists()) {
                User::create([
                    'name'     => $this->adminName ?: explode('@', $this->adminEmail)[0],
                    'email'    => $this->adminEmail,
                    'password' => Hash::make($this->adminPassword),
                ]);
            }

            // Persist application settings
            Setting::set('app_name',        $this->appName);
            Setting::set('app_org',         $this->appOrg);
            Setting::set('app_domain',      $this->appDomain);
            Setting::set('app_timezone',    $this->appTimezone);
            Setting::set('installed_at',    now()->toIso8601String());
            Setting::set('installed_version', '1.0.0');

            // Lock the installation
            $lock->markInstalled([
                'version'     => '1.0.0',
                'org'         => $this->appOrg,
                'admin_email' => $this->adminEmail,
            ]);

            $this->installationComplete = true;

            // Clean up wizard session
            Session::forget($this->sessionKey());

            return $this->redirectRoute('login');
        } catch (\Throwable $e) {
            $lock->markFailed($e->getMessage());
            session()->flash('error', 'Installation failed: ' . $e->getMessage());
            return null;
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Session management
    // ──────────────────────────────────────────────────────────────────────────

    public function continueFromSession(): void
    {
        // Session already restored in mount() — just persist current state
        $this->saveToSession();
    }

    public function resetWizard(): void
    {
        Session::forget($this->sessionKey());
        $this->currentStep         = 1;
        $this->completedSteps      = [];
        $this->dbTestResult        = null;
        $this->migrationResult     = null;
        $this->migrationsRan       = false;
        $this->imapTestResult      = null;
        $this->smtpTestResult      = null;
        $this->verificationResults = [];
        $this->verificationRun     = false;
        $this->saveToSession();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // .env writers (delegate to ConfigurationWriter)
    // ──────────────────────────────────────────────────────────────────────────

    private function writeDbConfigToEnv(): void
    {
        app(ConfigurationWriter::class)->write([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST'       => $this->dbHost,
            'DB_PORT'       => $this->dbPort,
            'DB_DATABASE'   => $this->dbDatabase,
            'DB_USERNAME'   => $this->dbUsername,
            'DB_PASSWORD'   => $this->dbPassword,
        ]);
    }

    private function writeMailConfigToEnv(): void
    {
        app(ConfigurationWriter::class)->write([
            'OPENMAIL_IMAP_HOST'       => $this->imapHost,
            'OPENMAIL_IMAP_PORT'       => (string) $this->imapPort,
            'OPENMAIL_IMAP_ENCRYPTION' => $this->imapEncryption,
            'OPENMAIL_IMAP_USERNAME'   => $this->imapUsername,
            'OPENMAIL_IMAP_PASSWORD'   => $this->imapPassword,
            'OPENMAIL_SMTP_HOST'       => $this->smtpHost,
            'OPENMAIL_SMTP_PORT'       => (string) $this->smtpPort,
            'OPENMAIL_SMTP_ENCRYPTION' => $this->smtpEncryption,
            'OPENMAIL_SMTP_USERNAME'   => $this->smtpUsername,
            'OPENMAIL_SMTP_PASSWORD'   => $this->smtpPassword,
        ]);
    }

    private function writeAppConfigToEnv(): void
    {
        app(ConfigurationWriter::class)->write([
            'APP_NAME'     => $this->appName,
            'APP_ENV'      => 'production',
            'APP_URL'      => $this->appUrl ?: ('http://' . ($this->appDomain ?: 'localhost')),
            'APP_TIMEZONE' => $this->appTimezone,
        ]);
    }

    private function writeSecurityConfigToEnv(): void
    {
        app(ConfigurationWriter::class)->write([
            'SESSION_LIFETIME'      => '1440',
            'SESSION_DRIVER'        => 'database',
            'CACHE_STORE'           => 'database',
            'SESSION_SECURE_COOKIE' => $this->secureCookies ? 'true' : 'false',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private function detectInstallMode(): void
    {
        if (app(InstallationLock::class)->isInstalled()) {
            $this->installMode = 'installed';
            return;
        }

        $envPath = base_path('.env');
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath) ?: '';
            if (str_contains($content, 'DB_DATABASE=') && ! preg_match('/^DB_DATABASE=\s*$/m', $content)) {
                $this->installMode = 'continue';
            }
        }
    }

    private function sessionKey(): string
    {
        return 'openmail:setup:wizard';
    }

    /** @return array<string, mixed> */
    private function sessionPayload(): array
    {
        // Credentials are encrypted before storage to protect against
        // SESSION_ENCRYPT=false and database session table compromise.
        $sensitiveKeys = ['dbPassword', 'imapPassword', 'smtpPassword', 'adminPassword', 'adminPasswordConfirmation'];
        $payload = [
            'currentStep'               => $this->currentStep,
            'completedSteps'            => $this->completedSteps,
            'appName'                   => $this->appName,
            'dbHost'                    => $this->dbHost,
            'dbPort'                    => $this->dbPort,
            'dbDatabase'                => $this->dbDatabase,
            'dbUsername'                => $this->dbUsername,
            'dbPassword'                => Crypt::encryptString($this->dbPassword),
            'emailDomain'               => $this->emailDomain,
            'detectedProvider'          => $this->detectedProvider,
            'imapHost'                  => $this->imapHost,
            'imapPort'                  => $this->imapPort,
            'imapEncryption'            => $this->imapEncryption,
            'imapUsername'              => $this->imapUsername,
            'imapPassword'              => Crypt::encryptString($this->imapPassword),
            'smtpHost'                  => $this->smtpHost,
            'smtpPort'                  => $this->smtpPort,
            'smtpEncryption'            => $this->smtpEncryption,
            'smtpUsername'              => $this->smtpUsername,
            'smtpPassword'              => Crypt::encryptString($this->smtpPassword),
            'appOrg'                    => $this->appOrg,
            'appDomain'                 => $this->appDomain,
            'appUrl'                    => $this->appUrl,
            'appTimezone'               => $this->appTimezone,
            'adminName'                 => $this->adminName,
            'adminEmail'                => $this->adminEmail,
            'adminPassword'             => Crypt::encryptString($this->adminPassword),
            'adminPasswordConfirmation' => Crypt::encryptString($this->adminPasswordConfirmation),
            'httpsEnabled'              => $this->httpsEnabled,
            'secureCookies'             => $this->secureCookies,
            'verificationResults'       => $this->verificationResults,
            'verificationRun'           => $this->verificationRun,
            'dbTestResult'              => $this->dbTestResult,
            'migrationResult'           => $this->migrationResult,
            'migrationsRan'             => $this->migrationsRan,
            'imapTestResult'            => $this->imapTestResult,
            'smtpTestResult'            => $this->smtpTestResult,
        ];
        return $payload;
    }

    private function saveToSession(): void
    {
        Session::put($this->sessionKey(), $this->sessionPayload());
    }

    private function restoreFromSession(): void
    {
        $saved = Session::get($this->sessionKey());

        if (! is_array($saved)) {
            return;
        }

        $sensitiveKeys = ['dbPassword', 'imapPassword', 'smtpPassword', 'adminPassword', 'adminPasswordConfirmation'];

        foreach ($saved as $key => $value) {
            if (property_exists($this, $key)) {
                // Decrypt sensitive fields that were encrypted in saveToSession
                if (in_array($key, $sensitiveKeys) && is_string($value)) {
                    try {
                        $this->$key = Crypt::decryptString($value);
                    } catch (\Throwable) {
                        // If decryption fails (e.g., key changed), clear the field
                        $this->$key = '';
                    }
                } else {
                    $this->$key = $value;
                }
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Render
    // ──────────────────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.setup-wizard');
    }
}
