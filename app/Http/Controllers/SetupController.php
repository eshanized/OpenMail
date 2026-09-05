<?php

namespace App\Http\Controllers;

use App\Services\SystemRequirementsChecker;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SetupController extends Controller
{
    public function show(Request $request)
    {
        $installed = file_exists(storage_path('installed'));

        if ($installed) {
            abort(404);
        }

        $requirements = app(SystemRequirementsChecker::class)->check();

        return view('setup.wizard', compact('requirements'));
    }
}