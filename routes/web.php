<?php

use App\Http\Controllers\SetupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Livewire\Mailbox\FolderSidebar;
use App\Livewire\Mailbox\MessageList;
use App\Livewire\Mailbox\MessageViewer;
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

    Route::get('/mailbox/{folderPath}/{uid}', function (string $folderPath, int $uid) {
        return view('mailbox', [
            'folderPath' => $folderPath,
            'uid' => $uid,
        ]);
    })->name('message.show')->where('folderPath', '.*')->where('uid', '[0-9]+');

    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])->name('search');

    Route::get('/labels/{label}', function (string $labelId) {
        $label = \App\Models\Label::where('id', $labelId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('mailbox', [
            'labelFilter' => $label,
        ]);
    })->name('labels.show')->where('labelId', '[0-9]+');
});

Route::get('/up', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
});