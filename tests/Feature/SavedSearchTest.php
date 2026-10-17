<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\SaveSearchButton;
use App\Livewire\Mailbox\SavedSearches;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_save_a_search_with_filters_including_unread_false(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(SaveSearchButton::class, [
                'query' => 'invoice',
                'filters' => [
                    'folder' => 'INBOX',
                    'has_attachment' => true,
                    'is_seen' => false,
                    'is_flagged' => false,
                    'unknown_filter' => 'must-not-persist',
                ],
            ])
            ->call('showSaveForm')
            ->set('name', 'Unread invoices')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $search = SavedSearch::query()->where('user_id', $user->id)->firstOrFail();

        $this->assertSame('Unread invoices', $search->name);
        $this->assertSame('invoice', $search->query);
        $this->assertSame('INBOX', $search->filters['folder']);
        $this->assertTrue($search->filters['has_attachment']);
        $this->assertFalse($search->filters['is_seen']);
        $this->assertArrayNotHasKey('unknown_filter', $search->filters);
        $this->assertArrayNotHasKey('is_flagged', $search->filters);
    }

    public function test_saved_searches_are_owner_scoped_and_can_be_deleted(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $visible = SavedSearch::create([
            'user_id' => $owner->id,
            'name' => 'Open invoices',
            'query' => 'invoice',
            'filters' => ['is_seen' => false],
        ]);
        $private = SavedSearch::create([
            'user_id' => $other->id,
            'name' => 'Private query',
            'query' => 'confidential',
            'filters' => [],
        ]);

        Livewire::actingAs($owner)
            ->test(SavedSearches::class)
            ->assertSee('Open invoices')
            ->assertDontSee('Private query')
            ->call('deleteSearch', $private->id)
            ->assertDispatched('toast');

        $this->assertDatabaseHas('saved_searches', ['id' => $private->id, 'user_id' => $other->id]);
        $this->assertDatabaseMissing('saved_searches', ['id' => $visible->id]);
    }

    public function test_saved_search_runs_with_saved_filters_and_cannot_be_run_by_another_user(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $search = SavedSearch::create([
            'user_id' => $owner->id,
            'name' => 'Unread receipts',
            'query' => 'receipt',
            'filters' => [
                'folder' => 'INBOX',
                'is_seen' => false,
                'has_attachment' => true,
            ],
        ]);

        Livewire::actingAs($owner)
            ->test(SavedSearches::class)
            ->call('runSearch', $search->id)
            ->assertRedirect(route('search', [
                'q' => 'receipt',
                'folder' => 'INBOX',
                'is_seen' => false,
                'has_attachment' => true,
            ]));

        Livewire::actingAs($other)
            ->test(SavedSearches::class)
            ->call('runSearch', $search->id)
            ->assertNotFound();
    }
}
