<div class="space-y-6">
    <section>
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Active Sessions</h3>
                <p class="text-xs text-ink-tertiary mt-0.5">Devices currently logged into this account.</p>
            </div>
            <span class="text-xs text-ink-tertiary tabular-nums">
                {{ count($sessions) }} {{ Str::plural('device', count($sessions)) }}
            </span>
        </div>

        <div class="space-y-2">
            @forelse($sessions as $session)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded border transition-colors
                    {{ $session['is_current']
                        ? 'border-primary/30 bg-primary-subtle'
                        : 'border-border bg-surface-raised hover:bg-hover' }}">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded flex items-center justify-center shrink-0 bg-surface border border-border text-ink-secondary">
                            @if(str_contains(strtolower($session['user_agent']), 'mobile') || str_contains(strtolower($session['user_agent']), 'iphone') || str_contains(strtolower($session['user_agent']), 'android'))
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-ink">{{ $session['user_agent'] }}</span>
                                @if($session['is_current'])
                                    <span class="text-[10px] font-medium text-primary bg-primary-subtle border border-primary/20 px-1.5 py-0.5 rounded">
                                        This device
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 text-[11px] text-ink-tertiary mt-0.5">
                                <span class="font-mono">{{ $session['ip_address'] }}</span>
                                <span>&middot;</span>
                                <span>Active {{ \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    @if(!$session['is_current'])
                        <div class="self-end sm:self-center shrink-0">
                            <button
                                wire:click="revokeSession({{ $session['id'] }})"
                                wire:confirm="Are you sure you want to revoke this session?"
                                class="px-2 py-1 rounded text-xs text-error hover:bg-error-subtle border border-error/20 transition-colors cursor-pointer"
                                title="Revoke session"
                            >
                                Terminate
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-6 text-ink-tertiary text-xs">
                    No active sessions found.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Danger Zone / Session Revocation --}}
    <section class="pt-4 border-t border-border">
        <div class="p-4 rounded border border-error/25 bg-error-subtle space-y-3">
            <div>
                <h4 class="text-xs font-semibold text-error uppercase tracking-wider">Sign Out Everywhere Else</h4>
                <p class="text-xs text-ink-secondary mt-0.5">
                    Log out of all other devices and web browsers. Your current active session will remain signed in.
                </p>
            </div>

            @if(count($sessions) > 1)
                @if($showRevokeConfirm)
                    <div class="pt-2 border-t border-error/20 flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-xs font-medium text-error">Are you sure? All other active logins will immediately be terminated.</p>
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="revokeOtherSessions"
                                class="px-3 py-1.5 text-xs font-medium rounded bg-error hover:bg-error/90 text-white transition-colors cursor-pointer"
                            >
                                Yes, log out other devices
                            </button>
                            <button
                                wire:click="cancelRevoke"
                                class="px-2.5 py-1.5 text-xs text-ink-secondary hover:text-ink border border-border rounded bg-surface-raised transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                @else
                    <div>
                        <button
                            wire:click="confirmRevokeOtherSessions"
                            class="px-3 py-1.5 text-xs font-medium rounded bg-error hover:bg-error/90 text-white transition-colors cursor-pointer"
                        >
                            Revoke all other sessions
                        </button>
                    </div>
                @endif
            @else
                <p class="text-xs text-ink-tertiary">
                    No other active devices detected. You are only signed in from this browser.
                </p>
            @endif
        </div>
    </section>
</div>