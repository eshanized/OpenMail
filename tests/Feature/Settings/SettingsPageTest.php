<?php

namespace Tests\Feature\Settings;

use Tests\TestCase;
use App\Models\User;
use App\Models\Setting;
use App\Models\Signature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;

class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);
    }

    #[Test]
    public function test_settings_page_renders_successfully(): void
    {
        $response = $this->actingAs($this->user)->get(route('settings'));

        $response->assertStatus(200);
        $response->assertSee('Settings & Preferences', false);
        $response->assertSee('Back to Mailbox');
        $response->assertSee('Profile');
        $response->assertSee('Mail');
        $response->assertSee('Appearance');
        $response->assertSee('Security');
        $response->assertSee('Signatures');
    }

    #[Test]
    public function test_settings_page_switches_active_tab(): void
    {
        Livewire::actingAs($this->user)
            ->test('settings.settings-page')
            ->assertSet('activeTab', 'profile')
            ->call('setTab', 'mail')
            ->assertSet('activeTab', 'mail')
            ->call('setTab', 'signatures')
            ->assertSet('activeTab', 'signatures');
    }

    #[Test]
    public function test_profile_tab_updates_display_name_and_email(): void
    {
        Livewire::actingAs($this->user)
            ->test('settings.profile-tab')
            ->assertSet('name', 'Jane Doe')
            ->assertSet('email', 'jane@example.com')
            ->set('name', 'Jane Smith')
            ->call('save')
            ->assertDispatched('toast', message: 'Profile updated successfully.');

        $this->user->refresh();
        $this->assertSame('Jane Smith', $this->user->name);
    }

    #[Test]
    public function test_mail_tab_updates_preferences(): void
    {
        Livewire::actingAs($this->user)
            ->test('settings.mail-tab')
            ->set('pageSize', 50)
            ->set('defaultFolder', 'Sent')
            ->set('replyBehavior', 'reply_all')
            ->call('save')
            ->assertDispatched('toast', message: 'Mail preferences updated');

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'page_size',
            'value' => '50',
            'group' => 'mail',
        ]);

        $this->assertDatabaseHas('settings', [
            'user_id' => $this->user->id,
            'key' => 'default_folder',
            'value' => '"Sent"',
            'group' => 'mail',
        ]);
    }

    #[Test]
    public function test_signatures_tab_can_create_signature(): void
    {
        Livewire::actingAs($this->user)
            ->test('settings.signatures-tab')
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->set('name', 'Official Work')
            ->set('contentJson', [
                'type' => 'doc',
                'content' => [
                    [
                        'type' => 'paragraph',
                        'content' => [
                            ['type' => 'text', 'text' => 'Best regards, Jane']
                        ]
                    ]
                ]
            ])
            ->set('isDefault', true)
            ->call('saveSignature')
            ->assertSet('showModal', false)
            ->assertDispatched('toast', message: 'Signature saved successfully.');

        $this->assertDatabaseHas('signatures', [
            'user_id' => $this->user->id,
            'name' => 'Official Work',
            'is_default' => 1,
        ]);
    }
}
