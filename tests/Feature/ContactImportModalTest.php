<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Livewire\Mailbox\ContactImportModal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContactImportModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_import_warns_when_no_contacts_are_selected(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ContactImportModal::class)
            ->call('confirmImport')
            ->assertSet('isProcessing', false)
            ->assertDispatched('toast');
    }

    public function test_select_all_and_deselect_all_follow_the_select_all_flag(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ContactImportModal::class)
            ->set('preview', [
                ['index' => 1, 'name' => 'First', 'email' => 'first@example.test', 'phone' => ''],
                ['index' => 4, 'name' => 'Second', 'email' => 'second@example.test', 'phone' => ''],
            ])
            ->call('toggleSelectAll')
            ->assertSet('selectedContacts', [1, 4])
            ->set('selectAll', false)
            ->call('toggleSelectAll')
            ->assertSet('selectedContacts', []);
    }

    public function test_parse_preview_reports_a_missing_or_unreadable_upload(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ContactImportModal::class)
            ->call('parsePreview')
            ->assertDispatched('toast');
    }

    public function test_import_failure_always_clears_the_processing_state(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ContactImportModal::class)
            ->set('selectedContacts', [1])
            ->call('confirmImport')
            ->assertSet('isProcessing', false)
            ->assertDispatched('toast');
    }

    public function test_export_warns_when_the_user_has_no_contacts(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ContactImportModal::class)
            ->call('exportVCard')
            ->assertDispatched('toast');
    }

    public function test_close_modal_updates_state_and_dispatches_close_event(): void
    {
        Livewire::actingAs(User::factory()->create())
            ->test(ContactImportModal::class)
            ->call('closeModal')
            ->assertSet('showModal', false)
            ->assertDispatched('closeImportModal');
    }
}
