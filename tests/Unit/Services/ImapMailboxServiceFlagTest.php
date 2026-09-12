<?php

namespace Tests\Unit\Services;

use App\Services\ImapMailboxService;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;
use Webklex\PHPIMAP\Query\WhereQuery;
use Webklex\PHPIMAP\Support\MessageCollection;

class ImapMailboxServiceFlagTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_set_flag_strips_leading_backslash_for_set_flag(): void
    {
        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('setFlag')
            ->with('Seen')
            ->once()
            ->andReturn(true);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('whereUidIn')
            ->with([10])
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')
            ->once()
            ->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getFolder')
            ->with('INBOX')
            ->once()
            ->andReturn($mockFolder);

        $service = new class extends ImapMailboxService
        {
            public function setActiveClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->setActiveClient($mockClient);

        // Caller passes '\\Seen'
        $result = $service->setFlag('INBOX', [10], '\\Seen', true);
        $this->assertTrue($result);
    }

    public function test_set_flag_strips_leading_backslash_for_unset_flag(): void
    {
        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('unsetFlag')
            ->with('Flagged')
            ->once()
            ->andReturn(true);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('whereUidIn')
            ->with([20])
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')
            ->once()
            ->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getFolder')
            ->with('INBOX')
            ->once()
            ->andReturn($mockFolder);

        $service = new class extends ImapMailboxService
        {
            public function setActiveClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->setActiveClient($mockClient);

        // Caller passes '\\Flagged' with value false
        $result = $service->setFlag('INBOX', [20], '\\Flagged', false);
        $this->assertTrue($result);
    }

    public function test_set_flag_handles_already_unslashed_flag(): void
    {
        $mockMessage = Mockery::mock(Message::class);
        $mockMessage->shouldReceive('setFlag')
            ->with('Seen')
            ->once()
            ->andReturn(true);

        $mockQuery = Mockery::mock(WhereQuery::class);
        $mockQuery->shouldReceive('whereUidIn')
            ->with([30])
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn(new MessageCollection([$mockMessage]));

        $mockFolder = Mockery::mock(Folder::class);
        $mockFolder->shouldReceive('query')
            ->once()
            ->andReturn($mockQuery);

        $mockClient = Mockery::mock(Client::class);
        $mockClient->shouldReceive('isConnected')->andReturn(true);
        $mockClient->shouldReceive('getFolder')
            ->with('INBOX')
            ->once()
            ->andReturn($mockFolder);

        $service = new class extends ImapMailboxService
        {
            public function setActiveClient(Client $client): void
            {
                $this->activeClient = $client;
            }
        };
        $service->setActiveClient($mockClient);

        // Caller passes 'Seen' without leading backslash
        $result = $service->setFlag('INBOX', [30], 'Seen', true);
        $this->assertTrue($result);
    }

    public function test_set_flag_returns_true_immediately_when_uids_empty(): void
    {
        $service = new ImapMailboxService;
        $result = $service->setFlag('INBOX', [], '\\Seen', true);
        $this->assertTrue($result);
    }
}
