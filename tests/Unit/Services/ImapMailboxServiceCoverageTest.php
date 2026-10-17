<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\ImapMailboxService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;

class ImapMailboxServiceCoverageTest extends TestCase
{
    private function serviceFor(Client $client): ImapMailboxService
    {
        $service = new class extends ImapMailboxService
        {
            public function injectClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->injectClient($client);

        return $service;
    }

    private function connectedClient(): Client
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('isConnected')->andReturn(true);

        return $client;
    }

    public function test_get_message_returns_null_for_missing_folder_and_message_for_existing_uid(): void
    {
        $client = $this->connectedClient();
        $client->shouldReceive('getFolder')->with('Missing')->once()->andReturn(null);
        $folder = Mockery::mock(Folder::class);
        $message = Mockery::mock(Message::class);
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('getMessageByUid')->with(42)->once()->andReturn($message);
        $folder->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($folder);

        $service = $this->serviceFor($client);
        $this->assertNull($service->getMessage('Missing', 42));
        $this->assertSame($message, $service->getMessage('INBOX', 42));
    }

    public function test_get_message_with_body_requests_body_and_returns_the_message(): void
    {
        $client = $this->connectedClient();
        $folder = Mockery::mock(Folder::class);
        $message = Mockery::mock(Message::class);
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('setFetchBody')->with(true)->once()->andReturnSelf();
        $query->shouldReceive('getMessageByUid')->with(7)->once()->andReturn($message);
        $folder->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($folder);

        $this->assertSame($message, $this->serviceFor($client)->getMessageWithBody('INBOX', 7));
    }

    public function test_get_attachment_returns_null_for_missing_index_and_requested_attachment_for_match(): void
    {
        $client = $this->connectedClient();
        $folder = Mockery::mock(Folder::class);
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('setFetchBody')->with(true)->twice()->andReturnSelf();
        $missingAttachmentMessage = Mockery::mock(Message::class);
        $missingAttachments = new class
        {
            public function get(int $index): ?string
            {
                return null;
            }
        };
        $missingAttachmentMessage->shouldReceive('getAttachments')->once()->andReturn($missingAttachments);
        $query->shouldReceive('getMessageByUid')->with(8)->once()->andReturn($missingAttachmentMessage);

        $message = Mockery::mock(Message::class);
        $attachments = new class
        {
            public function get(int $index): ?string
            {
                return $index === 1 ? 'downloadable-attachment' : null;
            }
        };
        $message->shouldReceive('getAttachments')->once()->andReturn($attachments);
        $query->shouldReceive('getMessageByUid')->with(9)->once()->andReturn($message);
        $folder->shouldReceive('query')->twice()->andReturn($query);
        $client->shouldReceive('getFolder')->with('INBOX')->twice()->andReturn($folder);

        $service = $this->serviceFor($client);
        $this->assertNull($service->getAttachment('INBOX', 8, 0));
        $this->assertSame('downloadable-attachment', $service->getAttachment('INBOX', 9, 1));
    }

    public function test_get_message_count_returns_total_unread_and_uidvalidity(): void
    {
        $client = $this->connectedClient();
        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('examine')->once()->andReturn(['exists' => 27, 'uidvalidity' => 440]);
        $query = Mockery::mock(WhereQuery::class);
        $query->shouldReceive('unseen')->once()->andReturnSelf();
        $query->shouldReceive('setFetchBody')->with(false)->once()->andReturnSelf();
        $query->shouldReceive('count')->once()->andReturn(6);
        $folder->shouldReceive('query')->once()->andReturn($query);
        $client->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($folder);

        $this->assertSame(
            ['total' => 27, 'unread' => 6, 'uidvalidity' => 440],
            $this->serviceFor($client)->getMessageCount('INBOX')
        );
    }

