<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Signature;
use App\Models\User;
use App\Services\SignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignatureServiceTest extends TestCase
{
    use RefreshDatabase;

    private SignatureService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SignatureService::class);
    }

    private function createSignature(User $user, string $name, bool $isDefault = false): Signature
    {
        return $this->service->create([
            'name' => $name,
            'content_json' => [
                'type' => 'doc',
                'content' => [
                    ['type' => 'paragraph', 'content' => [
                        ['type' => 'text', 'text' => 'Regards, '.$name],
                    ]],
                ],
            ],
            'is_default' => $isDefault,
        ], $user);
    }

    public function test_first_signature_becomes_default_automatically(): void
    {
        $user = User::factory()->create();
        $signature = $this->createSignature($user, 'Personal');

        $this->assertTrue($signature->fresh()->is_default);
        $this->assertSame($signature->id, $this->service->getDefault($user)->id);
    }

    public function test_new_signatures_do_not_replace_existing_default_unless_selected(): void
    {
        $user = User::factory()->create();
        $default = $this->createSignature($user, 'Primary');
        $secondary = $this->createSignature($user, 'Secondary');

        $this->assertTrue($default->fresh()->is_default);
        $this->assertFalse($secondary->fresh()->is_default);
        $this->assertSame($default->id, $this->service->getDefault($user)->id);
    }

    public function test_set_default_unsets_other_signatures_for_the_same_user(): void
    {
        $user = User::factory()->create();
        $first = $this->createSignature($user, 'First');
        $second = $this->createSignature($user, 'Second');

        $this->service->setDefault($second);

        $this->assertFalse($first->fresh()->is_default);
        $this->assertTrue($second->fresh()->is_default);
        $this->assertSame($second->id, $this->service->getDefault($user)->id);
    }

    public function test_deleting_default_promotes_newest_remaining_signature(): void
    {
        $user = User::factory()->create();
        $oldDefault = $this->createSignature($user, 'Old default');
        $this->createSignature($user, 'Middle');
        $newest = $this->createSignature($user, 'Newest');

        $this->service->delete($oldDefault);

        $this->assertDatabaseMissing('signatures', ['id' => $oldDefault->id]);
        $this->assertTrue($newest->fresh()->is_default);
    }

    public function test_deleting_default_with_no_remaining_signature_is_safe(): void
    {
        $user = User::factory()->create();
        $signature = $this->createSignature($user, 'Only one');

        $this->service->delete($signature);

        $this->assertNull($this->service->getDefault($user));
    }

    public function test_update_returns_the_persisted_signature(): void
    {
        $user = User::factory()->create();
        $signature = $this->createSignature($user, 'Original');

        $updated = $this->service->update($signature, ['name' => 'Updated']);

        $this->assertSame('Updated', $updated->name);
        $this->assertSame('Updated', $signature->fresh()->name);
    }

    public function test_get_all_orders_defaults_first_then_alphabetically(): void
    {
        $user = User::factory()->create();
        $this->createSignature($user, 'Zebra');
        $this->createSignature($user, 'Beta');
        $alpha = $this->createSignature($user, 'Alpha');
        $this->service->setDefault($alpha);

        $this->assertSame(['Alpha', 'Beta', 'Zebra'], $this->service->getAll($user)->pluck('name')->all());
    }

    public function test_signatures_are_scoped_to_their_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $owned = $this->createSignature($owner, 'Owned');
        $this->createSignature($other, 'Other');

        $this->assertSame([$owned->id], $this->service->getAll($owner)->pluck('id')->all());
    }
}
