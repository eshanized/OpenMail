<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\File;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $lockFile = storage_path('installed');
        if (! File::exists($lockFile)) {
            File::put($lockFile, json_encode(['installed_at' => now()->toIso8601String()]));
        }
    }
}
