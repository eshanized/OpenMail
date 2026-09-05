<?php

use App\Http\Controllers\SetupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (!file_exists(storage_path('installed'))) {
        return redirect('/install');
    }
    return redirect('/login');
});

Route::get('/install', [SetupController::class, 'show'])->name('install');
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/mailbox', function () {
        return view('mailbox');
    })->name('mailbox');
});

Route::get('/up', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
});