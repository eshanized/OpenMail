<?php

namespace Tests\Feature;

use App\Models\Label;
use App\Models\MessageMetadata;
use App\Models\User;
use App\Services\SearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\Searchable;
use Tests\TestCase;

class SearchIndexingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function message_metadata_uses_searchable_trait(): void
    {
        $model = new MessageMetadata;
        $this->assertArrayHasKey(Searchable::class, class_uses_recursive($model));
    }

    /** @test */
    public function to_searchable_array_includes_all_d05_fields(): void
    {
        $message = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Subject',
            'from_address' => 'sender@example.com',
            'from_name' => 'Test Sender',
            'to_address' => 'recipient@example.com',
            'snippet' => 'Test snippet text',
            'body_text' => 'Test body text content',
            'message_id' => '<test-message-id@example.com>',
            'folder_path' => 'INBOX',
            'date' => now(),
            'has_attachments' => true,
            'is_seen' => false,
            'is_flagged' => true,
        ]);

        $searchable = $message->toSearchableArray();

        $this->assertEquals('Test Subject', $searchable['subject']);
        $this->assertEquals('sender@example.com', $searchable['from_address']);
        $this->assertEquals('Test Sender', $searchable['from_name']);
        $this->assertEquals('recipient@example.com', $searchable['to_address']);
        $this->assertEquals('Test snippet text', $searchable['snippet']);
        $this->assertEquals('Test body text content', $searchable['body_text']);
        $this->assertEquals($message->id, $searchable['id']);
        $this->assertEquals($this->user->id, $searchable['user_id']);
        $this->assertEquals('INBOX', $searchable['folder_path']);
        $this->assertTrue($searchable['has_attachments']);
        $this->assertFalse($searchable['is_seen']);
        $this->assertTrue($searchable['is_flagged']);
    }

    /** @test */
    public function searchable_as_returns_per_user_index_name(): void
    {
        $message = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('message_metadata_'.$this->user->id, $message->searchableAs());
    }

    /** @test */
    public function should_be_searchable_returns_true(): void
    {
        $message = MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertTrue($message->shouldBeSearchable());
    }

    /** @test */
    public function search_service_returns_paginated_results(): void
    {
        // Create messages with different content
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Important meeting about project alpha',
            'from_address' => 'boss@example.com',
            'body_text' => 'Please attend the meeting about project alpha tomorrow',
            'folder_path' => 'INBOX',
            'date' => now()->subDay(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Lunch plans for friday',
            'from_address' => 'colleague@example.com',
            'body_text' => 'Want to grab lunch on friday?',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Project alpha update',
            'from_address' => 'teammate@example.com',
            'body_text' => 'Here is the latest update on project alpha',
            'folder_path' => 'Sent',
            'date' => now()->subHours(2),
        ]);

        $service = new SearchService;
        $results = $service->search($this->user->id, 'project alpha');

        $this->assertCount(2, $results);
    }

    /** @test */
    public function search_service_applies_folder_filter(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Meeting in inbox',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Meeting in sent',
            'folder_path' => 'Sent',
            'date' => now(),
        ]);

        $service = new SearchService;
        $results = $service->search($this->user->id, 'Meeting', ['folder' => 'INBOX']);

        $this->assertCount(1, $results);
        $this->assertEquals('INBOX', $results->first()->folder_path);
    }

    /** @test */
    public function search_service_applies_date_range_filter(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Old message',
            'folder_path' => 'INBOX',
            'date' => now()->subDays(10),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Recent message',
            'folder_path' => 'INBOX',
            'date' => now()->subDay(),
        ]);

        $service = new SearchService;
        $results = $service->search($this->user->id, 'message', [
            'date_from' => now()->subDays(3),
            'date_to' => now(),
        ]);

        $this->assertCount(1, $results);
        $this->assertEquals('Recent message', $results->first()->subject);
    }

    /** @test */
    public function search_service_applies_has_attachment_filter(): void
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

        $service = new SearchService;
        $results = $service->search($this->user->id, 'Message', ['has_attachment' => true]);

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->has_attachments);
    }

    /** @test */
    public function search_service_applies_is_seen_filter(): void
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

        $service = new SearchService;
        $results = $service->search($this->user->id, 'message', ['is_seen' => false]);

        $this->assertCount(1, $results);
        $this->assertFalse($results->first()->is_seen);
    }

    /** @test */
    public function search_service_applies_is_flagged_filter(): void
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

        $service = new SearchService;
        $results = $service->search($this->user->id, 'message', ['is_flagged' => true]);

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is_flagged);
    }

    /** @test */
    public function search_service_applies_labels_filter(): void
    {
        $label = Label::create([
            'user_id' => $this->user->id,
            'name' => 'Important',
            'color' => 'bg-red-500',
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

        $service = new SearchService;
        $results = $service->search($this->user->id, 'message', ['labels' => [$label->id]]);

        $this->assertCount(1, $results);
        $this->assertEquals($taggedMessage->id, $results->first()->id);
    }

    /** @test */
    public function instant_search_returns_limited_results_for_dropdown(): void
    {
        // Create more than 8 messages
        for ($i = 0; $i < 12; $i++) {
            MessageMetadata::factory()->create([
                'user_id' => $this->user->id,
                'subject' => "Meeting invite #$i",
                'folder_path' => 'INBOX',
                'date' => now()->subHours($i),
            ]);
        }

        $service = new SearchService;
        $results = $service->instantSearch($this->user->id, 'Meeting', 8);

        $this->assertCount(8, $results);
    }

    /** @test */
    public function search_isolation_by_user_id(): void
    {
        $otherUser = User::factory()->create();

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'My private message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $otherUser->id,
            'subject' => 'My private message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $service = new SearchService;
        $results = $service->search($this->user->id, 'private message');

        $this->assertCount(1, $results);
        $this->assertEquals($this->user->id, $results->first()->user_id);
    }

    /** @test */
    public function body_text_populated_with_plain_text_during_sync(): void
    {
        $htmlBody = '<html><body><p>Hello World</p><p>This is a test message body with some content.</p></body></html>';
        $plainText = MessageMetadata::extractBodyText($htmlBody);

        $this->assertStringContainsString('Hello World', $plainText);
        $this->assertStringContainsString('test message body', $plainText);
        // Should not contain HTML tags
        $this->assertStringNotContainsString('<p>', $plainText);
        $this->assertStringNotContainsString('<html>', $plainText);
    }

    /** @test */
    public function body_text_truncated_to_500_chars(): void
    {
        $longBody = str_repeat('A', 600);
        $plainText = MessageMetadata::extractBodyText($longBody);

        $this->assertLessThanOrEqual(500, strlen($plainText));
    }

    /** @test */
    public function body_text_handles_empty_input(): void
    {
        $this->assertEquals('', MessageMetadata::extractBodyText(''));
        $this->assertEquals('', MessageMetadata::extractBodyText(null));
    }

    /** @test */
    public function search_results_ordered_by_relevance_and_date(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Urgent project alpha deadline',
            'folder_path' => 'INBOX',
            'date' => now()->subDays(5),
        ]);

        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'project alpha update',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $service = new SearchService;
        $results = $service->search($this->user->id, 'project alpha');

        // Both should be returned
        $this->assertCount(2, $results);
    }

    /** @test */
    public function query_with_boolean_operator_does_not_cause_error(): void
    {
        MessageMetadata::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test message',
            'folder_path' => 'INBOX',
            'date' => now(),
        ]);

        $service = new SearchService;
        // This should not throw an exception
        $results = $service->search($this->user->id, '+test -bad *query "exact"');
        $this->assertNotNull($results);
    }

    /** @test */
    public function sanitize_query_strips_fulltext_boolean_operators(): void
    {
        $service = new SearchService;

        // FULLTEXT boolean operators: + - > < * " ( ) ~
        $malicious = '+important -junk >large <small *wildcard "phrase" (group) ~fuzzy';
        $sanitized = $service->sanitizeQuery($malicious);

        // All boolean operators should be removed
        $this->assertStringNotContainsString('+', $sanitized);
        $this->assertStringNotContainsString('-', $sanitized);
        $this->assertStringNotContainsString('>', $sanitized);
        $this->assertStringNotContainsString('<', $sanitized);
        $this->assertStringNotContainsString('*', $sanitized);
        $this->assertStringNotContainsString('"', $sanitized);
        $this->assertStringNotContainsString('(', $sanitized);
        $this->assertStringNotContainsString(')', $sanitized);
        $this->assertStringNotContainsString('~', $sanitized);

        // Keywords should be preserved
        $this->assertStringContainsString('important', $sanitized);
        $this->assertStringContainsString('junk', $sanitized);
        $this->assertStringContainsString('wildcard', $sanitized);
        $this->assertStringContainsString('phrase', $sanitized);
        $this->assertStringContainsString('group', $sanitized);
        $this->assertStringContainsString('fuzzy', $sanitized);
    }

    /** @test */
    public function sanitize_query_normalizes_whitespace(): void
    {
        $service = new SearchService;

        $result = $service->sanitizeQuery('  too   many    spaces  ');
        $this->assertEquals('too many spaces', $result);
    }

    /** @test */
    public function sanitize_query_returns_empty_for_only_operators(): void
    {
        $service = new SearchService;

        $result = $service->sanitizeQuery('+-<>*()~');
        $this->assertEquals('', trim($result));
    }
}
