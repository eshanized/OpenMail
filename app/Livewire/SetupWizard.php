<?php

namespace App\Livewire;

use App\Models\Setting;
use App\Models\User;
use App\Services\ImapConnectionTester;
use App\Services\InstallationVerifier;
use App\Services\MailConfigDetector;
use App\Services\SmtpConnectionTester;
use App\Services\SystemRequirementsChecker;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SetupWizard extends Component
{
    public int $currentStep = 1;
    public int $totalSteps = 8;
    public array $completedSteps = [];

    // Step 1: Welcome
    public string $appName = 'OpenMail';
    public string $installMode = 'fresh'; // 'fresh' or 'continue'

    // Step 2: System Requirements
    public array $requirements = [];

    // Step 3: Database Config
    public string $dbHost = '127.0.0.1';
    public string $dbPort = '3306';
    public string $dbDatabase = '';
    public string $dbUsername = '';
    public string $dbPassword = '';
    public ?array $dbTestResult = null;

    // Step 4: Mail Config
    public string $emailDomain = '';
    public string $detectedProvider = '';
    public string $imapHost = '';
    public int $imapPort = 993;
    public string $imapEncryption = 'ssl';
    public string $imapUsername = '';
    public string $imapPassword = '';
    public string $smtpHost = '';
    public int $smtpPort = 465;
    public string $smtpEncryption = 'ssl';
    public string $smtpUsername = '';
    public string $smtpPassword = '';
    public ?array $imapTestResult = null;
    public ?array $smtpTestResult = null;

    // Step 5: App Settings
    public string $appOrg = '';
    public string $appDomain = '';
    public string $appTimezone = 'UTC';

    // Step 6: Admin Account
    public string $adminEmail = '';
    public string $adminPassword = '';
    public string $adminPasswordConfirmation = '';

    // Step 7: Security
    public bool $httpsEnabled = false;
    public bool $secureCookies = false;

    // Step 8: Verify
    public array $verificationResults = [];
    public bool $verificationRun = false;

    protected array $stepRules = [
        1 => [
            'appName' => 'required|string|min:2|max:100',
        ],
        2 => [],
        3 => [
            'dbHost' => 'required|string',
            'dbPort' => 'required|integer|min:1|max:65535',
            'dbDatabase' => 'required|string|min:1',
            'dbUsername' => 'nullable|string',
            'dbPassword' => 'nullable|string',
        ],
        4 => [
            'emailDomain' => 'required|email',
            'imapHost' => 'required|string',
            'imapPort' => 'required|integer|min:1|max:65535',
            'imapEncryption' => 'required|in:ssl,tls,none',
            'imapUsername' => 'required|string',
            'imapPassword' => 'required|string',
            'smtpHost' => 'required|string',
            'smtpPort' => 'required|integer|min:1|max:65535',
            'smtpEncryption' => 'required|in:ssl,tls,none',
            'smtpUsername' => 'required|string',
            'smtpPassword' => 'required|string',
        ],
        5 => [
            'appOrg' => 'required|string|min:2|max:100',
            'appDomain' => 'required|string|min:3|max:255',
            'appTimezone' => 'required|timezone',
        ],
        6 => [
            'adminEmail' => 'required|email',
            'adminPassword' => 'required|string|min:8|regex:/[A-Z]/|regex:/[a-z]/|regex:/[0-9]/|confirmed',
            'adminPasswordConfirmation' => 'required|string',
        ],
        7 => [],
        8 => [],
    ];

    public function mount(): void
    {
        $this->restoreFromSession();
        $this->requirements = app(SystemRequirementsChecker::class)->check();
        $this->detectInstallMode();
    }

    private function detectInstallMode(): void
    {
        $envPath = base_path('.env');
        $installed = file_exists(storage_path('installed'));

        if ($installed) {
            $this->installMode = 'continue';
        } elseif (File::exists($envPath)) {
            $content = File::get($envPath);
            if (str_contains($content, 'DB_DATABASE=') && !str_contains($content, 'DB_DATABASE=$')) {
                $this->installMode = 'continue';
            }
        }
    }

    public function nextStep(): void
    {
        $this->validate($this->stepRules[$this->currentStep] ?? []);

        if (!in_array($this->currentStep, $this->completedSteps)) {
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

    public function testDatabaseConnection(): void
    {
        $this->validate([
            'dbHost' => 'required|string',
            'dbPort' => 'required|integer|min:1|max:65535',
            'dbDatabase' => 'required|string|min:1',
            'dbUsername' => 'nullable|string',
            'dbPassword' => 'nullable|string',
        ]);

        try {
            // Temporarily set database config
            Config::set('database.connections.testing', [
                'driver' => 'mysql',
                'host' => $this->dbHost,
                'port' => $this->dbPort,
                'database' => $this->dbDatabase,
                'username' => $this->dbUsername,
                'password' => $this->dbPassword,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Test connection
            DB::purge('testing');
            $pdo = DB::connection('testing')->getPdo();

            $this->dbTestResult = ['success' => true, 'error' => null];

            // Write to .env
            $this->writeDbConfigToEnv();

            // Run migrations
            Artisan::call('migrate', ['--force' => true, '--path' => 'database/migrations']);

            session()->flash('success', 'Database connected and migrations ran successfully!');
        } catch (\Exception $e) {
            $this->dbTestResult = [
                'success' => false,
                'error' => 'Database connection failed. Please check your credentials.',
                'technical' => [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ],
            ];
        }

        $this->saveToSession();
    }

    public function updatedEmailDomain(): void
    {
        if ($this->emailDomain) {
            $detector = app(MailConfigDetector::class);
            $detected = $detector->detect($this->emailDomain);

            if ($detected) {
                $this->detectedProvider = $detected['name'];
                $this->imapHost = $detected['imap_host'];
                $this->imapPort = $detected['imap_port'];
                $this->imapEncryption = $detected['imap_encryption'];
                $this->smtpHost = $detected['smtp_host'];
                $this->smtpPort = $detected['smtp_port'];
                $this->smtpEncryption = $detected['smtp_encryption'];

                // Pre-fill username/password if email domain matches
                if (str_contains($this->emailDomain, '@')) {
                    $this->imapUsername = $this->emailDomain;
                    $this->smtpUsername = $this->emailDomain;
                }
            }
        }
    }

    public function testImapConnection(): void
    {
        $this->validate([
            'imapHost' => 'required|string',
            'imapPort' => 'required|integer|min:1|max:65535',
            'imapEncryption' => 'required|in:ssl,tls,none',
            'imapUsername' => 'required|string',
            'imapPassword' => 'required|string',
        ]);

        $tester = app(ImapConnectionTester::class);
        $this->imapTestResult = $tester->test(
            $this->imapHost,
            $this->imapPort,
            $this->imapEncryption,
            $this->imapUsername,
            $this->imapPassword
        );

        $this->saveToSession();
    }

    public function testSmtpConnection(): void
    {
        $this->validate([
            'smtpHost' => 'required|string',
            'smtpPort' => 'required|integer|min:1|max:65535',
            'smtpEncryption' => 'required|in:ssl,tls,none',
            'smtpUsername' => 'required|string',
            'smtpPassword' => 'required|string',
        ]);

        $tester = app(SmtpConnectionTester::class);
        $this->smtpTestResult = $tester->test(
            $this->smtpHost,
            $this->smtpPort,
            $this->smtpEncryption,
            $this->smtpUsername,
            $this->smtpPassword
        );

        $this->saveToSession();
    }

    public function runVerification(): void
    {
        $config = [
            'imapHost' => $this->imapHost,
            'imapPort' => $this->imapPort,
            'imapEncryption' => $this->imapEncryption,
            'imapUsername' => $this->imapUsername,
            'imapPassword' => $this->imapPassword,
            'smtpHost' => $this->smtpHost,
            'smtpPort' => $this->smtpPort,
            'smtpEncryption' => $this->smtpEncryption,
            'smtpUsername' => $this->smtpUsername,
            'smtpPassword' => $this->smtpPassword,
        ];

        $verifier = app(InstallationVerifier::class);
        $this->verificationResults = $verifier->verify($config);
        $this->verificationRun = true;
        $this->saveToSession();
    }

    public function finish(): void
    {
        $this->runVerification();

        // Check if all verifications passed
        $allPassed = collect($this->verificationResults)->every(fn($check) => $check['passed']);

        if (!$allPassed) {
            session()->flash('error', 'Some verification checks failed. Please fix the issues before finishing.');
            return;
        }

        // Create admin user
        User::create([
            'name' => explode('@', $this->adminEmail)[0],
            'email' => $this->adminEmail,
            'password' => Hash::make($this->adminPassword),
        ]);

        // Save app settings
        Setting::set('app_name', $this->appName);
        Setting::set('app_org', $this->appOrg);
        Setting::set('app_domain', $this->appDomain);
        Setting::set('app_timezone', $this->appTimezone);

        // Write security config to .env
        $this->writeSecurityConfigToEnv();

        // Write mail config to .env
        $this->writeMailConfigToEnv();

        // Create lock file with flock for atomicity
        $lockFile = storage_path('installed');
        $fp = fopen($lockFile, 'c+');
        if (flock($fp, LOCK_EX)) {
            ftruncate($fp, 0);
            fwrite($fp, now()->toIso8601String());
            flock($fp, LOCK_UN);
        }
        fclose($fp);

        // Clear wizard session
        Session::forget('openmail:wizard');

        // Redirect to login
        return redirect()->route('login');
    }

    public function continueFromSession(): void
    {
        // Session already restored in mount(), just continue
        $this->saveToSession();
    }

    public function resetWizard(): void
    {
        Session::forget($this->sessionKey());
        $this->currentStep = 1;
        $this->completedSteps = [];
        $this->saveToSession();
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
            $this->saveToSession();
        }
    }

    public function getAllVerificationPassedProperty(): bool
    {
        return collect($this->verificationResults)->every(fn($check) => $check['passed']);
    }

    private function writeDbConfigToEnv(): void
    {
        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : '';

        $keys = [
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $this->dbHost,
            'DB_PORT' => $this->dbPort,
            'DB_DATABASE' => $this->dbDatabase,
            'DB_USERNAME' => $this->dbUsername,
            'DB_PASSWORD' => $this->dbPassword,
        ];

        $this->updateEnvFile($envPath, $content, $keys);
        Artisan::call('config:clear');
    }

    private function writeMailConfigToEnv(): void
    {
        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : '';

        $keys = [
            'OPENMAIL_IMAP_HOST' => $this->imapHost,
            'OPENMAIL_IMAP_PORT' => $this->imapPort,
            'OPENMAIL_IMAP_ENCRYPTION' => $this->imapEncryption,
            'OPENMAIL_IMAP_USERNAME' => $this->imapUsername,
            'OPENMAIL_IMAP_PASSWORD' => $this->imapPassword,
            'OPENMAIL_SMTP_HOST' => $this->smtpHost,
            'OPENMAIL_SMTP_PORT' => $this->smtpPort,
            'OPENMAIL_SMTP_ENCRYPTION' => $this->smtpEncryption,
            'OPENMAIL_SMTP_USERNAME' => $this->smtpUsername,
            'OPENMAIL_SMTP_PASSWORD' => $this->smtpPassword,
        ];

        $this->updateEnvFile($envPath, $content, $keys);
        Artisan::call('config:clear');
    }

    private function writeSecurityConfigToEnv(): void
    {
        $envPath = base_path('.env');
        $content = File::exists($envPath) ? File::get($envPath) : '';

        $keys = [
            'SESSION_LIFETIME' => '1440',
            'SESSION_SECURE_COOKIE' => $this->secureCookies ? 'true' : 'false',
        ];

        $this->updateEnvFile($envPath, $content, $keys);
        Artisan::call('config:clear');
    }

    private function updateEnvFile(string $path, string $content, array $keys): void
    {
        $lines = $content ? explode("\n", $content) : [];
        $found = [];

        foreach ($lines as $i => $line) {
            foreach ($keys as $key => $value) {
                if (str_starts_with(trim($line), $key . '=')) {
                    $lines[$i] = $key . '=' . $value;
                    $found[$key] = true;
                    break;
                }
            }
        }

        foreach ($keys as $key => $value) {
            if (!isset($found[$key])) {
                $lines[] = $key . '=' . $value;
            }
        }

        File::put($path, implode("\n", $lines) . "\n");
    }

    private function sessionKey(): string
    {
        return 'openmail:setup:wizard';
    }

    private function saveToSession(): void
    {
        $data = [
            'currentStep' => $this->currentStep,
            'completedSteps' => $this->completedSteps,
            'appName' => $this->appName,
            'dbHost' => $this->dbHost,
            'dbPort' => $this->dbPort,
            'dbDatabase' => $this->dbDatabase,
            'dbUsername' => $this->dbUsername,
            'dbPassword' => $this->dbPassword,
            'emailDomain' => $this->emailDomain,
            'detectedProvider' => $this->detectedProvider,
            'imapHost' => $this->imapHost,
            'imapPort' => $this->imapPort,
            'imapEncryption' => $this->imapEncryption,
            'imapUsername' => $this->imapUsername,
            'imapPassword' => $this->imapPassword,
            'smtpHost' => $this->smtpHost,
            'smtpPort' => $this->smtpPort,
            'smtpEncryption' => $this->smtpEncryption,
            'smtpUsername' => $this->smtpUsername,
            'smtpPassword' => $this->smtpPassword,
            'appOrg' => $this->appOrg,
            'appDomain' => $this->appDomain,
            'appTimezone' => $this->appTimezone,
            'adminEmail' => $this->adminEmail,
            'adminPassword' => $this->adminPassword,
            'adminPasswordConfirmation' => $this->adminPasswordConfirmation,
            'httpsEnabled' => $this->httpsEnabled,
            'secureCookies' => $this->secureCookies,
            'verificationResults' => $this->verificationResults,
            'verificationRun' => $this->verificationRun,
            'dbTestResult' => $this->dbTestResult,
            'imapTestResult' => $this->imapTestResult,
            'smtpTestResult' => $this->smtpTestResult,
        ];

        Session::put($this->sessionKey(), $data);
    }

    private function restoreFromSession(): void
    {
        $saved = Session::get($this->sessionKey());
        if (!$saved) {
            return;
        }

        foreach ($saved as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function render()
    {
        return view('setup.wizard');
    }
}