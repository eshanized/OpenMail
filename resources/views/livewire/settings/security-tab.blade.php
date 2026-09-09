<div class="space-y-6">
    <section>
        <h3 class="text-lg font-semibold mb-4 text-ink">Active Sessions</h3>
        <p class="text-sm text-ink-tertiary mb-4">These are the devices currently logged into your account.</p>
        
        <div class="glass-card p-6 space-y-3">
            @forelse($sessions as $session)
                <div class="flex items-center justify-between p-4 glass-card rounded-lg {{ $session['is_current'] ? 'border-success/20 bg-success-subtle/50' : '' }}">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            @if($session['is_current'])
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-success-subtle">
                                    <svg class="w-5 h-5 text-success" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-surface-sunken">
                                    <svg class="w-5 h-5 text-ink-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-ink">
                                {{ $session['user_agent'] }}
                                @if($session['is_current'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-success-subtle text-success">
                                        Current Session
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-ink-tertiary">
                                IP: {{ $session['ip_address'] }} · Last active: {{ \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                    <div class="glass-card p-3">
                        <button
                            wire:click="revokeSession({{ $session['id'] }})"
                            wire:confirm="Are you sure you want to revoke this session?"
                            class="px-3 py-1.5 text-sm font-medium text-danger hover:text-danger transition-colors"
                            title="Revoke session"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Revoke
                        </button>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-ink-tertiary">
                    No active sessions found.
                </div>
            @endforelse
        </div>
    </section>

    <section class="border-t border-white/60 dark:border-white/10 pt-6">
        <h3 class="text-lg font-semibold mb-4 text-ink">Session Management</h3>
        <p class="text-sm text-ink-tertiary mb-4">
            Revoke all other sessions to force re-authentication on other devices. Your current session will remain active.
        </p>
        
        @if(count($sessions) > 1)
            @if($showRevokeConfirm)
                <div class="glass-card p-4 border-warning/20 bg-warning-subtle/50 rounded-lg mb-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-warning mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="text-sm text-warning">
                            <strong>Are you sure?</strong> This will log out all other devices immediately.
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-3">
                        <button
                            wire:click="revokeOtherSessions"
                            class="group inline-flex items-center gap-2 rounded-lg
                                       bg-linear-to-r from-red-600 to-red-700
                                       px-4 py-2 text-sm font-semibold text-white
                                       shadow-glow
                                       transition duration-150 ease-out
                                       hover:scale-[1.02] hover:shadow-glow-strong
                                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                                        focus-visible:ring-danger
                                       motion-reduce:transform-none motion-reduce:transition-none">
                            Yes, Revoke All
                        </button>
                        <button
                            wire:click="cancelRevoke"
                            class="px-4 py-2 text-sm font-medium text-ink-secondary bg-surface-raised border border-border rounded-md hover:bg-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary transition-colors">
                            Cancel
                        </button>
                    </div>
                </div>
            @else
                <button
                    wire:click="confirmRevokeOtherSessions"
                    class="group inline-flex items-center gap-2 rounded-lg
                               bg-linear-to-r from-red-600 to-red-700
                               px-4 py-2 text-sm font-semibold text-white
                               shadow-glow
                               transition duration-150 ease-out
                               hover:scale-[1.02] hover:shadow-glow-strong
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-danger
                               motion-reduce:transform-none motion-reduce:transition-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Revoke Other Sessions
                </button>
            @endif
        @else
            <p class="text-sm text-ink-tertiary">
                You're only logged in from this device.
            </p>
        @endif
    </section>
</div>