<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Install\ConfigurationWriter;
use App\Install\DatabaseInstaller;
use App\Install\InstallationLock;
use App\Install\SecurityConfigurator;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

/**
 * php artisan openmail:install
 *
 * CLI installer that shares the same service layer as the web wizard.
 * Suitable for automated deployments, Docker, and expert users.
 * Integrates with the same InstallationLock as the web wizard.
 */
class InstallCommand extends Command
{
    protected $signature = 'openmail:install
                            {--force : Skip installation lock check (re-install)}
                            {--db-host=127.0.0.1 : Database host}
                            {--db-port=3306 : Database port}
                            {--db-database= : Database name (required)}
                            {--db-username=root : Database username}
                            {--db-password= : Database password}
                            {--admin-name= : Administrator full name}
                            {--admin-email= : Administrator email address}
                            {--admin-password= : Administrator password}
                            {--imap-host= : IMAP server host}
                            {--imap-port=993 : IMAP server port}
                            {--imap-encryption=ssl : IMAP encryption (ssl|tls|none)}
                            {--imap-username= : IMAP username}
                            {--imap-password= : IMAP password}
                            {--smtp-host= : SMTP server host}
                            {--smtp-port=465 : SMTP server port}
                            {--smtp-encryption=ssl : SMTP encryption (ssl|tls|none)}
                            {--smtp-username= : SMTP username}
                            {--smtp-password= : SMTP password}
                            {--app-name=OpenMail : Application name}
                            {--app-org= : Organization name}
                            {--app-domain= : Application domain}
                            {--app-url= : Application URL}
                            {--app-timezone=UTC : Application timezone}';

    protected $description = 'Run the OpenMail installation wizard from the command line.';

