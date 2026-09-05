<?php

namespace App\Services;

class SystemRequirementsChecker
{
    public function check(): array
    {
        return [
            'php_version' => [
                'label' => 'PHP Version',
                'required' => '8.2',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            'extensions' => $this->checkExtensions(),
            'writable' => $this->checkPermissions(),
            'database' => $this->checkDatabaseDriver(),
        ];
    }

    private function checkExtensions(): array
    {
        $required = ['mbstring', 'openssl', 'pdo', 'pdo_mysql', 'curl', 'xml', 'tokenizer'];
        return array_map(fn($ext) => [
            'label' => "ext-{$ext}",
            'passed' => extension_loaded($ext),
        ], $required);
    }

    private function checkPermissions(): array
    {
        $paths = [
            'storage' => storage_path(),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];
        return array_map(fn($path) => [
            'label' => $path,
            'passed' => is_writable($path),
        ], $paths);
    }

    private function checkDatabaseDriver(): array
    {
        return [
            'label' => 'Database Driver (SQLite)',
            'passed' => extension_loaded('pdo_sqlite'),
        ];
    }
}