    public function test_get_messages_returns_an_empty_paginator_if_folder_is_missing(): void
    {
        $client = $this->connectedClient();
        $client->shouldReceive('getFolder')->with('Missing')->once()->andReturn(null);

        $paginator = $this->serviceFor($client)->getMessages('Missing', 'date', 'desc', 2, 10);

        $this->assertSame(0, $paginator->total());
        $this->assertSame(10, $paginator->perPage());
        $this->assertSame(2, $paginator->currentPage());
        $this->assertCount(0, $paginator->items());
    }

    public function test_create_rename_and_delete_folder_forward_expected_paths(): void
    {
        $client = $this->connectedClient();
        $client->shouldReceive('createFolder')->with('Projects.Child')->once();
        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('rename')->with('Projects.Renamed')->once();
        $folder->shouldReceive('delete')->once();
        $client->shouldReceive('getFolder')->with('Projects.Child')->once()->andReturn($folder);
        $client->shouldReceive('getFolder')->with('Projects.Renamed')->once()->andReturn($folder);

        $service = $this->serviceFor($client);
        $this->assertTrue($service->createFolder('Child', 'Projects'));
        $this->assertTrue($service->renameFolder('Projects.Child', 'Renamed'));
        $this->assertTrue($service->deleteFolder('Projects.Renamed'));
    }

    public function test_append_message_adds_seen_flag_and_extracts_uid(): void
    {
        $client = $this->connectedClient();
        $folder = Mockery::mock(Folder::class);
        $folder->shouldReceive('appendMessage')->with('raw-mime', ['\\Seen', '\\Draft'], null)->once()->andReturn(['uid' => 123]);
        $client->shouldReceive('getFolder')->with('Drafts')->once()->andReturn($folder);

        $this->assertSame('123', $this->serviceFor($client)->appendMessage('Drafts', 'raw-mime', ['\\Draft']));
    }

    public function test_append_message_throws_if_the_target_folder_does_not_exist(): void
    {
        $client = $this->connectedClient();
        $client->shouldReceive('getFolder')->with('Missing')->once()->andReturn(null);
        $client->shouldReceive('disconnect')->once();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Folder not found: Missing');

        $this->serviceFor($client)->appendMessage('Missing', 'raw-mime');
    }

    public function test_invalid_draft_and_sent_uids_are_rejected_without_opening_imap(): void
    {
        $service = new ImapMailboxService;

        $this->assertFalse($service->deleteFromDrafts('not-numeric'));
        $this->assertFalse($service->deleteFromSent('not-numeric'));
    }

    public function test_thread_header_lookup_short_circuits_for_empty_uid_list(): void
    {
        $this->assertSame([], (new ImapMailboxService)->getThreadHeaders('INBOX', []));
    }

    public function test_disconnect_closes_the_active_client_and_clears_it(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('isConnected')->once()->andReturn(true);
        $client->shouldReceive('disconnect')->once();

        $service = $this->serviceFor($client);
        $service->disconnect();

        $activeClient = new ReflectionProperty(ImapMailboxService::class, 'activeClient');
        $this->assertNull($activeClient->getValue($service));
    }

    public function test_folder_cache_refresh_forgets_both_folder_keys(): void
    {
        Cache::flush();
        $user = new User;
        auth()->setUser($user);
        $folderKey = 'folder:INBOX:'.$user->id;
        $foldersKey = 'folders:'.$user->id;
        Cache::put($folderKey, ['total' => 5]);
        Cache::put($foldersKey, [['path' => 'INBOX']]);

        (new ImapMailboxService)->refreshFolderCache('INBOX');

        $this->assertFalse(Cache::has($folderKey));
        $this->assertFalse(Cache::has($foldersKey));
    }

    public function test_cached_folder_list_avoids_repeated_source_calls(): void
    {
        Cache::flush();
        $service = new class extends ImapMailboxService
        {
            public int $calls = 0;

            public function getFolders(): array
            {
                $this->calls++;

                return [['path' => 'INBOX']];
            }
        };

        $first = $service->getCachedFolders();
        $second = $service->getCachedFolders();

        $this->assertSame($first, $second);
        $this->assertSame(1, $service->calls);
    }
}
