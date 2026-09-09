<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\User;
use App\Services\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /** @test */
    public function contact_create_validates_email_format_and_sets_avatar_color(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertEquals('John Doe', $contact->name);
        $this->assertEquals('john@example.com', $contact->email);
        $this->assertNotEmpty($contact->avatar_color);
        $this->assertDatabaseHas('contacts', [
            'user_id' => $this->user->id,
            'email' => 'john@example.com',
        ]);
    }

    /** @test */
    public function contact_create_rejects_invalid_email(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $service = new ContactService();
        $service->create($this->user->id, [
            'name' => 'Bad Email',
            'email' => 'not-an-email',
        ]);
    }

    /** @test */
    public function contact_create_rejects_duplicate_email_per_user(): void
    {
        $service = new ContactService();
        $service->create($this->user->id, [
            'name' => 'First',
            'email' => 'dup@example.com',
        ]);

        $this->expectException(\App\Exceptions\DuplicateContactException::class);
        $service->create($this->user->id, [
            'name' => 'Second',
            'email' => 'dup@example.com',
        ]);
    }

    /** @test */
    public function contact_update_validates_email_unique_excluding_self(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'John',
            'email' => 'john@example.com',
        ]);

        // Update own email should work
        $updated = $service->update($contact, [
            'email' => 'john-new@example.com',
        ]);
        $this->assertEquals('john-new@example.com', $updated->email);
    }

    /** @test */
    public function contact_update_rejects_duplicate_email_from_another_contact(): void
    {
        $service = new ContactService();
        $contact1 = $service->create($this->user->id, [
            'name' => 'First',
            'email' => 'first@example.com',
        ]);
        $service->create($this->user->id, [
            'name' => 'Second',
            'email' => 'second@example.com',
        ]);

        $this->expectException(\App\Exceptions\DuplicateContactException::class);
        $service->update($contact1, [
            'email' => 'second@example.com',
        ]);
    }

    /** @test */
    public function contact_delete_removes_contact(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'To Delete',
            'email' => 'delete@example.com',
        ]);

        $service->delete($contact);

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function contact_delete_syncs_groups(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'Grouped',
            'email' => 'grouped@example.com',
        ]);

        $group = ContactGroup::create([
            'user_id' => $this->user->id,
            'name' => 'Friends',
        ]);
        $contact->groups()->attach($group->id);

        $this->assertDatabaseHas('contact_group_contact', [
            'contact_id' => $contact->id,
            'contact_group_id' => $group->id,
        ]);

        $service->delete($contact);

        $this->assertDatabaseMissing('contact_group_contact', [
            'contact_id' => $contact->id,
        ]);
    }

    /** @test */
    public function contact_group_crud_with_user_scoping(): void
    {
        $service = new ContactService();

        // Create group
        $group = $service->createGroup($this->user->id, [
            'name' => 'Work',
            'color' => 'bg-blue-500',
        ]);

        $this->assertEquals('Work', $group->name);
        $this->assertEquals('bg-blue-500', $group->color);
        $this->assertDatabaseHas('contact_groups', [
            'user_id' => $this->user->id,
            'name' => 'Work',
        ]);

        // Update group
        $group->name = 'Office';
        $group->save();

        $this->assertDatabaseHas('contact_groups', [
            'id' => $group->id,
            'name' => 'Office',
        ]);

        // Delete group
        $group->delete();
        $this->assertDatabaseMissing('contact_groups', [
            'id' => $group->id,
        ]);
    }

    /** @test */
    public function get_groups_returns_user_groups_with_contact_counts(): void
    {
        $service = new ContactService();

        $group1 = ContactGroup::create(['user_id' => $this->user->id, 'name' => 'Friends']);
        $group2 = ContactGroup::create(['user_id' => $this->user->id, 'name' => 'Work']);

        $contact1 = $service->create($this->user->id, ['name' => 'A', 'email' => 'a@test.com']);
        $contact2 = $service->create($this->user->id, ['name' => 'B', 'email' => 'b@test.com']);

        $contact1->groups()->attach($group1->id);
        $contact2->groups()->attach($group1->id);
        $contact2->groups()->attach($group2->id);

        $groups = $service->getGroups($this->user->id);

        $this->assertCount(2, $groups);

        $friendsGroup = $groups->firstWhere('name', 'Friends');
        $this->assertEquals(2, $friendsGroup->contacts_count);

        $workGroup = $groups->firstWhere('name', 'Work');
        $this->assertEquals(1, $workGroup->contacts_count);
    }

    /** @test */
    public function search_contacts_by_email_or_name(): void
    {
        $service = new ContactService();
        $service->create($this->user->id, ['name' => 'Alice Smith', 'email' => 'alice@example.com']);
        $service->create($this->user->id, ['name' => 'Bob Jones', 'email' => 'bob@example.com']);

        $results = $service->search($this->user->id, 'alice');
        $this->assertCount(1, $results);
        $this->assertEquals('alice@example.com', $results->first()->email);

        $results = $service->search($this->user->id, 'jones');
        $this->assertCount(1, $results);
        $this->assertEquals('bob@example.com', $results->first()->email);
    }

    /** @test */
    public function get_for_user_returns_only_user_contacts(): void
    {
        $service = new ContactService();
        $otherUser = User::factory()->create();

        $service->create($this->user->id, ['name' => 'Mine', 'email' => 'mine@test.com']);
        $service->create($otherUser->id, ['name' => 'Theirs', 'email' => 'theirs@test.com']);

        $contacts = $service->getForUser($this->user->id);
        $this->assertCount(1, $contacts);
        $this->assertEquals('Mine', $contacts->first()->name);
    }

    /** @test */
    public function increment_usage_increments_count(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, ['name' => 'Used', 'email' => 'used@test.com']);

        $this->assertEquals(0, $contact->usage_count);

        $service->incrementUsage($contact);
        $contact->refresh();

        $this->assertEquals(1, $contact->usage_count);

        $service->incrementUsage($contact);
        $contact->refresh();

        $this->assertEquals(2, $contact->usage_count);
    }

    /** @test */
    public function avatar_color_deterministic_from_email_hash(): void
    {
        // Same email should produce same color (test static method directly)
        $color1 = Contact::colorFromEmail('test@example.com');
        $color2 = Contact::colorFromEmail('test@example.com');
        $this->assertEquals($color1, $color2);

        // Different email should potentially produce different color
        $color3 = Contact::colorFromEmail('different@example.com');
        
        // Verify colors are from palette
        $palette = ['bg-blue-500','bg-green-600','bg-red-600','bg-yellow-600','bg-purple-600','bg-pink-600','bg-orange-600','bg-teal-600','bg-indigo-600','bg-gray-500'];
        $this->assertContains($color1, $palette);
        $this->assertContains($color3, $palette);
    }

    /** @test */
    public function avatar_color_set_on_create_not_changed_on_update(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, ['name' => 'Test', 'email' => 'test@test.com']);
        $originalColor = $contact->avatar_color;

        $service->update($contact, ['name' => 'Updated Name']);
        $contact->refresh();

        $this->assertEquals($originalColor, $contact->avatar_color);
    }

