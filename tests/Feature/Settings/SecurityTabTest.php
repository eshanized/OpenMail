<?php

declare(strict_types=1);

namespace Tests\Feature\Settings;

use App\Livewire\Settings\SecurityTab;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SecurityTabTest extends TestCase
{
    use RefreshDatabase;

    private function insertSession(string $id, int $userId, string $userAgent, int $lastActivity): void
    {
        DB::table('sessions')->insert([
            'id' => $id,
            'user_id' => $userId,
            'ip_address' => '192.0.2.15',
            'user_agent' => $userAgent,
            'payload' => '',
            'last_activity' => $lastActivity,
        ]);
    }

    public function test_session_list_parses_device_and_browser_information(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(SecurityTab::class);
        $currentId = session()->getId();

        $this->insertSession($currentId, $user->id, 'Mozilla/5.0 (Windows NT 10.0) Chrome/125.0', time());
        $this->insertSession('firefox-linux', $user->id, 'Mozilla/5.0 (X11; Linux x86_64) Firefox/127.0', time() - 1);
        $this->insertSession('safari-mac', $user->id, 'Mozilla/5.0 (Macintosh) Safari/605.1', time() - 2);
        $this->insertSession('edge-android', $user->id, 'Mozilla/5.0 (Linux; Android 14) EdgA/123.0', time() - 3);
        $this->insertSession('unknown-device', $user->id, '', time() - 4);

        $component->call('loadSessions')
            ->assertSet('currentSessionId', $currentId);

        $sessions = $component->get('sessions');
        $byId = collect($sessions)->keyBy('id');

        $this->assertSame('Chrome on Windows', $byId[$currentId]['user_agent']);
        $this->assertTrue($byId[$currentId]['is_current']);
        $this->assertSame('Firefox on Linux', $byId['firefox-linux']['user_agent']);
        $this->assertSame('Safari on macOS', $byId['safari-mac']['user_agent']);
        $this->assertSame('Edge on Android', $byId['edge-android']['user_agent']);
        $this->assertSame('Unknown Device', $byId['unknown-device']['user_agent']);
    }

    public function test_revoke_actions_toggle_confirmation_and_revoke_only_other_sessions(): void
    {
        $user = User::factory()->create();
        $component = Livewire::actingAs($user)->test(SecurityTab::class);
        $currentId = session()->getId();

        $this->insertSession($currentId, $user->id, 'Chrome Windows', time());
        $this->insertSession('another-session', $user->id, 'Firefox Linux', time() - 1);

        $component->call('confirmRevokeOtherSessions')
            ->assertSet('showRevokeConfirm', true)
            ->call('cancelRevoke')
            ->assertSet('showRevokeConfirm', false)
            ->set('currentSessionId', 'attacker-controlled-session-id')
            ->call('revokeOtherSessions')
            ->assertSet('showRevokeConfirm', false)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('sessions', ['id' => $currentId, 'user_id' => $user->id]);
        $this->assertDatabaseMissing('sessions', ['id' => 'another-session']);
        $this->assertSame($currentId, $component->get('currentSessionId'));
    }
}
