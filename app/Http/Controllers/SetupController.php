<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Install\InstallationLock;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function show(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $lock = app(InstallationLock::class);

        if ($lock->isInstalled()) {
            abort(404);
        }

        // Render the page that embeds <livewire:setup-wizard />
        return view('setup.page');
    }
}