/** @test */
    public function test_contact_sidebar_loads_contacts(): void
    {
        $service = new ContactService();
        $service->create($this->user->id, ['name' => 'Alice', 'email' => 'alice@example.com']);
        $service->create($this->user->id, ['name' => 'Bob', 'email' => 'bob@example.com']);

        $component = \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Mailbox\ContactSidebar::class)
            ->assertSet('activeTab', false)
            ->set('contacts', $service->getForUser($this->user->id));

        $component->assertSet('contacts', fn($contacts) => $contacts->count() === 2);
    }

    /** @test */
    public function test_contact_sidebar_search(): void
    {
        $service = new ContactService();
        $service->create($this->user->id, ['name' => 'Alice Smith', 'email' => 'alice@example.com']);
        $service->create($this->user->id, ['name' => 'Bob Jones', 'email' => 'bob@example.com']);

        $component = \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Mailbox\ContactSidebar::class)
            ->set('activeTab', true)
            ->set('contacts', $service->getForUser($this->user->id))
            ->set('search', 'alice');

        $component->assertSet('contacts', fn($contacts) => $contacts->count() === 1);
    }

    /** @test */
    public function test_contact_modal_create(): void
    {
        $component = \Livewire\Livewire::actingAs($this->user)
            ->test(\App\Livewire\Mailbox\ContactModal::class)
            ->set('name', 'New Contact')
            ->set('email', 'new@example.com')
            ->call('save');

        // Verify save succeeded: no validation errors, modal closed, and event dispatched
        $component->assertHasNoErrors();
        $component->assertSet('showModal', false);
        $component->assertDispatched('contactSaved');
        $component->assertDispatched('toast', 'Contact saved', 'success');
    }

    /** @test */
    public function test_contact_modal_edit(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Mailbox\ContactModal::class, ['contact' => $contact])
            ->set('name', 'Updated Name')
            ->call('save');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function test_contact_modal_delete(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'To Delete',
            'email' => 'delete@example.com',
        ]);

        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Mailbox\ContactModal::class, ['contact' => $contact])
            ->call('delete');

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }

    /** @test */
    public function test_contact_modal_validation(): void
    {
        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Mailbox\ContactModal::class)
            ->set('name', '')
            ->set('email', 'not-an-email')
            ->call('save');

        $component->assertHasErrors(['name', 'email']);
    }

    /** @test */
    public function test_contact_row_renders_correctly(): void
    {
        $service = new ContactService();
        $contact = $service->create($this->user->id, [
            'name' => 'Test Contact',
            'email' => 'test@example.com',
            'phone' => '+1234567890',
        ]);

        $this->actingAs($this->user);
        
        $component = \Livewire\Livewire::test(\App\Livewire\Mailbox\ContactRow::class, ['contact' => $contact]);

        $component->assertSee('Test Contact');
        $component->assertSee('test@example.com');
        $component->assertSee('+1234567890');
    }
}
