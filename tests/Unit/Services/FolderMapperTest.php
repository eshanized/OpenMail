<?php

namespace Tests\Unit\Services;

use App\Services\FolderMapper;
use Mockery;
use Tests\TestCase;

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

    private function createMockFolder(array $attributes, string $name)
    {
        $mock = Mockery::mock('alias:Webklex\PHPIMAP\Folder')
            ->makePartial();

        $mock->attributes = $attributes;
        $mock->name = $name;
        $mock->path = $name;

        return $mock;
    }
}
