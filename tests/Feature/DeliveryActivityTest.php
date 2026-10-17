<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\DeliveryActivity;
use App\Models\PendingSend;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DeliveryActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_delivery_history_is_scoped_to_the_authenticated_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        PendingSend::create([
            'user_id' => $owner->id,
            'idempotency_key' => 'delivery-owner-sent',
            'message_json' => ['to' => 'owner@example.test', 'subject' => 'Owner delivery'],
            'mime_message' => 'owner mime',
            'send_at' => now()->subMinute(),
            'status' => PendingSend::STATUS_SENT,
            'retry_count' => 0,
            'sent_folder_synced' => true,
            'smtp_accepted' => true,
            'delivered_at' => now()->subSeconds(30),
        ]);

        PendingSend::create([
            'user_id' => $other->id,
            'idempotency_key' => 'delivery-other-private',
            'message_json' => ['to' => 'private@example.test', 'subject' => 'Another user private subject'],
            'mime_message' => 'private mime',
            'send_at' => now()->subMinute(),
            'status' => PendingSend::STATUS_FAILED,
            'retry_count' => 3,
        ]);

        Livewire::actingAs($owner)
            ->test(DeliveryActivity::class)
            ->assertSee('Owner delivery')
            ->assertSee('Sent')
            ->assertDontSee('Another user private subject')
            ->assertDontSee('private@example.test');
    }

    public function test_status_filter_is_limited_to_supported_statuses_and_hides_raw_transport_errors(): void
    {
        $user = User::factory()->create();

        PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'delivery-status-sent',
            'message_json' => ['to' => 'sent@example.test', 'subject' => 'Sent record'],
            'mime_message' => 'sent mime',
            'send_at' => now()->subMinute(),
            'status' => PendingSend::STATUS_SENT,
            'retry_count' => 0,
        ]);

        PendingSend::create([
            'user_id' => $user->id,
            'idempotency_key' => 'delivery-status-uncertain',
            'message_json' => ['to' => 'uncertain@example.test', 'subject' => 'Uncertain record'],
            'mime_message' => 'uncertain mime',
            'send_at' => now()->subMinute(),
            'status' => PendingSend::STATUS_UNCERTAIN,
            'retry_count' => 1,
            'last_error' => 'raw SMTP failure should not appear in the UI',
            'last_error_category' => PendingSend::CATEGORY_UNCERTAIN,
        ]);

        $component = Livewire::actingAs($user)->test(DeliveryActivity::class);

        $component->set('statusFilter', PendingSend::STATUS_UNCERTAIN)
            ->assertSee('Uncertain record')
            ->assertSee('Uncertain delivery')
            ->assertDontSee('Sent record')
            ->assertDontSee('raw SMTP failure should not appear in the UI')
            ->assertSee('check Sent or the recipient before sending another copy');

        $component->set('statusFilter', 'unsupported-status')
            ->assertSee('Sent record')
            ->assertSee('Uncertain record');
    }
}
