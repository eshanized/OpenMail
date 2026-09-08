<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Console\Commands\ProcessPendingSends;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('pending-sends:process')->everyMinute()->withoutOverlapping(5);

// Audit log retention: prune entries older than 90 days daily at 02:00
Schedule::command('audit:prune')->dailyAt('02:00');
