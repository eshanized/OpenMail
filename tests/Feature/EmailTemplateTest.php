<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\Composer;
use App\Livewire\Mailbox\EmailTemplateManager;
use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_and_update_an_email_template(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(EmailTemplateManager::class)
            ->call('createTemplate')
            ->set('name', 'Meeting follow-up')
            ->set('subject', 'Thanks for meeting')
            ->set('body', "Hello,\nThank you for your time.")
            ->call('saveTemplate')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $template = EmailTemplate::query()
            ->where('user_id', $user->id)
            ->where('name', 'Meeting follow-up')
            ->firstOrFail();

        $this->assertSame('Thanks for meeting', $template->subject);
        $this->assertSame("Hello,\nThank you for your time.", $template->body);

        Livewire::actingAs($user)
            ->test(EmailTemplateManager::class)
            ->call('editTemplate', $template->id)
            ->set('name', 'Meeting follow-up v2')
            ->call('saveTemplate')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'user_id' => $user->id,
            'name' => 'Meeting follow-up v2',
        ]);
    }

    public function test_template_crud_and_composer_application_are_owner_scoped(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $template = EmailTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Welcome',
            'subject' => 'Welcome aboard',
            'body' => "Hello there,\nWelcome to the team.",
        ]);

        $privateTemplate = EmailTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Private message',
            'subject' => 'Confidential subject',
            'body' => 'Private content.',
        ]);

        Livewire::actingAs($owner)
            ->test(EmailTemplateManager::class)
            ->assertSee('Welcome')
            ->assertDontSee('Private message')
            ->call('editTemplate', $privateTemplate->id)
            ->assertDispatched('toast')
            ->assertSet('editingTemplateId', null);

        Livewire::actingAs($owner)
            ->test(Composer::class)
            ->call('applyTemplate', $template->id)
            ->assertSet('subject', 'Welcome aboard')
            ->assertSet('body', "Hello there,\nWelcome to the team.")
            ->assertSet('bodyText', "Hello there,\nWelcome to the team.")
            ->assertSet('bodyHtml', "Hello there,<br />\nWelcome to the team.");
    }

    public function test_template_names_are_unique_per_user_and_deletion_is_scoped(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        EmailTemplate::create([
            'user_id' => $owner->id,
            'name' => 'Default',
            'body' => 'A',
        ]);

        EmailTemplate::create([
            'user_id' => $otherUser->id,
            'name' => 'Default',
            'body' => 'B',
        ]);

        Livewire::actingAs($owner)
            ->test(EmailTemplateManager::class)
            ->call('createTemplate')
            ->set('name', 'Default')
            ->set('body', 'duplicate')
            ->call('saveTemplate')
            ->assertHasErrors(['name' => 'unique']);

        Livewire::actingAs($owner)
            ->test(EmailTemplateManager::class)
            ->call('deleteTemplate', EmailTemplate::where('user_id', $otherUser->id)->firstOrFail()->id);

        $this->assertDatabaseHas('email_templates', [
            'user_id' => $otherUser->id,
            'name' => 'Default',
        ]);
    }
}
