<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ContactAutocompleteCache;
use App\Models\EmailTemplate;
use App\Models\Folder;
use App\Models\ThreadHeaderCache;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ModelRelationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_belongs_to_user_and_casts_metadata(): void
    {
        $user = User::factory()->create();
        $log = AuditLog::create([
            'user_id' => $user->id,
            'event_type' => 'test.event',
            'description' => 'A structured audit event',
            'metadata' => ['source' => 'unit-test'],
        ]);

        $this->assertSame($user->id, $log->user->id);
        $this->assertSame(['source' => 'unit-test'], $log->fresh()->metadata);
    }

    public function test_contact_autocomplete_cache_belongs_to_user_and_casts_values(): void
    {
        $user = User::factory()->create();
        $cache = ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'person@example.test',
            'name' => 'Person',
            'frequency' => '7',
            'last_used_at' => now(),
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame($user->id, $cache->user->id);
        $this->assertSame(7, $cache->fresh()->frequency);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cache->fresh()->last_used_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cache->fresh()->expires_at);
    }

    public function test_email_template_belongs_to_its_owner(): void
    {
        $user = User::factory()->create();
        $template = EmailTemplate::create([
            'user_id' => $user->id,
            'name' => 'Follow-up',
            'subject' => 'Following up',
            'body' => 'Hello there',
        ]);

        $this->assertSame($user->id, $template->user->id);
        $this->assertSame('Hello there', $template->fresh()->body);
    }

    public function test_folder_scopes_separate_standard_and_custom_folders_and_cast_counts(): void
    {
        $user = User::factory()->create();
        $inbox = Folder::create([
            'user_id' => $user->id,
            'path' => 'INBOX',
            'name' => 'Inbox',
            'role' => 'inbox',
            'total_count' => '12',
            'unread_count' => '3',
            'uidvalidity' => '99',
            'has_children' => 1,
            'sort_order' => '2',
        ]);
        $custom = Folder::create([
            'user_id' => $user->id,
            'path' => 'Projects',
            'name' => 'Projects',
            'role' => null,
            'total_count' => 0,
            'unread_count' => 0,
        ]);

        $this->assertSame([$inbox->id], Folder::forUser($user->id)->standardRoles()->pluck('id')->all());
        $this->assertSame([$custom->id], Folder::forUser($user->id)->custom()->pluck('id')->all());
        $this->assertSame(12, $inbox->fresh()->total_count);
        $this->assertSame(3, $inbox->fresh()->unread_count);
        $this->assertTrue($inbox->fresh()->has_children);
        $this->assertTrue($inbox->fresh()->isUnread());
        $this->assertFalse($custom->fresh()->isUnread());
    }

    public function test_thread_header_cache_belongs_to_user_and_casts_timestamps_and_uid(): void
    {
        $user = User::factory()->create();
        $cache = ThreadHeaderCache::create([
            'user_id' => $user->id,
            'message_id' => '<message@example.test>',
            'in_reply_to' => '<parent@example.test>',
            'references' => '<root@example.test> <parent@example.test>',
            'subject' => 'A thread',
            'date' => now(),
            'folder_path' => 'INBOX',
            'uid' => '123',
            'expires_at' => now()->addDay(),
        ]);

        $this->assertSame($user->id, $cache->user->id);
        $this->assertSame(123, $cache->fresh()->uid);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cache->fresh()->date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $cache->fresh()->expires_at);
    }
}
