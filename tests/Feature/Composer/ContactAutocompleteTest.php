<?php

namespace Tests\Feature\Composer;

use App\Models\ContactAutocompleteCache;
use App\Models\User;
use App\Services\ContactAutocompleteService;
use App\Services\ImapMailboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_contacts_returns_matching_recipients_from_cache()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'john@example.com',
            'name' => 'John Doe',
            'frequency' => 5,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'jane@example.com',
            'name' => 'Jane Smith',
            'frequency' => 3,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $service = app(ContactAutocompleteService::class);
        $results = $service->search($user->id, 'john');

        $this->assertCount(1, $results);
        $this->assertEquals('john@example.com', $results->first()['email']);
        $this->assertEquals('John Doe', $results->first()['name']);
    }

    public function test_search_contacts_respects_limit_parameter()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        for ($i = 1; $i <= 15; $i++) {
            ContactAutocompleteCache::create([
                'user_id' => $user->id,
                'email' => "user{$i}@example.com",
                'name' => "User {$i}",
                'frequency' => $i,
                'last_used_at' => now(),
                'expires_at' => now()->addDays(7),
            ]);
        }

        $service = app(ContactAutocompleteService::class);
        $results = $service->search($user->id, 'user', 5);

        $this->assertCount(5, $results);
        // Should be ordered by frequency descending
        $this->assertEquals('user15@example.com', $results->first()['email']);
    }

    public function test_refresh_cache_populates_from_imap_sent_inbox()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mockImapService = \Mockery::mock(ImapMailboxService::class);
        $mockImapService->shouldReceive('searchRecipients')
            ->once()
            ->andReturn([
                ['email' => 'contact1@example.com', 'name' => 'Contact One', 'frequency' => 10],
                ['email' => 'contact2@example.com', 'name' => 'Contact Two', 'frequency' => 5],
            ]);

        $this->app->instance(ImapMailboxService::class, $mockImapService);

        $service = app(ContactAutocompleteService::class);
        $count = $service->refreshCache($user->id);

        $this->assertEquals(2, $count);

        $cached = ContactAutocompleteCache::where('user_id', $user->id)->get();
        $this->assertCount(2, $cached);
        $this->assertEquals('contact1@example.com', $cached[0]->email);
        $this->assertEquals(10, $cached[0]->frequency);
    }

    public function test_cache_expires_after_7_days()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'old@example.com',
            'name' => 'Old Contact',
            'frequency' => 1,
            'last_used_at' => now()->subDays(8),
            'expires_at' => now()->subDays(1), // Expired
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'new@example.com',
            'name' => 'New Contact',
            'frequency' => 1,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $service = app(ContactAutocompleteService::class);
        $results = $service->search($user->id, 'contact');

        $this->assertCount(1, $results);
        $this->assertEquals('new@example.com', $results->first()['email']);
    }

    public function test_search_works_with_partial_email_name_matches()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'john.doe@company.com',
            'name' => 'John Doe',
            'frequency' => 5,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $user->id,
            'email' => 'jane.smith@other.com',
            'name' => 'Jane Smith',
            'frequency' => 3,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $service = app(ContactAutocompleteService::class);

        // Search by partial email
        $results = $service->search($user->id, 'john');
        $this->assertCount(1, $results);

        // Search by partial name
        $results = $service->search($user->id, 'doe');
        $this->assertCount(1, $results);
        $this->assertEquals('john.doe@company.com', $results->first()['email']);

        // Search by domain
        $results = $service->search($user->id, 'company');
        $this->assertCount(1, $results);

        // Search matching both
        $results = $service->search($user->id, 'j');
        $this->assertCount(2, $results);
    }
}
