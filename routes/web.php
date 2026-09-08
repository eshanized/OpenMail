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
    Route::get('/settings', \App\Livewire\Settings\SettingsPage::class)
        ->middleware('throttle:api.settings')
        ->name('settings');

    Route::get('/mailbox', function () {
        $defaultFolder = auth()->user()->setting('default_folder', 'INBOX');
        return redirect()->route('mailbox.folder', ['folderPath' => $defaultFolder]);
    })->name('mailbox');

    Route::get('/mailbox/{folderPath}', function (string $folderPath) {
        return view('mailbox', [
            'folderPath' => $folderPath,
        ]);
    })->name('mailbox.folder')->where('folderPath', '.*');

    Route::get('/mailbox/{folderPath}/{uid}', function (string $folderPath, int $uid) {
        return view('mailbox', [
            'folderPath' => $folderPath,
            'uid' => $uid,
        ]);
    })->name('message.show')->where('folderPath', '.*')->where('uid', '[0-9]+');

    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'index'])
        ->middleware('throttle:api.search')
        ->name('search');

    Route::get('/labels/{label}', function (string $labelId) {
        $label = \App\Models\Label::where('id', $labelId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        return view('mailbox', [
            'labelFilter' => $label,
        ]);
    })->name('labels.show')->where('labelId', '[0-9]+');

    // Signature CRUD routes (SET-02)
    Route::get('/settings/signatures', function () {
        return redirect()->route('settings');
    })->name('settings.signatures');

    // Session revocation route (SET-05)
    Route::post('/settings/sessions/revoke', [\App\Http\Controllers\Auth\LogoutController::class, 'revokeOtherSessions'])
        ->middleware('throttle:10,1')
        ->name('settings.sessions.revoke');

    // API routes with authenticated rate limiting (SEC-04)
    // Compose actions go through Livewire's update endpoint (throttled via Livewire middleware)
    // Search and Settings GET routes have per-route throttle middleware above
});

Route::get('/up', function () {
    return response('OK', 200)->header('Content-Type', 'text/plain');
});

// CSP violation reports (SEC-03, D-03) — rate-limited to prevent flooding
Route::post('/csp-report', [\App\Http\Controllers\CspReportController::class, 'store'])
    ->name('csp.report')
    ->middleware('throttle:60,1');