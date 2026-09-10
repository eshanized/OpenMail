<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallLock
{
    public function handle(Request $request, Closure $next): Response
    {
        $installed = file_exists(storage_path('installed'));

        // If app IS installed and user tries /install → abort 404
        if ($request->is('install*') && $installed) {
            abort(404);
        }

        // If app is NOT installed and user tries any other route → redirect to /install
        if (! $request->is('install*') && ! $installed) {
            // Allow access to login, logout, and Livewire update endpoints during setup
            if (! $request->is('login*') && ! $request->is('logout*') && ! $request->is('livewire*')) {
                return redirect('/install');
            }
        }

        return $next($request);
    }
}
