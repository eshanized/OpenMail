<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoginController extends Controller
{
    public function show(Request $request)
    {
        // Check if installed
        if (!file_exists(storage_path('installed'))) {
            return redirect('/install');
        }

        return view('auth.login');
    }
}