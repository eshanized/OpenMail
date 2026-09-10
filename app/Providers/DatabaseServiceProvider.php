<?php

namespace App\Providers;

use App\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        DB::extend('sqlite_testing', function ($config) {
            return new SQLiteConnection(
                $config['database'],
                $config['prefix'] ?? '',
                $config['foreign_key_constraints'] ?? true,
                $config
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
