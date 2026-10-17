<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Controllers\Auth\LogoutController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AuthControllerCoverageTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(storage_path('installed'));
        parent::tearDown();
    }

    public function test_login_controller_redirects_to_installer_before_installation(): void
    {
        File::delete(storage_path('installed'));

        $this->get('/login')->assertRedirect('/install');
    }

    public function test_login_controller_displays_login_view_after_installation(): void
    {
        File::put(storage_path('installed'), json_encode(['version' => '2.0.0']));

        $this->get('/login')->assertOk()->assertViewIs('auth.login');
    }

    public function test_logout_clears_authentication_and_records_audit_event(): void
    {
        File::put(storage_path('installed'), '{}');
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'auth.logout',
            'description' => 'User logged out',
        ]);
    }

    public function test_revoke_other_sessions_keeps_current_session_and_audits_the_action(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $currentId = 'current-auth-session';
        $otherId = 'other-auth-session';
        DB::table('sessions')->insert([
            [
                'id' => $currentId,
                'user_id' => $user->id,
                'ip_address' => '192.0.2.1',
                'user_agent' => 'current browser',
                'payload' => '',
                'last_activity' => time(),
            ],
            [
                'id' => $otherId,
                'user_id' => $user->id,
                'ip_address' => '192.0.2.2',
                'user_agent' => 'other browser',
                'payload' => '',
                'last_activity' => time() - 1,
            ],
        ]);

        $response = $this->post('/settings/sessions/revoke')->from('/settings');

        $response->assertRedirect('/settings');
        $this->assertDatabaseHas('sessions', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => $otherId]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event_type' => 'auth.sessions.revoked',
        ]);
    }
}
