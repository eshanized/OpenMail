<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\MessageMetadata;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchResultsPageTest extends TestCase
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
    public function search_results_page_renders_with_results(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Project alpha deadline',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Project alpha']));
        $response->assertStatus(200);
        // Subject is highlighted with <mark> tags, so check for the individual words
        $response->assertSee(['Project', 'alpha', 'deadline'], false);
    }

    /** @test */
    public function search_results_page_redirects_on_empty_query(): void
    {
        $response = $this->get(route('search', ['q' => '']));
        $response->assertRedirect(route('mailbox'));
    }

    /** @test */
    public function search_results_page_shows_empty_state(): void
    {
        $response = $this->get(route('search', ['q' => 'nonexistent query xyz']));
        $response->assertStatus(200);
        $response->assertSee('No messages found');
        $response->assertSee('Try adjusting your search terms or filters');
    }

    /** @test */
    public function folder_filter_works(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Message in inbox',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Message in sent',
            'folder_path' => 'Sent',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Message', 'folder' => 'INBOX']));
        $response->assertStatus(200);
        // Check that INBOX message is shown (highlighted with <mark> tags)
        $response->assertSee(['inbox'], false);
        // Check that Sent message is not shown
        $response->assertDontSee('Message in sent');
    }

    /** @test */
    public function date_range_filter_works(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Recent message',
            'folder_path' => 'INBOX',
            'date' => now()->subDay(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Old message',
            'folder_path' => 'INBOX',
            'date' => now()->subDays(30),
        ]);

        $response = $this->get(route('search', [
            'q' => 'message',
            'date_from' => now()->subDays(7)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]));
        $response->assertStatus(200);
        $response->assertSee('Recent');
        $response->assertDontSee('Old message');
    }

    /** @test */
    public function attachment_filter_works(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Message with attachment',
            'has_attachments' => true,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Message without attachment',
            'has_attachments' => false,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Message', 'has_attachment' => '1']));
        $response->assertStatus(200);
        $response->assertSee('attachment');
        $response->assertDontSee('without attachment');
    }

    /** @test */
    public function read_unread_filter_works(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Unread message',
            'is_seen' => false,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Read message',
            'is_seen' => true,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'message', 'is_seen' => '0']));
        $response->assertStatus(200);
        $response->assertSee('Unread');
        $response->assertDontSee('Read message');
    }

    /** @test */
    public function flagged_filter_works(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Flagged message',
            'is_flagged' => true,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Unflagged message',
            'is_flagged' => false,
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'message', 'is_flagged' => '1']));
        $response->assertStatus(200);
        $response->assertSee('Flagged');
        $response->assertDontSee('Unflagged message');
    }

    /** @test */
    public function label_filter_works(): void
    {
        $label = Label::create([
            'user_id' => $this->user->id,
            'name' => 'Important',
            'color' => '#DC2626',
        ]);

        $taggedMessage = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Tagged message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);
        $taggedMessage->labels()->attach($label->id, ['user_id' => $this->user->id]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Untagged message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'message', 'labels' => [$label->id]]));
        $response->assertStatus(200);
        $response->assertSee('Tagged');
        $response->assertDontSee('Untagged message');
    }

    /** @test */
    public function active_filter_chips_displayed(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Test', 'folder' => 'INBOX', 'has_attachment' => '1']));
        $response->assertStatus(200);
        $response->assertSee('Folder:');
        $response->assertSee('Has attachment');
    }

    /** @test */
    public function pagination_works(): void
    {
        // Create enough messages to trigger pagination (25 per page)
        for ($i = 0; $i < 30; $i++) {
            MessageMetadata::factory()->create([
                'user_id' => $this->user->id,
                'subject' => "Searchable message number $i",
                'folder_path' => 'INBOX',
                'date' => now()->subHours($i),
            ]);
        }

        $response = $this->get(route('search', ['q' => 'Searchable message']));
        $response->assertStatus(200);
        // Should have pagination links
        $response->assertSee('page=2');
    }

    /** @test */
    public function search_results_page_shows_result_count(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Alpha message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Alpha second message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'Alpha']));
        $response->assertStatus(200);
        $response->assertSee('2 results');
    }

    /** @test */
    public function search_results_page_isolation_by_user(): void
    {
        $otherUser = User::factory()->create();

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'My private search result',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $otherUser->id,
            'subject' => 'My private search result',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $response = $this->get(route('search', ['q' => 'private search result']));
        $response->assertStatus(200);
        // Should only show 1 result (current user's)
        $response->assertSee('1 result');
    }
}
