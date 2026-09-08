<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Log a security-relevant audit event.
     */
    public static function log(
        string $eventType,
        string $description,
        array $metadata = [],
        ?Request $request = null
    ): void {
        $request = $request ?? request();
        $user = Auth::user();

        AuditLog::create([
            'user_id' => $user?->id,
            'event_type' => $eventType,
            'description' => $description,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Log a successful login event.
     */
    public static function loginSuccess(?Request $request = null): void
    {
        self::log('auth.login.success', 'User logged in successfully', [], $request);
    }

    /**
     * Log a failed login event.
     */
    public static function loginFailed(string $email, string $reason, ?Request $request = null): void
    {
        self::log('auth.login.failed', "Login failed: {$reason}", ['email' => $email], $request);
    }

    /**
     * Log an account lockout event.
     */
    public static function lockout(string $email, ?Request $request = null): void
    {
        self::log('auth.lockout', 'Account locked due to failed attempts', ['email' => $email], $request);
    }

    /**
     * Log a mail send/reply/forward event.
     */
    public static function send(string $action, array $metadata = [], ?Request $request = null): void
    {
        self::log("mail.send.{$action}", "Message {$action}", $metadata, $request);
    }
}
