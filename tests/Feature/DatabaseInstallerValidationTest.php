<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Install\DatabaseInstaller;
use Tests\TestCase;

class DatabaseInstallerValidationTest extends TestCase
{
    public function test_database_creation_rejects_names_that_contain_no_safe_characters(): void
    {
        $result = app(DatabaseInstaller::class)->createDatabase(
            '127.0.0.1',
            3306,
            '!!!; DROP DATABASE main; --',
            'installer',
            'not-used'
        );

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid database name. Use only letters, numbers, and underscores.', $result['error']);
    }
}
