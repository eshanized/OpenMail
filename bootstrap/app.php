<?php

use App\Http\Middleware\InstallLock;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Csp\AddCspHeaders;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Security headers on all responses (SEC-10)
        $middleware->append(SecurityHeaders::class);

        // CSP headers via spatie/laravel-csp (SEC-03)
        $middleware->append(AddCspHeaders::class);

        $middleware->web(append: [
            InstallLock::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
