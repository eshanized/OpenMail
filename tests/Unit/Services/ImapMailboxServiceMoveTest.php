<?php

namespace Tests\Unit\Services;

use App\Services\ImapMailboxService;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\FolderCollection;
use Webklex\PHPIMAP\Support\MessageCollection;

class ImapMailboxServiceMoveTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_move_messages_passes_string_path_to_message_move(): void
    {
        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('move')
            ->with('Archive')
            ->once()
            ->andReturn($mockMessage);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('whereUidIn')
            ->with([101])
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn(new MessageCollection([$mockMessage]));

        $mockSourceFolder = Mockery::mock(Folder::class);
        $mockSourceFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockDestFolder = Mockery::mock(Folder::class);
        $mockDestFolder->path = 'Archive';

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getFolder')->with('INBOX')->once()->andReturn($mockSourceFolder);
        $mockClient->shouldReceive('getFolder')->with('Archive')->once()->andReturn($mockDestFolder);

        $service = new class extends ImapMailboxService
        {
            public function setActiveClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->setActiveClient($mockClient);

        $result = $service->moveMessages('INBOX', [101], 'Archive');
        $this->assertTrue($result);
    }

    public function test_delete_messages_permanently_expunges_if_already_in_trash(): void
    {
        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('delete')
            ->with(true)
            ->once()
            ->andReturn(true);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('whereUidIn')
            ->with([202])
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn(new MessageCollection([$mockMessage]));

        $mockTrashFolder = Mockery::mock(Folder::class);
        $mockTrashFolder->path = 'Trash';
        $mockTrashFolder->attributes = ['\\Trash'];
        $mockTrashFolder->shouldReceive('query')->once()->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getFolders')->once()->andReturn(new FolderCollection([$mockTrashFolder]));
        $mockClient->shouldReceive('getFolder')->with('Trash')->once()->andReturn($mockTrashFolder);

        $service = new class extends ImapMailboxService
        {
            public function setActiveClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->setActiveClient($mockClient);

        $result = $service->deleteMessages('Trash', [202]);
        $this->assertTrue($result);
    }
}
