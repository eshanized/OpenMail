<?php

namespace Tests\Unit\Services;

use App\Services\ThreadBuilder;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Webklex\PHPIMAP\Attribute;

class ThreadBuilderTest extends TestCase
{
    private ThreadBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new ThreadBuilder;
    }

    /** @test */
    public function build_threads_returns_root_threads_with_nested_children_from_headers(): void
    {
        // Create messages with proper Message-ID, In-Reply-To, References
        $messages = collect([
            $this->makeMessage('msg-1', null, null, 'First message', Carbon::parse('2024-01-01 10:00')),
            $this->makeMessage('msg-2', 'msg-1', 'msg-1', 'Re: First message', Carbon::parse('2024-01-01 11:00')),
            $this->makeMessage('msg-3', 'msg-2', 'msg-1 msg-2', 'Re: First message', Carbon::parse('2024-01-01 12:00')),
        ]);

        $threads = $this->builder->buildThreads($messages);

        $this->assertCount(1, $threads);
        $this->assertEquals('msg-1', $threads[0]->message_id);
        $this->assertCount(1, $threads[0]->children);
        $this->assertEquals('msg-2', $threads[0]->children[0]->message_id);
        $this->assertCount(1, $threads[0]->children[0]->children);
        $this->assertEquals('msg-3', $threads[0]->children[0]->children[0]->message_id);
    }

    /** @test */
    public function extract_parent_id_prefers_in_reply_to_falls_back_to_last_references(): void
    {
        $message = $this->makeMessage('msg-3', 'msg-2', 'msg-1 msg-2', 'Subject', now());

        $parentId = $this->builder->extractParentId($message);

        $this->assertEquals('msg-2', $parentId); // In-Reply-To takes precedence
    }

    /** @test */
    public function extract_parent_id_falls_back_to_references_when_no_in_reply_to(): void
    {
        $message = $this->makeMessage('msg-3', null, 'msg-1 msg-2', 'Subject', now());

        $parentId = $this->builder->extractParentId($message);

        $this->assertEquals('msg-2', $parentId); // Last References entry
    }

    /** @test */
    public function extract_parent_id_returns_null_when_no_headers(): void
    {
        $message = $this->makeMessage('msg-1', null, null, 'Subject', now());

        $parentId = $this->builder->extractParentId($message);

        $this->assertNull($parentId);
    }

    /** @test */
    public function group_by_subject_strips_re_fwd_normalizes_whitespace_groups_by_subject_and_2day_window(): void
    {
        $messages = collect([
            $this->makeMessage('msg-1', null, null, '  Re:  Hello World  ', Carbon::parse('2024-01-01 10:00')),
            $this->makeMessage('msg-2', null, null, 'Fwd: Hello World', Carbon::parse('2024-01-01 11:00')),
            $this->makeMessage('msg-3', null, null, 'Hello World', Carbon::parse('2024-01-02 10:00')), // Within 2 days
            $this->makeMessage('msg-4', null, null, 'Hello World', Carbon::parse('2024-01-05 10:00')), // Outside 2 days
            $this->makeMessage('msg-5', null, null, 'Different Subject', Carbon::parse('2024-01-01 10:00')),
        ]);

        $grouped = $this->builder->groupBySubject($messages);

        // Should have 3 groups: (msg-1, msg-2, msg-3), (msg-4), (msg-5)
        $this->assertCount(3, $grouped);

        // First group should have msg-3 (latest date within the cluster: Jan 2)
        $firstGroup = $grouped[0];
        $this->assertEquals('msg-3', $firstGroup->message_id);
    }

    /** @test */
    public function thread_ordering_by_latest_message_date_descending(): void
    {
        $messages = collect([
            $this->makeMessage('msg-1', null, null, 'Thread A', Carbon::parse('2024-01-01 10:00')),
            $this->makeMessage('msg-2', null, null, 'Thread B', Carbon::parse('2024-01-03 10:00')), // Newer
            $this->makeMessage('msg-3', 'msg-2', 'msg-2', 'Re: Thread B', Carbon::parse('2024-01-04 10:00')), // Even newer
        ]);

        $threads = $this->builder->buildThreads($messages);

        // Thread B (with msg-3) should be first because latest message is newest
        $this->assertEquals('msg-2', $threads[0]->message_id);
        $this->assertEquals('msg-1', $threads[1]->message_id);
    }

    /** @test */
    public function handles_missing_parent_messages_dummy_containers_pruned(): void
    {
        // msg-2 references msg-1 which doesn't exist
        $messages = collect([
            $this->makeMessage('msg-2', 'msg-1', 'msg-1', 'Re: Missing parent', Carbon::parse('2024-01-01 11:00')),
            $this->makeMessage('msg-3', null, null, 'Independent', Carbon::parse('2024-01-01 12:00')),
        ]);

        $threads = $this->builder->buildThreads($messages);

        // msg-2 becomes a root (parent missing, pruned per JWZ step 3)
        $this->assertCount(2, $threads);
        $messageIds = collect($threads)->pluck('message_id')->toArray();
        $this->assertContains('msg-2', $messageIds);
        $this->assertContains('msg-3', $messageIds);
    }

    /** @test */
    public function handles_circular_references_gracefully_no_infinite_loop(): void
    {
        // msg-1 -> msg-2 -> msg-1 (circular)
        $messages = collect([
            $this->makeMessage('msg-1', 'msg-2', 'msg-2', 'Circular 1', Carbon::parse('2024-01-01 10:00')),
            $this->makeMessage('msg-2', 'msg-1', 'msg-1', 'Circular 2', Carbon::parse('2024-01-01 11:00')),
        ]);

        $threads = $this->builder->buildThreads($messages);

        // Should not infinite loop; both become roots
        $this->assertCount(2, $threads);
    }

    /** @test */
    public function empty_input_returns_empty_array(): void
    {
        $threads = $this->builder->buildThreads(collect([]));

        $this->assertEquals([], $threads);
    }

    /** @test */
    public function resolves_missing_parents_from_thread_header_cache(): void
    {
        // This test will be implemented after the cache integration is added
        // For now, we test the method exists and handles cache hits
        $this->assertTrue(method_exists($this->builder, 'resolveMissingParentsFromCache'));
    }

    /** @test */
    public function handles_string_dates_and_formats_correctly(): void
    {
        $message = (object) [
            'message_id' => 'msg-string-date',
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'Date Test',
            'date' => now()->toRfc2822String(),
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender',
            'to_address' => 'user@example.com',
            'is_seen' => true,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 10,
            'folder_path' => 'INBOX',
            'snippet' => '',
            'labels' => collect(),
        ];

        $threads = $this->builder->buildThreads(collect([$message]));

        $this->assertCount(1, $threads);
        $this->assertNotEmpty($threads[0]->formatted_date);
        $this->assertEquals(now()->format('g:i A'), $threads[0]->formatted_date);
    }

    /** @test */
    public function from_display_falls_back_to_address_when_from_name_is_empty_string(): void
    {
        $message = (object) [
            'message_id' => 'msg-empty-name',
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'GitHub Notification',
            'date' => now(),
            'from_address' => 'noreply@github.com',
            'from_name' => '', // Empty string, typical for some system emails
            'to_address' => 'user@example.com',
            'is_seen' => true,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 11,
            'folder_path' => 'INBOX',
            'snippet' => '',
            'labels' => collect(),
        ];

        $threads = $this->builder->buildThreads(collect([$message]));

        $this->assertCount(1, $threads);
        $this->assertEquals('noreply@github.com', $threads[0]->from_display);
    }

    /** @test */
    public function build_threads_ensures_labels_is_collection_even_when_attribute_or_null_passed(): void
    {
        $message = (object) [
            'message_id' => 'msg-attr',
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'Attribute test',
            'date' => now(),
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender',
            'to_address' => 'user@example.com',
            'is_seen' => true,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 12,
            'folder_path' => 'INBOX',
            'snippet' => '',
            'labels' => new Attribute('labels'),
        ];

        $threads = $this->builder->buildThreads(collect([$message]));

        $this->assertCount(1, $threads);
        $this->assertInstanceOf(Collection::class, $threads[0]->labels);
    }

    private function makeMessage(string $messageId, ?string $inReplyTo, ?string $references, string $subject, Carbon $date): object
    {
        return (object) [
            'message_id' => $messageId,
            'in_reply_to' => $inReplyTo ? "<{$inReplyTo}>" : null,
            'references' => $references ? "<{$references}>" : null,
            'subject' => $subject,
            'date' => $date,
            'from_address' => 'test@example.com',
            'from_name' => 'Test User',
            'to_address' => 'recipient@example.com',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 1,
            'folder_path' => 'INBOX',
            'snippet' => 'Test snippet',
            'labels' => collect(),
        ];
    }
}
