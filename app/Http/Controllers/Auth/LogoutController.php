<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LogoutController extends Controller
{
    public function logout(Request $request)
    {
        AuditService::log('auth.logout', 'User logged out', [], $request);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Revoke all other sessions for the authenticated user (SET-05).
     */
    public function revokeOtherSessions(Request $request)
    {
        $user = $request->user();

        DB::table('sessions')
            ->where('user_id', $user->id)
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        AuditService::log('auth.sessions.revoked', 'Revoked all other sessions', [], $request);

        return back()->with('success', 'All other sessions revoked.');
    }
}
