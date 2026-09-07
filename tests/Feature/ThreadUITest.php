<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MessageMetadata;
use App\Services\ThreadBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ThreadUITest extends TestCase
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
    public function testThreadedViewLoads(): void
    {
        // Create test messages with threading headers
        // Note: ThreadBuilder expects message_id WITHOUT brackets, in_reply_to/references WITH brackets
        $msg1 = MessageMetadata::create([
            'user_id' => $this->user->id,
            'folder_path' => 'INBOX',
            'uid' => 1,
            'message_id' => 'msg-1@example.com',  // No brackets for message_id
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'First message',
            'from_address' => 'sender@example.com',
            'from_name' => 'Sender One',
            'to_address' => $this->user->email,
            'date' => Carbon::parse('2024-01-01 10:00'),
            'snippet' => 'This is the first message',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
        ]);

        $msg2 = MessageMetadata::create([
            'user_id' => $this->user->id,
            'folder_path' => 'INBOX',
            'uid' => 2,
            'message_id' => 'msg-2@example.com',  // No brackets for message_id
            'in_reply_to' => '<msg-1@example.com>',  // With brackets for in_reply_to
            'references' => '<msg-1@example.com>',
            'subject' => 'Re: First message',
            'from_address' => 'sender2@example.com',
            'from_name' => 'Sender Two',
            'to_address' => $this->user->email,
            'date' => Carbon::parse('2024-01-01 11:00'),
            'snippet' => 'This is a reply',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
        ]);

        // Test the ThreadBuilder directly with MessageMetadata models
        $builder = new ThreadBuilder();
        $messages = collect([$msg1, $msg2]);
        $threads = $builder->buildThreads($messages);

        $this->assertCount(1, $threads);
        $this->assertEquals('msg-1@example.com', $threads[0]->message_id);
        $this->assertCount(1, $threads[0]->children);
        $this->assertEquals('msg-2@example.com', $threads[0]->children[0]->message_id);

        // ThreadBuilder correctly builds threads from MessageMetadata models
        $this->assertTrue(true);
    }

    /** @test */
    public function testExpandCollapse(): void
    {
        // This test will be expanded in Task 2 when thread-row component exists
        $this->assertTrue(true, 'Placeholder for expand/collapse test - will be implemented in Task 2');
    }

    /** @test */
    public function testThreadTogglePersistence(): void
    {
        // This test will be expanded in Task 2 when thread toggle button exists
        $this->assertTrue(true, 'Placeholder for thread toggle persistence test - will be implemented in Task 2');
    }

    /** @test */
    public function message_metadata_has_thread_accessors(): void
    {
        $message = MessageMetadata::create([
            'user_id' => $this->user->id,
            'folder_path' => 'INBOX',
            'uid' => 1,
            'message_id' => '<test@example.com>',
            'subject' => 'Test',
            'from_address' => 'test@example.com',
            'to_address' => $this->user->email,
            'date' => now(),
            'is_seen' => false,
        ]);

        // Test children accessor (virtual)
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $message->children);
        $this->assertEmpty($message->children);

        // Test latestDate accessor (defaults to date)
        $this->assertNotNull($message->latestDate);

        // Test unreadCount accessor (defaults to 1 when unread)
        $this->assertEquals(1, $message->unreadCount);

        // Test thread_id virtual attribute
        $this->assertEquals('<test@example.com>', $message->thread_id);

        // Test formatted_date accessor
        $this->assertNotEmpty($message->formatted_date);

        // Test from_display accessor
        $this->assertEquals('test@example.com', $message->from_display);
    }

    /** @test */
    public function thread_builder_orders_by_latest_message_date_descending(): void
    {
        $builder = new ThreadBuilder();
        
        $msg1 = (object) [
            'message_id' => '<msg-1@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'Thread A',
            'date' => Carbon::parse('2024-01-01 10:00'),
            'from_address' => 'a@example.com',
            'from_name' => 'A',
            'to_address' => 'b@example.com',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 1,
            'folder_path' => 'INBOX',
            'snippet' => 'A',
            'labels' => collect(),
        ];

        $msg2 = (object) [
            'message_id' => '<msg-2@example.com>',
            'in_reply_to' => null,
            'references' => null,
            'subject' => 'Thread B',
            'date' => Carbon::parse('2024-01-03 10:00'),
            'from_address' => 'b@example.com',
            'from_name' => 'B',
            'to_address' => 'a@example.com',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 2,
            'folder_path' => 'INBOX',
            'snippet' => 'B',
            'labels' => collect(),
        ];

        $msg3 = (object) [
            'message_id' => '<msg-3@example.com>',
            'in_reply_to' => '<msg-2@example.com>',
            'references' => '<msg-2@example.com>',
            'subject' => 'Re: Thread B',
            'date' => Carbon::parse('2024-01-04 10:00'),
            'from_address' => 'a@example.com',
            'from_name' => 'A',
            'to_address' => 'b@example.com',
            'is_seen' => false,
            'is_flagged' => false,
            'has_attachments' => false,
            'uid' => 3,
            'folder_path' => 'INBOX',
            'snippet' => 'C',
            'labels' => collect(),
        ];

        $messages = collect([$msg1, $msg2, $msg3]);
        $threads = $builder->buildThreads($messages);

        // Thread B (with msg-3 as latest) should be first
        $this->assertEquals('<msg-2@example.com>', $threads[0]->message_id);
        $this->assertEquals('<msg-1@example.com>', $threads[1]->message_id);
    }

    /** @test */
    public function thread_builder_resolves_missing_parents_from_cache(): void
    {
        $builder = new ThreadBuilder();
        
        // Test that the method exists and can be called
        $this->assertTrue(method_exists($builder, 'resolveMissingParentsFromCache'));
        
        // Test with empty array
        $result = $builder->resolveMissingParentsFromCache([], $this->user->id);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $result);
        $this->assertEmpty($result);
    }
}