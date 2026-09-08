<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class SecurityTab extends Component
{
    public array $sessions = [];
    public ?string $currentSessionId = null;
    public bool $showRevokeConfirm = false;

    public function mount(): void
    {
        $this->currentSessionId = session()->getId();
        $this->loadSessions();
    }

    public function loadSessions(): void
    {
        $userId = auth()->id();
        
        $this->sessions = DB::table('sessions')
            ->where('user_id', $userId)
            ->orderBy('last_activity', 'desc')
            ->get()
            ->map(fn($session) => [
                'id' => $session->id,
                'ip_address' => $session->ip_address ?? 'Unknown',
                'user_agent' => $this->parseUserAgent($session->user_agent ?? ''),
                'last_activity' => $session->last_activity,
                'is_current' => $session->id === $this->currentSessionId,
            ])
            ->toArray();
    }

    public function confirmRevokeOtherSessions(): void
    {
        $this->showRevokeConfirm = true;
    }

    public function cancelRevoke(): void
    {
        $this->showRevokeConfirm = false;
    }

    public function revokeOtherSessions(): void
    {
        $userId = auth()->id();
        $currentSessionId = $this->currentSessionId;

        DB::table('sessions')
            ->where('user_id', $userId)
            ->where('id', '!=', $currentSessionId)
            ->delete();

        $this->showRevokeConfirm = false;
        $this->loadSessions();

        $this->dispatch('toast', message: 'All other sessions have been revoked', type: 'success');
    }

    private function parseUserAgent(string $userAgent): string
    {
        if (empty($userAgent)) {
            return 'Unknown Device';
        }

        // Simple user agent parsing
        if (str_contains($userAgent, 'Windows')) {
            $os = 'Windows';
        } elseif (str_contains($userAgent, 'Mac')) {
            $os = 'macOS';
        } elseif (str_contains($userAgent, 'Linux')) {
            $os = 'Linux';
        } elseif (str_contains($userAgent, 'Android')) {
            $os = 'Android';
        } elseif (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            $os = 'iOS';
        } else {
            $os = 'Unknown OS';
        }

        if (str_contains($userAgent, 'Chrome') && !str_contains($userAgent, 'Edg')) {
            $browser = 'Chrome';
        } elseif (str_contains($userAgent, 'Firefox')) {
            $browser = 'Firefox';
        } elseif (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) {
            $browser = 'Safari';
        } elseif (str_contains($userAgent, 'Edg')) {
            $browser = 'Edge';
        } else {
            $browser = 'Unknown Browser';
        }

        return "{$browser} on {$os}";
    }

    public function render()
    {
        return view('livewire.settings.security-tab');
    }
}
