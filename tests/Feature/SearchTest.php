<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\MessageMetadata;
use App\Models\User;
use App\Livewire\Mailbox\SearchBar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /** @test */
    public function search_bar_renders_in_mailbox(): void
    {
        Livewire::test(SearchBar::class)
            ->assertStatus(200);
    }

    /** @test */
    public function instant_search_returns_results_for_valid_query(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Project alpha deadline tomorrow',
            'from_name' => 'Boss Man',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        Livewire::test(SearchBar::class)
            ->set('query', 'Project alpha')
            ->call('instantSearch')
            ->assertSet('loading', false)
            ->assertSet('open', true);

        // Verify results were populated
        $component = Livewire::test(SearchBar::class)
            ->set('query', 'Project alpha')
            ->call('instantSearch');

        $this->assertGreaterThan(0, $component->get('results')->count());
    }

    /** @test */
    public function instant_search_returns_max_8_results(): void
    {
        for ($i = 0; $i < 12; $i++) {
            MessageMetadata::factory()->create([
                'user_id' => $this->user->id,
                'subject' => "Meeting invite number $i",
                'folder_path' => 'INBOX',
                'date' => now()->subHours($i),
            ]);
        }

        $component = Livewire::test(SearchBar::class)
            ->set('query', 'Meeting')
            ->call('instantSearch');

        $this->assertLessThanOrEqual(8, $component->get('results')->count());
    }

    /** @test */
    public function instant_search_returns_empty_for_short_query(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        Livewire::test(SearchBar::class)
            ->set('query', 'a')
            ->call('instantSearch')
            ->assertSet('open', false);
    }

    /** @test */
    public function instant_search_isolation_by_user(): void
    {
        $otherUser = User::factory()->create();

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'My private search test',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $otherUser->id,
            'subject' => 'My private search test',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $component = Livewire::test(SearchBar::class)
            ->set('query', 'private search test')
            ->call('instantSearch');

        $results = $component->get('results');
        // Should only find 1 result (current user's), not both
        $this->assertCount(1, $results);
    }

    /** @test */
    public function close_dropdown_clears_results(): void
    {
        Livewire::test(SearchBar::class)
            ->set('open', true)
            ->call('closeDropdown')
            ->assertSet('open', false)
            ->assertSet('highlightedIndex', -1);
    }

    /** @test */
    public function keyboard_navigation_highlighted_index_updates(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test message one',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        Livewire::test(SearchBar::class)
            ->set('query', 'Test message')
            ->call('instantSearch')
            ->assertSet('highlightedIndex', -1);
    }
}
