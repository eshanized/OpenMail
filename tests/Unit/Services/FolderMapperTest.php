<?php

namespace Tests\Unit\Services;

use App\Services\FolderMapper;
use Mockery;
use Tests\TestCase;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Support\FolderCollection;

class FolderMapperTest extends TestCase
{
    private FolderMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mapper = new FolderMapper;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_inbox_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Inbox'], 'INBOX');
        $this->assertEquals('inbox', $this->mapper->mapFolderRole($folder));
    }

    public function test_sent_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Sent'], 'Sent');
        $this->assertEquals('sent', $this->mapper->mapFolderRole($folder));
    }

    public function test_drafts_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Drafts'], 'Drafts');
        $this->assertEquals('drafts', $this->mapper->mapFolderRole($folder));
    }

    public function test_trash_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Trash'], 'Trash');
        $this->assertEquals('trash', $this->mapper->mapFolderRole($folder));
    }

    public function test_junk_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Junk'], 'Junk');
        $this->assertEquals('spam', $this->mapper->mapFolderRole($folder));
    }

    public function test_archive_special_use_attribute(): void
    {
        $folder = $this->createMockFolder(['\\Archive'], 'Archive');
        $this->assertEquals('archive', $this->mapper->mapFolderRole($folder));
    }

    public function test_name_heuristic_sent_mail(): void
    {
        $folder = $this->createMockFolder([], 'Sent Mail');
        $this->assertEquals('sent', $this->mapper->mapFolderRole($folder));
    }

    public function test_name_heuristic_deleted_items(): void
    {
        $folder = $this->createMockFolder([], 'Deleted Items');
        $this->assertEquals('trash', $this->mapper->mapFolderRole($folder));
    }

    public function test_name_heuristic_junk(): void
    {
        $folder = $this->createMockFolder([], 'Junk');
        $this->assertEquals('spam', $this->mapper->mapFolderRole($folder));
    }

    public function test_custom_folder_returns_null(): void
    {
        $folder = $this->createMockFolder([], 'Projects');
        $this->assertNull($this->mapper->mapFolderRole($folder));
    }

    public function test_inbox_path_always_inbox(): void
    {
        $folder = $this->createMockFolder([], 'INBOX');
        $this->assertEquals('inbox', $this->mapper->mapFolderRole($folder));
    }

    public function test_map_folder_name_with_role(): void
    {
        $folder = $this->createMockFolder(['\\Sent'], 'Sent Mail');
        $this->assertEquals('Sent', $this->mapper->mapFolderName($folder));
    }

    public function test_map_folder_name_custom(): void
    {
        $folder = $this->createMockFolder([], 'My Custom Folder');
        $this->assertEquals('My Custom Folder', $this->mapper->mapFolderName($folder));
    }

    public function test_get_trash_folder_path_special_use(): void
    {
        $folder = $this->createMockFolder(['\\Trash'], 'MyTrash');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getFolders')->once()->andReturn(new FolderCollection([$folder]));

        $this->assertEquals('MyTrash', $this->mapper->getTrashFolderPath($client));
    }

    public function test_get_trash_folder_path_heuristic(): void
    {
        $folder = $this->createMockFolder([], 'Deleted Items');
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('getFolders')->once()->andReturn(new FolderCollection([$folder]));

        $this->assertEquals('Deleted Items', $this->mapper->getTrashFolderPath($client));
    }

    private function createMockFolder(array $attributes, string $name): Folder
    {
        $ref = new \ReflectionClass(Folder::class);
        $folder = $ref->newInstanceWithoutConstructor();

        $folder->attributes = $attributes;
        $folder->name = $name;
        $folder->path = $name;

        return $folder;
    }
}
