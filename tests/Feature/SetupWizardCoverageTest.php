<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\SetupWizard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Session;
use Livewire\Livewire;
use Tests\TestCase;

class SetupWizardCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        File::delete(storage_path('installed'));
        Session::forget('openmail:setup:wizard');
    }

    public function test_wizard_mounts_with_requirements_and_supports_basic_navigation(): void
    {
        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 1)
            ->assertSet('totalSteps', 8)
            ->assertSet('requirementsLoaded', true)
            ->assertSet('appName', 'OpenMail')
            ->call('nextStep')
            ->assertSet('currentStep', 2)
            ->call('previousStep')
            ->assertSet('currentStep', 1)
            ->call('previousStep')
            ->assertSet('currentStep', 1)
            ->call('goToStep', 8)
            ->assertSet('currentStep', 8)
            ->call('goToStep', 0)
            ->assertSet('currentStep', 8)
            ->call('goToStep', 9)
            ->assertSet('currentStep', 8);
    }

    public function test_database_step_requires_successful_connection_and_migrations(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 3)
            ->call('nextStep')
            ->assertHasErrors('dbConnection')
            ->assertSet('currentStep', 3)
            ->set('dbTestResult', ['success' => true])
            ->call('nextStep')
            ->assertHasErrors('dbMigrations')
            ->assertSet('currentStep', 3)
            ->set('migrationsRan', true)
            ->set('dbDatabase', 'openmail_test')
            ->call('nextStep')
            ->assertSet('currentStep', 4)
            ->assertSet('completedSteps', [3]);
    }

    public function test_next_step_validates_admin_password_strength_and_confirmation(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 6)
            ->set('adminName', 'Administrator')
            ->set('adminEmail', 'admin@example.test')
            ->set('adminPassword', 'weakpass')
            ->set('adminPasswordConfirmation', 'different')
            ->call('nextStep')
            ->assertHasErrors(['adminPassword'])
            ->assertSet('currentStep', 6);
    }

    public function test_email_domain_autodetection_populates_provider_and_server_details(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('emailDomain', 'admin@gmail.com')
            ->assertSet('detectedProvider', 'Gmail')
            ->assertSet('imapHost', 'imap.gmail.com')
            ->assertSet('imapPort', 993)
            ->assertSet('smtpHost', 'smtp.gmail.com')
            ->assertSet('smtpPort', 465)
            ->assertSet('imapUsername', 'admin@gmail.com')
            ->assertSet('smtpUsername', 'admin@gmail.com');
    }

    public function test_invalid_database_inputs_are_rejected_without_opening_connections(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('dbHost', '')
            ->set('dbDatabase', '')
            ->call('testDatabaseConnection')
            ->assertHasErrors(['dbHost', 'dbDatabase'])
            ->set('dbHost', '127.0.0.1')
            ->set('dbDatabase', '!!!')
            ->set('dbUsername', 'installer')
            ->call('createDatabase')
            ->assertSet('createDbResult.success', false)
            ->assertSet('createDbResult.error', 'Invalid database name. Use only letters, numbers, and underscores.');
    }

    public function test_run_migrations_requires_a_successful_connection_first(): void
    {
        Livewire::test(SetupWizard::class)
            ->call('runMigrations')
            ->assertHasErrors('dbConnection')
            ->assertSet('migrationsRan', false);
    }

    public function test_mail_connection_tests_validate_credentials_before_connecting(): void
    {
        Livewire::test(SetupWizard::class)
            ->call('testImapConnection')
            ->assertHasErrors(['imapHost', 'imapUsername', 'imapPassword'])
            ->call('testSmtpConnection')
            ->assertHasErrors(['smtpHost', 'smtpUsername', 'smtpPassword']);
    }

    public function test_verification_lists_all_checks_and_marks_missing_credentials(): void
    {
        $component = Livewire::test(SetupWizard::class)
            ->call('runVerification')
            ->assertSet('verificationRun', true);

        $results = collect($component->get('verificationResults'))->keyBy('name');

        $this->assertCount(7, $results);
        $this->assertTrue($results['db_connection']['passed']);
        $this->assertFalse($results['imap_connection']['passed']);
        $this->assertFalse($results['smtp_connection']['passed']);
        $this->assertFalse($results['admin_account']['passed']);
        $this->assertFalse($component->instance()->getAllVerificationPassedProperty());
    }

    public function test_finish_stops_when_verification_has_failures(): void
    {
        $component = Livewire::test(SetupWizard::class)->call('finish');

        $this->assertFalse($component->get('installationComplete'));
        $this->assertFileDoesNotExist(storage_path('installed'));
    }

    public function test_wizard_session_encrypts_sensitive_credentials(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('dbPassword', 'database-secret')
            ->set('imapPassword', 'imap-secret')
            ->set('smtpPassword', 'smtp-secret')
            ->set('adminPassword', 'admin-secret')
            ->set('adminPasswordConfirmation', 'admin-secret')
            ->call('continueFromSession');

        $payload = Session::get('openmail:setup:wizard');

        $this->assertNotSame('database-secret', $payload['dbPassword']);
        $this->assertSame('database-secret', Crypt::decryptString($payload['dbPassword']));
        $this->assertSame('imap-secret', Crypt::decryptString($payload['imapPassword']));
        $this->assertSame('smtp-secret', Crypt::decryptString($payload['smtpPassword']));
        $this->assertSame('admin-secret', Crypt::decryptString($payload['adminPassword']));
    }

    public function test_restore_clears_unreadable_encrypted_passwords(): void
    {
        Session::put('openmail:setup:wizard', [
            'currentStep' => 5,
            'dbPassword' => 'invalid-ciphertext',
            'imapPassword' => 'invalid-ciphertext',
            'smtpPassword' => 'invalid-ciphertext',
            'adminPassword' => 'invalid-ciphertext',
            'adminPasswordConfirmation' => 'invalid-ciphertext',
        ]);

        Livewire::test(SetupWizard::class)
            ->assertSet('currentStep', 5)
            ->assertSet('dbPassword', '')
            ->assertSet('imapPassword', '')
            ->assertSet('smtpPassword', '')
            ->assertSet('adminPassword', '')
            ->assertSet('adminPasswordConfirmation', '');
    }

    public function test_reset_wizard_clears_database_mail_and_verification_results(): void
    {
        Livewire::test(SetupWizard::class)
            ->set('currentStep', 7)
            ->set('completedSteps', [1, 2, 3])
            ->set('dbTestResult', ['success' => true])
            ->set('createDbResult', ['success' => true])
            ->set('migrationResult', ['success' => true])
            ->set('migrationsRan', true)
            ->set('imapTestResult', ['success' => true])
            ->set('smtpTestResult', ['success' => true])
            ->set('verificationResults', [['passed' => true]])
            ->set('verificationRun', true)
            ->call('resetWizard')
            ->assertSet('currentStep', 1)
            ->assertSet('completedSteps', [])
            ->assertSet('dbTestResult', null)
            ->assertSet('createDbResult', null)
            ->assertSet('migrationResult', null)
            ->assertSet('migrationsRan', false)
            ->assertSet('imapTestResult', null)
            ->assertSet('smtpTestResult', null)
            ->assertSet('verificationResults', [])
            ->assertSet('verificationRun', false);
    }
}
