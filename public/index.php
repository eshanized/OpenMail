<?php

use App\Install\InstallationBootstrap;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap installer requirements (generates APP_KEY, creates .env if missing).
// This runs BEFORE Laravel boots so the installer can reach the UI even with
// a completely fresh deployment (no .env, no APP_KEY).
if (! file_exists(__DIR__.'/../storage/installed')) {
    require_once __DIR__.'/../app/Install/InstallationBootstrap.php';
    InstallationBootstrap::ensureBootable(dirname(__DIR__));
}

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
