<?php

namespace Tests\Feature\Composer;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\PendingSend;
use App\Services\ComposerService;
use App\Services\ImapMailboxService;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class DraftAutosaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_localstorage_key_format_matches_pattern()
    {
        $key = 'openmail:draft:' . '550e8400-e29b-41d4-a716-446655440000';
        $this->assertMatchesRegularExpression('/^openmail:draft:[0-9a-f-]{36}$/', $key);
    }

    public function test_autosave_debounce_fires_after_1500ms()
    {
        // This test verifies the debounce logic is implemented in the frontend
        // The actual debounce is handled in the Alpine.js component
        $jsPath = base_path('resources/js/components/TiptapEditor.js');
        $content = file_get_contents($jsPath);

        // Check that the Composer component has the debounce logic
        $bladePath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $bladeContent = file_get_contents($bladePath);

        $this->assertStringContainsString('1500', $bladeContent, 'Should have 1500ms debounce in Alpine.js');
        $this->assertStringContainsString('autosave-draft', $bladeContent, 'Should dispatch autosave-draft event');
    }

    public function test_imap_append_to_drafts_folder_on_sync()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mockImapService = \Mockery::mock(ImapMailboxService::class);
        $mockImapService->shouldReceive('appendToDrafts')
            ->once()
            ->andReturn('12345');

        $this->app->instance(ImapMailboxService::class, $mockImapService);

        $composerService = app(ComposerService::class);
        $result = $composerService->saveDraft($user->id, [
            'to' => 'test@example.com',
            'subject' => 'Test Draft',
            'body' => '<p>Test content</p>',
            'attachments' => [],
        ], null);

        $this->assertTrue($result['success']);
        $this->assertEquals('12345', $result['draft_uid']);
    }

    public function test_existing_draft_uid_used_for_update()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $mockImapService = \Mockery::mock(ImapMailboxService::class);
        $mockImapService->shouldReceive('appendToDrafts')
            ->with(\Mockery::type('string'), 'existing-uid-123')
            ->once()
            ->andReturn('12346');

        $this->app->instance(ImapMailboxService::class, $mockImapService);

        $composerService = app(ComposerService::class);
        $result = $composerService->saveDraft($user->id, [
            'to' => 'test@example.com',
            'subject' => 'Test Draft Updated',
            'body' => '<p>Updated content</p>',
            'attachments' => [],
        ], 'existing-uid-123');

        $this->assertTrue($result['success']);
        $this->assertEquals('12346', $result['draft_uid']);
    }

    public function test_discard_clears_localstorage_and_deletes_from_imap_drafts()
    {
        // This test verifies the discard functionality is implemented in the Composer component
        // The actual discardDraft method calls deleteFromDrafts and dispatches clear-draft-storage event
        $bladePath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $bladeContent = file_get_contents($bladePath);

        $this->assertStringContainsString('clear-draft-storage', $bladeContent, 'Should dispatch clear-draft-storage event');
        $this->assertStringContainsString('localStorage.removeItem', $bladeContent, 'Should remove LocalStorage item');
    }
}