<div class="space-y-8">
    <section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-sm font-semibold text-ink">Active Sessions</h3>
                <p class="text-xs text-ink-tertiary mt-0.5">Devices and browsers currently logged into your OpenMail account.</p>
            </div>
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-surface-sunken border border-border text-ink-secondary">
                {{ count($sessions) }} {{ Str::plural('device', count($sessions)) }}
            </span>
        </div>
        
        <div class="space-y-3">
            @forelse($sessions as $session)
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 p-4 rounded-xl border transition-all duration-150
                    {{ $session['is_current']
                        ? 'border-emerald-500/30 bg-emerald-500/5 dark:bg-emerald-500/10'
                        : 'border-border bg-surface hover:bg-hover' }}">
                    <div class="flex items-center gap-3.5">
                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0
                            {{ $session['is_current']
                                ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 ring-1 ring-emerald-500/20'
                                : 'bg-surface-sunken text-ink-tertiary' }}">
                            @if(str_contains(strtolower($session['user_agent']), 'mobile') || str_contains(strtolower($session['user_agent']), 'iphone') || str_contains(strtolower($session['user_agent']), 'android'))
                                {{-- Phone icon --}}
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"/>
                                </svg>
                            @else
                                {{-- Computer icon --}}
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25"/>
                                </svg>
                            @endif
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-ink">{{ $session['user_agent'] }}</span>
                                @if($session['is_current'])
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/30">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                        This Device
                                    </span>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-ink-tertiary mt-1">
                                <span class="font-mono">{{ $session['ip_address'] }}</span>
                                <span>·</span>
                                <span>Active {{ \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>

                    @if(!$session['is_current'])
                        <div class="self-end sm:self-center shrink-0">
                            <button
                                wire:click="revokeSession({{ $session['id'] }})"
                                wire:confirm="Are you sure you want to revoke this session?"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold text-error hover:bg-error-subtle border border-transparent hover:border-error/20 transition-all cursor-pointer"
                                title="Revoke session"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                Terminate Session
                            </button>
                        </div>
                    @endif
                </div>
            @empty
                <div class="text-center py-8 text-ink-tertiary text-sm">
                    No active sessions found.
                </div>
            @endforelse
        </div>
    </section>

    {{-- Danger Zone / Session Revocation --}}
    <section class="pt-6 border-t border-border-subtle">
        <div class="p-5 rounded-2xl border border-error/20 bg-error-subtle/30 space-y-3">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-error/10 text-error flex items-center justify-center shrink-0 mt-0.5">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h4 class="text-sm font-semibold text-ink">Sign Out Everywhere Else</h4>
                    <p class="text-xs text-ink-secondary mt-0.5">
                        Log out of all other devices and web browsers. Your current active session will remain signed in.
                    </p>
                </div>
            </div>

            @if(count($sessions) > 1)
                @if($showRevokeConfirm)
                    <div class="pt-3 border-t border-error/15 flex items-center justify-between gap-4 flex-wrap">
                        <p class="text-xs font-semibold text-error">Are you sure? All other active logins will immediately be terminated.</p>
                        <div class="flex items-center gap-2">
                            <button
                                wire:click="revokeOtherSessions"
                                class="px-4 py-2 text-xs font-semibold rounded-xl bg-error hover:bg-error/90 text-white shadow-sm transition-all cursor-pointer"
                            >
                                Yes, Log Out Other Devices
                            </button>
                            <button
                                wire:click="cancelRevoke"
                                class="px-3.5 py-2 text-xs font-semibold rounded-xl bg-surface-raised border border-border text-ink hover:bg-hover transition-colors cursor-pointer"
                            >
                                Cancel
                            </button>
                        </div>
                    </div>
                @else
                    <div class="pt-2">
                        <button
                            wire:click="confirmRevokeOtherSessions"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl bg-error hover:bg-error/90 text-white shadow-sm transition-all cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/>
                            </svg>
                            Revoke All Other Sessions
                        </button>
                    </div>
                @endif
            @else
                <p class="text-xs text-ink-tertiary pt-1">
                    No other active devices detected. You are only signed in from this browser.
                </p>
            @endif
        </div>
    </section>
</div>