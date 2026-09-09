<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function audit_log_can_be_created_without_updated_at_column(): void
    {
        $log = AuditLog::create([
            'event_type' => 'auth.test',
            'description' => 'Test event description',
            'ip' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertNotNull($log->id);
        $this->assertNotNull($log->created_at);
        $this->assertDatabaseHas('audit_logs', [
            'id' => $log->id,
            'event_type' => 'auth.test',
        ]);
    }

    /** @test */
    public function audit_service_logs_login_success(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        AuditService::loginSuccess();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'auth.login.success',
            'description' => 'User logged in successfully',
        ]);
    }

    /** @test */
    public function audit_service_logs_login_failed(): void
    {
        AuditService::loginFailed('user@example.com', 'invalid_credentials');

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'auth.login.failed',
            'description' => 'Login failed: invalid_credentials',
        ]);
    }

    /** @test */
    public function audit_service_logs_lockout(): void
    {
        AuditService::lockout('user@example.com');

        $this->assertDatabaseHas('audit_logs', [
            'event_type' => 'auth.lockout',
            'description' => 'Account locked due to failed attempts',
        ]);
    }
}
