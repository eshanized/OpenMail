<div class="space-y-6">
    <section>
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Active Sessions</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">These are the devices currently logged into your account.</p>
        
        <div class="space-y-3">
            @forelse($sessions as $session)
                <div class="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg {{ $session['is_current'] ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 'bg-white dark:bg-gray-800' }}">
                    <div class="flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            @if($session['is_current'])
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-green-100 dark:bg-green-900">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </span>
                            @else
                                <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-100 dark:bg-gray-700">
                                    <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>
                        <div>
                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $session['user_agent'] }}
                                @if($session['is_current'])
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Current Session
                                    </span>
                                @endif
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                IP: {{ $session['ip_address'] }} · Last active: {{ \Carbon\Carbon::parse($session['last_activity'])->diffForHumans() }}
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-6 text-gray-500 dark:text-gray-400">
                    No active sessions found.
                </div>
            @endforelse
        </div>
    </section>

    <section class="border-t border-gray-200 dark:border-gray-700 pt-6">
        <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Session Management</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
            Revoke all other sessions to force re-authentication on other devices. Your current session will remain active.
        </p>
        
        @if(count($sessions) > 1)
            @if($showRevokeConfirm)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4 mb-4">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                        <div class="text-sm text-yellow-800 dark:text-yellow-200">
                            <strong>Are you sure?</strong> This will log out all other devices immediately.
                        </div>
                    </div>
                    <div class="mt-4 flex space-x-3">
                        <button wire:click="revokeOtherSessions" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                            Yes, Revoke All
                        </button>
                        <button wire:click="cancelRevoke" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Cancel
                        </button>
                    </div>
                </div>
            @else
                <button wire:click="confirmRevokeOtherSessions" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Revoke Other Sessions
                </button>
            @endif
        @else
            <p class="text-sm text-gray-500 dark:text-gray-400">
                You're only logged in from this device.
            </p>
        @endif
    </section>
</div>
