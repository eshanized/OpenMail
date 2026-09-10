<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactAutocompleteCache;
use App\Models\User;
use App\Services\ContactAutocompleteService;
use App\Services\ContactService;
use App\Services\FolderMapper;
use App\Services\ImapMailboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactAutocompleteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private ContactAutocompleteService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = new ContactAutocompleteService(
            app(ImapMailboxService::class),
            app(FolderMapper::class)
        );
    }

    /** @test */
    public function search_unified_merges_local_contacts_and_imap_cache(): void
    {
        // Create local contact
        $contactService = new ContactService;
        $contactService->create($this->user->id, [
            'name' => 'Local Contact',
            'email' => 'local@example.com',
            'phone' => '+1111111111',
        ]);

        // Create IMAP cache entry
        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'imap@example.com',
            'name' => 'IMAP Contact',
            'frequency' => 5,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, 'local', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('local@example.com', $results->first()['email']);
        $this->assertEquals('local', $results->first()['source']);
    }

    /** @test */
    public function search_unified_local_contacts_exact_match_ranks_higher(): void
    {
        $contactService = new ContactService;
        $contactService->create($this->user->id, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $contactService->create($this->user->id, [
            'name' => 'Johnny',
            'email' => 'johnny@example.com',
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'john.imap@example.com',
            'name' => 'John IMAP',
            'frequency' => 10,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, 'john', 10);

        // Exact match on email prefix should rank higher
        $this->assertEquals('john@example.com', $results->first()['email']);
        $this->assertEquals('local', $results->first()['source']);
    }

    /** @test */
    public function search_unified_imap_cache_frequency_desc(): void
    {
        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'low@example.com',
            'name' => 'Low Frequency',
            'frequency' => 1,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);
        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'high@example.com',
            'name' => 'High Frequency',
            'frequency' => 10,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, '', 10);

        $this->assertEquals('high@example.com', $results->first()['email']);
        $this->assertEquals('low@example.com', $results->last()['email']);
    }

    /** @test */
    public function search_unified_deduplicates_by_email_local_wins(): void
    {
        $contactService = new ContactService;
        $contactService->create($this->user->id, [
            'name' => 'Local Version',
            'email' => 'same@example.com',
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'same@example.com',
            'name' => 'IMAP Version',
            'frequency' => 100,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, 'same', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('same@example.com', $results->first()['email']);
        $this->assertEquals('Local Version', $results->first()['name']);
        $this->assertEquals('local', $results->first()['source']);
    }

    /** @test */
    public function search_unified_imap_cache_auto_expires(): void
    {
        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'expired@example.com',
            'name' => 'Expired',
            'frequency' => 10,
            'last_used_at' => now()->subDays(10),
            'expires_at' => now()->subDays(3), // Expired
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'valid@example.com',
            'name' => 'Valid',
            'frequency' => 5,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, '', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('valid@example.com', $results->first()['email']);
    }

    /** @test */
    public function search_unified_returns_format_compatible_with_composer_dropdown(): void
    {
        $contactService = new ContactService;
        $contact = $contactService->create($this->user->id, [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);

        $results = $this->service->searchUnified($this->user->id, 'test', 10);

        $this->assertCount(1, $results);
        $result = $results->first();

        $this->assertArrayHasKey('name', $result);
        $this->assertArrayHasKey('email', $result);
        $this->assertArrayHasKey('phone', $result);
        $this->assertArrayHasKey('avatar', $result);
        $this->assertArrayHasKey('frequency', $result);
        $this->assertArrayHasKey('source', $result);

        $this->assertEquals('Test Contact', $result['name']);
        $this->assertEquals('test@example.com', $result['email']);
        $this->assertEquals('+1234567890', $result['phone']);
        $this->assertNotNull($result['avatar']);
        $this->assertEquals(0, $result['frequency']); // usage_count starts at 0
        $this->assertEquals('local', $result['source']);
    }

    /** @test */
    public function search_unified_respects_limit(): void
    {
        $contactService = new ContactService;
        for ($i = 1; $i <= 15; $i++) {
            $contactService->create($this->user->id, [
                'name' => "Contact $i",
                'email' => "contact$i@example.com",
            ]);
        }

        $results = $this->service->searchUnified($this->user->id, 'contact', 5);

        $this->assertCount(5, $results);
    }

    /** @test */
    public function search_unified_case_insensitive_deduplication(): void
    {
        $contactService = new ContactService;
        $contactService->create($this->user->id, [
            'name' => 'Local',
            'email' => 'TEST@EXAMPLE.COM',
        ]);

        ContactAutocompleteCache::create([
            'user_id' => $this->user->id,
            'email' => 'test@example.com', // lowercase
            'name' => 'IMAP',
            'frequency' => 10,
            'last_used_at' => now(),
            'expires_at' => now()->addDays(7),
        ]);

        $results = $this->service->searchUnified($this->user->id, 'test', 10);

        $this->assertCount(1, $results);
        $this->assertEquals('local', $results->first()['source']);
        $this->assertEquals('test@example.com', strtolower($results->first()['email']));
    }
}
