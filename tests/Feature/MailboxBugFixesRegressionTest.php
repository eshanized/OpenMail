<?php

namespace Tests\Feature;

use App\Livewire\Mailbox\MessageToolbar;
use App\Livewire\Mailbox\MessageViewer;
use App\Models\Setting;
use App\Models\User;
use App\Services\ComposerService;
use App\Services\ImapMailboxService;
use App\Services\MessageSanitizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class MailboxBugFixesRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_setting_model_handles_both_raw_strings_and_json_structures(): void
    {
        // 1. Raw unencoded string inserted directly into DB
        DB::table('settings')->insert([
            'user_id' => $this->user->id,
            'key' => 'raw_theme',
            'value' => 'dark',
            'group' => 'appearance',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $setting = Setting::where('user_id', $this->user->id)->where('key', 'raw_theme')->first();
        $this->assertNotNull($setting);
        $this->assertEquals('dark', $setting->value);
        $this->assertEquals('dark', $this->user->setting('raw_theme'));

        // 2. Setting complex array
        Setting::setForUser($this->user->id, 'shortcuts', ['send' => 'ctrl+enter', 'archive' => 'e']);
        $retrieved = $this->user->setting('shortcuts');
        $this->assertIsArray($retrieved);
        $this->assertEquals('ctrl+enter', $retrieved['send']);

        // 3. Setting string
        Setting::setForUser($this->user->id, 'theme', 'light');
        $this->assertEquals('light', $this->user->setting('theme'));
    }

    public function test_composer_service_delete_draft_and_sender_envelope(): void
    {
        config(['mail.default' => 'array']);

        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('deleteFromDrafts')
            ->with('12345')
            ->once()
            ->andReturn(true);

        $mockImap->shouldReceive('appendToSent')
            ->once()
            ->andReturn('999');

        $composerService = new ComposerService($mockImap, app(MessageSanitizer::class));

        // Test deleteDraft
        $this->assertTrue($composerService->deleteDraft('12345'));
        $this->assertSame($mockImap, $composerService->getImapService());

        $result = $composerService->sendMessage($this->user->id, [
            'to' => 'bob@example.com',
            'subject' => 'Test Envelope',
            'body' => 'Hello Bob',
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('999', $result['sent_uid']);

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertNotEmpty($messages);
        $last = is_array($messages) ? end($messages) : $messages->last();
        $email = method_exists($last, 'getOriginalMessage') ? $last->getOriginalMessage() : $last;
        $from = method_exists($email, 'getFrom') ? $email->getFrom() : [];
        $this->assertNotEmpty($from);
        $this->assertEquals($this->user->email, $from[0]->getAddress());
    }

    public function test_message_viewer_actions_function_without_unhydrated_message_property(): void
    {
        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('getCachedFolders')->andReturn([]);
        $mockImap->shouldReceive('getMessageWithBody')
            ->with('INBOX', 101)
            ->once()
            ->andReturn((object) [
                'subject' => 'Test Subject',
                'from_address' => 'sender@example.com',
                'from_name' => 'Sender',
                'date' => now(),
            ]);

        $mockImap->shouldReceive('setFlag')
            ->with('INBOX', [101], '\\Seen', true)
            ->atLeast()->once();

        $mockImap->shouldReceive('setFlag')
            ->with('INBOX', [101], '\\Seen', false)
            ->once();

        $mockImap->shouldReceive('setFlag')
            ->with('INBOX', [101], '\\Flagged', true)
            ->once();

        $mockImap->shouldReceive('refreshFolderCache')
            ->with('INBOX')
            ->atLeast()->once();

        $this->app->instance(ImapMailboxService::class, $mockImap);

        $component = Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 101,
            ]);

        // In subsequent livewire requests, protected $message is null
        // Call toggleRead (transitions false -> true)
        $component->call('toggleRead');
        $this->assertTrue($component->get('isSeen'));

        // Call toggleRead again (transitions true -> false)
        $component->call('toggleRead');
        $this->assertFalse($component->get('isSeen'));

        // Call toggleStar
        $component->call('toggleStar');
        $this->assertTrue($component->get('isFlagged'));
    }

    public function test_message_viewer_delete_and_archive_redirect_to_mailbox_folder(): void
    {
        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('getCachedFolders')->andReturn([]);
        $mockImap->shouldReceive('getMessageWithBody')
            ->with('INBOX', 101)
            ->once()
            ->andReturn((object) [
                'subject' => 'Test Subject',
                'from_address' => 'sender@example.com',
                'from_name' => 'Sender',
            ]);

        $mockImap->shouldReceive('setFlag')
            ->with('INBOX', [101], '\\Seen', true)
            ->once();

        $mockImap->shouldReceive('deleteMessages')
            ->with('INBOX', [101])
            ->once()
            ->andReturn(true);

        $mockImap->shouldReceive('refreshFolderCache')
            ->with('INBOX')
            ->atLeast()->once();

        $this->app->instance(ImapMailboxService::class, $mockImap);

        Livewire::actingAs($this->user)
            ->test(MessageViewer::class, [
                'folderPath' => 'INBOX',
                'uid' => 101,
            ])
            ->call('deleteMessage')
            ->assertRedirect(route('mailbox.folder', ['folderPath' => 'INBOX']));
    }

    public function test_message_toolbar_bulk_actions_dispatch_events_and_refresh_cache(): void
    {
        $mockImap = Mockery::mock(ImapMailboxService::class);
        $mockImap->shouldReceive('getCachedFolders')->andReturn([]);
        $mockImap->shouldReceive('setFlag')
            ->with('INBOX', [1, 2, 3], '\\Seen', true)
            ->once();
        $mockImap->shouldReceive('deleteMessages')
            ->with('INBOX', [1, 2, 3])
            ->once();
        $mockImap->shouldReceive('refreshFolderCache')
            ->with('INBOX')
            ->atLeast()->once();

        $this->app->instance(ImapMailboxService::class, $mockImap);

        Livewire::actingAs($this->user)
            ->test(MessageToolbar::class, [
                'folderPath' => 'INBOX',
                'selectedUids' => [1, 2, 3],
            ])
            ->call('bulkMarkRead')
            ->assertDispatched('folder-stats-updated')
            ->assertDispatched('selection-cleared')
            ->call('bulkDelete')
            ->assertDispatched('folder-stats-updated')
            ->assertDispatched('selection-cleared');
    }
}
