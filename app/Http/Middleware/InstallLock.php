<?php

namespace App\Http\Middleware;

use App\Install\InstallationLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallLock
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = app(InstallationLock::class)->isInstalled();

        // If app IS installed and user tries /install → abort 404
        if ($request->is('install*') && $installed) {
            abort(404);
        }

        // If app is NOT installed and user tries any other route → redirect to /install
        if (! $request->is('install*') && ! $installed) {
            // Allow access to Livewire update endpoints and healthcheck during setup
            if (! $request->is('livewire*') && ! $request->is('up')) {
                return redirect('/install');
            }
        }

        return $next($request);
    }
}