    public function __construct(
        private readonly DatabaseInstaller $dbInstaller,
        private readonly InstallationLock $lock,
        private readonly ConfigurationWriter $writer,
        private readonly SecurityConfigurator $security,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->newLine();
        $this->line('<fg=blue;options=bold>  ╔═════════════════════════════════╗</>');
        $this->line('<fg=blue;options=bold>  ║  OpenMail Installation Wizard   ║</>');
        $this->line('<fg=blue;options=bold>  ╚═════════════════════════════════╝</>');
        $this->newLine();

        // ── Guard ──────────────────────────────────────────────────────────────
        if ($this->lock->isInstalled() && ! $this->option('force')) {
            $this->error('OpenMail is already installed.');
            $this->line('Use --force to re-run installation (this may overwrite existing data).');

            return self::FAILURE;
        }

        // ── Database ──────────────────────────────────────────────────────────
        $this->info('1/6 Database configuration');

        $dbHost = $this->option('db-host') ?: $this->ask('Database host', '127.0.0.1');
        $dbPort = (int) ($this->option('db-port') ?: $this->ask('Database port', '3306'));
        $dbDatabase = $this->option('db-database') ?: $this->ask('Database name');
        $dbUsername = $this->option('db-username') ?: $this->ask('Database username', 'root');
        $dbPassword = $this->option('db-password') ?? $this->secret('Database password (leave blank for none)');

        if (empty($dbDatabase)) {
            $this->error('Database name is required.');

            return self::FAILURE;
        }

        $this->line('  Testing database connection…');
        $testResult = $this->dbInstaller->testConnection($dbHost, $dbPort, $dbDatabase, $dbUsername, $dbPassword ?? '');

        if (! $testResult['success']) {
            $this->error('Database connection failed: '.$testResult['error']);
            if (isset($testResult['technical'])) {
                $this->line('  '.$testResult['technical']['message']);
            }

            return self::FAILURE;
        }
        $this->line('  <fg=green>✓ Database connected</>');

        // Write DB config first so migrations can use the right connection
        $this->writer->write([
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $dbHost,
            'DB_PORT' => (string) $dbPort,
            'DB_DATABASE' => $dbDatabase,
            'DB_USERNAME' => $dbUsername,
            'DB_PASSWORD' => $dbPassword ?? '',
        ]);
        Artisan::call('config:clear');

        $this->line('  Running migrations…');
        $migResult = $this->dbInstaller->runMigrations();
        if (! $migResult['success']) {
            $this->error('Migration failed: '.($migResult['error'] ?? 'unknown error'));

            return self::FAILURE;
        }
        $this->line('  <fg=green>✓ Migrations complete</>');
        $this->newLine();

        // ── Mail ──────────────────────────────────────────────────────────────
        $this->info('2/6 Mail server configuration');

        $imapHost = $this->option('imap-host') ?: $this->ask('IMAP host');
        $imapPort = (int) ($this->option('imap-port') ?: $this->ask('IMAP port', '993'));
        $imapEncryption = $this->option('imap-encryption') ?: $this->choice('IMAP encryption', ['ssl', 'tls', 'none'], 0);
        $imapUsername = $this->option('imap-username') ?: $this->ask('IMAP username');
        $imapPassword = $this->option('imap-password') ?? $this->secret('IMAP password');

        $smtpHost = $this->option('smtp-host') ?: $this->ask('SMTP host', $imapHost);
        $smtpPort = (int) ($this->option('smtp-port') ?: $this->ask('SMTP port', '465'));
        $smtpEncryption = $this->option('smtp-encryption') ?: $this->choice('SMTP encryption', ['ssl', 'tls', 'none'], 0);
        $smtpUsername = $this->option('smtp-username') ?: $this->ask('SMTP username', $imapUsername);
        $smtpPassword = $this->option('smtp-password') ?? $this->secret('SMTP password');

        $this->writer->write([
            'OPENMAIL_IMAP_HOST' => $imapHost ?? '',
            'OPENMAIL_IMAP_PORT' => (string) $imapPort,
            'OPENMAIL_IMAP_ENCRYPTION' => $imapEncryption,
            'OPENMAIL_IMAP_USERNAME' => $imapUsername ?? '',
            'OPENMAIL_IMAP_PASSWORD' => $imapPassword ?? '',
            'OPENMAIL_SMTP_HOST' => $smtpHost ?? '',
            'OPENMAIL_SMTP_PORT' => (string) $smtpPort,
            'OPENMAIL_SMTP_ENCRYPTION' => $smtpEncryption,
            'OPENMAIL_SMTP_USERNAME' => $smtpUsername ?? '',
            'OPENMAIL_SMTP_PASSWORD' => $smtpPassword ?? '',
        ]);
        $this->line('  <fg=green>✓ Mail configuration saved</>');
        $this->newLine();

        // ── Application settings ──────────────────────────────────────────────
        $this->info('3/6 Application settings');

        $appName = $this->option('app-name') ?: $this->ask('Application name', 'OpenMail');
        $appOrg = $this->option('app-org') ?: $this->ask('Organization name');
        $appDomain = $this->option('app-domain') ?: $this->ask('Domain (e.g. mail.example.com)');
        $appUrl = $this->option('app-url') ?: $this->ask('Application URL', 'https://'.($appDomain ?: 'localhost'));
        $appTimezone = $this->option('app-timezone') ?: $this->ask('Timezone', 'UTC');

        $this->writer->write([
            'APP_NAME' => $appName,
            'APP_ENV' => 'production',
            'APP_DEBUG' => 'false',
            'APP_URL' => $appUrl,
            'APP_TIMEZONE' => $appTimezone,
        ]);
        $this->line('  <fg=green>✓ Application settings saved</>');
        $this->newLine();

        // ── Administrator ─────────────────────────────────────────────────────
        $this->info('4/6 Administrator account');

        $adminName = $this->option('admin-name') ?: $this->ask('Administrator name');
        $adminEmail = $this->option('admin-email') ?: $this->ask('Administrator email');
        $adminPassword = $this->option('admin-password') ?? $this->secret('Administrator password');

        if (empty($adminEmail) || empty($adminPassword)) {
            $this->error('Administrator email and password are required.');

            return self::FAILURE;
        }

        if (strlen($adminPassword) < 8) {
            $this->error('Password must be at least 8 characters.');

            return self::FAILURE;
        }

        if (! User::where('email', $adminEmail)->exists()) {
            User::create([
                'name' => $adminName ?: explode('@', $adminEmail)[0],
                'email' => $adminEmail,
                'password' => Hash::make($adminPassword),
            ]);
        }
        $this->line('  <fg=green>✓ Administrator account created</>');
        $this->newLine();

        // ── Security ──────────────────────────────────────────────────────────
        $this->info('5/6 Security configuration');

        $this->security->ensureAppKey();
        $this->security->apply([
            'https' => str_starts_with($appUrl, 'https://'),
            'domain' => $appDomain ?? '',
        ]);

        $this->writer->write([
            'SESSION_LIFETIME' => '1440',
            'SESSION_DRIVER' => 'database',
            'CACHE_STORE' => 'database',
            'SESSION_SECURE_COOKIE' => str_starts_with($appUrl, 'https://') ? 'true' : 'false',
        ]);
        $this->line('  <fg=green>✓ Security defaults applied</>');
        $this->newLine();

        // ── Application settings in DB ────────────────────────────────────────
        $this->info('6/6 Saving settings & locking installation');

        Setting::set('app_name', $appName);
        Setting::set('app_org', $appOrg ?? '');
        Setting::set('app_domain', $appDomain ?? '');
        Setting::set('app_timezone', $appTimezone);
        Setting::set('installed_at', now()->toIso8601String());
        Setting::set('installed_version', '1.0.0');

        $this->lock->markInstalled([
            'version' => '1.0.0',
            'org' => $appOrg ?? '',
            'admin_email' => $adminEmail,
            'installed_via' => 'cli',
        ]);

        $this->newLine();
        $this->line('<fg=green;options=bold>  ✓ OpenMail has been installed successfully!</>');
        $this->newLine();
        $this->table(
            ['Setting', 'Value'],
            [
                ['URL',           $appUrl],
                ['Admin email',   $adminEmail],
                ['Organization',  $appOrg ?? '–'],
                ['Timezone',      $appTimezone],
                ['DB host',       $dbHost.':'.$dbPort],
                ['DB database',   $dbDatabase],
            ]
        );
        $this->newLine();
        $this->line('  Visit your application URL and log in with your administrator account.');
        $this->newLine();

        return self::SUCCESS;
    }
}
