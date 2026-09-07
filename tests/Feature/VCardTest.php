<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use App\Services\VCardService;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class VCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function import_parses_vcard_30_with_all_fields(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Jane Smith
        EMAIL:jane@example.com
        TEL:+1234567890
        NOTE:Colleague from work
        CATEGORIES:Work,Colleagues
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertEquals(1, $results['imported']);
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
            'notes' => 'Colleague from work',
        ]);

        $contact = Contact::where('user_id', $this->user->id)->where('email', 'jane@example.com')->first();
        $this->assertNotNull($contact);
        $this->assertCount(2, $contact->groups);
        $this->assertTrue($contact->groups->contains('name', 'Work'));
        $this->assertTrue($contact->groups->contains('name', 'Colleagues'));
    }

    /** @test */
    public function import_handles_multiple_vcards(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:First Person
        EMAIL:first@example.com
        END:VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Second Person
        EMAIL:second@example.com
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertEquals(2, $results['imported']);
        $this->assertCount(2, Contact::where('user_id', $this->user->id)->get());
    }

    /** @test */
    public function import_conflict_strategy_skip(): void
    {
        // Create existing contact
        $contactService = new ContactService();
        $contactService->create($this->user->id, [
            'name' => 'Existing',
            'email' => 'existing@example.com',
        ]);

        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Existing Updated
        EMAIL:existing@example.com
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id, 'skip');

        $this->assertEquals(1, $results['skipped']);
        $this->assertEquals(0, $results['imported']);

        // Name should NOT be updated
        $contact = Contact::where('user_id', $this->user->id)->where('email', 'existing@example.com')->first();
        $this->assertEquals('Existing', $contact->name);
    }

    /** @test */
    public function import_conflict_strategy_update(): void
    {
        $contactService = new ContactService();
        $contactService->create($this->user->id, [
            'name' => 'Old Name',
            'email' => 'update@example.com',
        ]);

        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:New Name
        EMAIL:update@example.com
        TEL:+9999999999
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id, 'update');

        $this->assertEquals(1, $results['updated']);
        $this->assertEquals(0, $results['imported']);

        $contact = Contact::where('user_id', $this->user->id)->where('email', 'update@example.com')->first();
        $this->assertEquals('New Name', $contact->name);
        $this->assertEquals('+9999999999', $contact->phone);
    }

    /** @test */
    public function import_conflict_strategy_duplicate(): void
    {
        $contactService = new ContactService();
        $contactService->create($this->user->id, [
            'name' => 'First',
            'email' => 'dup@example.com',
        ]);

        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Second
        EMAIL:dup@example.com
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id, 'duplicate');

        $this->assertEquals(1, $results['imported']);
        
        // Original contact should still exist
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'email' => 'dup@example.com',
            'name' => 'First',
        ]);
        
        // New contact should be created with modified email
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'email' => 'dup+1@example.com',
            'name' => 'Second',
        ]);
        
        $this->assertCount(2, Contact::where('user_id', $this->user->id)->get());
    }

    /** @test */
    public function import_skips_vcards_without_email(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:No Email Person
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertCount(1, $results['errors']);
        $this->assertStringContainsString('Missing email', $results['errors'][0]);
    }

    /** @test */
    public function import_normalizes_email_to_lowercase(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Case Test
        EMAIL:UPPER@EXAMPLE.COM
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertEquals(1, $results['imported']);
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'email' => 'upper@example.com',
        ]);
    }

    /** @test */
    public function export_generates_valid_vcard_30_with_all_fields(): void
    {
        $contactService = new ContactService();
        $contact = $contactService->create($this->user->id, [
            'name' => 'Export Test',
            'email' => 'export@example.com',
            'phone' => '+1112223333',
            'notes' => 'Test notes',
        ]);

        $group = ContactGroup::create(['user_id' => $this->user->id, 'name' => 'Family']);
        $contact->groups()->attach($group->id);

        $service = new VCardService();
        $output = $service->export($this->user->id);

        $this->assertNotEmpty($output);
        $this->assertStringContainsString('BEGIN:VCARD', $output);
        $this->assertStringContainsString('VERSION:3.0', $output);
        $this->assertStringContainsString('FN:Export Test', $output);
        $this->assertStringContainsString('EMAIL:export@example.com', $output);
        $this->assertStringContainsString('TEL:+1112223333', $output);
        $this->assertStringContainsString('NOTE:Test notes', $output);
        $this->assertStringContainsString('CATEGORIES:Family', $output);
        $this->assertStringContainsString('END:VCARD', $output);
    }

    /** @test */
    public function export_empty_when_no_contacts(): void
    {
        $service = new VCardService();
        $output = $service->export($this->user->id);

        $this->assertEmpty($output);
    }

    /** @test */
    public function export_only_includes_user_contacts(): void
    {
        $contactService = new ContactService();
        $otherUser = User::factory()->create();

        $contactService->create($this->user->id, ['name' => 'Mine', 'email' => 'mine@test.com']);
        $contactService->create($otherUser->id, ['name' => 'Theirs', 'email' => 'theirs@test.com']);

        $service = new VCardService();
        $output = $service->export($this->user->id);

        $this->assertStringContainsString('FN:Mine', $output);
        $this->assertStringNotContainsString('FN:Theirs', $output);
    }

    /** @test */
    public function import_handles_vcard_with_minimal_fields(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        EMAIL:minimal@test.com
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertEquals(1, $results['imported']);
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'email' => 'minimal@test.com',
            'name' => '',
        ]);
    }

    /** @test */
    public function import_syncs_groups_from_categories(): void
    {
        $vcard = <<<VCARD
        BEGIN:VCARD
        VERSION:3.0
        FN:Grouped
        EMAIL:grouped@test.com
        CATEGORIES:Friends,Family
        END:VCARD
        VCARD;

        $service = new VCardService();
        $results = $service->import($vcard, $this->user->id);

        $this->assertEquals(1, $results['imported']);

        $contact = Contact::where('user_id', $this->user->id)->where('email', 'grouped@test.com')->first();
        $this->assertCount(2, $contact->groups);
        $this->assertTrue($contact->groups->contains('name', 'Friends'));
        $this->assertTrue($contact->groups->contains('name', 'Family'));
    }
}
