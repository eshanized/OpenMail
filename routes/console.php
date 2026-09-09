<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ProcessPendingSends;
use App\Console\Commands\InstallCommand;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('openmail:install', function () {
    $this->call(InstallCommand::class);
})->purpose('Install OpenMail via CLI');

Schedule::command('pending-sends:process')->everyMinute()->withoutOverlapping(5);

// Audit log retention: prune entries older than 90 days daily at 02:00
Schedule::command('audit:prune')->dailyAt('02:00');
