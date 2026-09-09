<div class="p-8 sm:p-10">
    {{-- Header --}}
    <div class="text-center">
        <div class="flex justify-center mb-4">
            <div class="w-16 h-16 bg-primary-subtle rounded-full flex items-center justify-center">
                <svg class="w-8 h-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-ink">Welcome to OpenMail</h1>
        <p class="mt-3 text-ink-secondary max-w-md mx-auto">
            A modern, self-hosted webmail platform for organizations.
            This wizard will guide you through a complete installation in a few steps.
        </p>
    </div>

    {{-- Installation mode notice --}}
    <div class="mt-8">
        @if ($installMode === 'continue')
            <div class="p-4 bg-warning-subtle/50 border border-warning/20 rounded-lg" role="status">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-warning mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-warning">Existing configuration detected</p>
                        <p class="mt-1 text-sm text-warning">A partial configuration already exists. You can continue the installation or start over.</p>
                    </div>
                </div>
            </div>
        @else
            <div class="p-4 bg-success-subtle/50 border border-success/20 rounded-lg" role="status">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-sm text-success">Fresh installation &mdash; no existing configuration found.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- What you'll need --}}
    <div class="mt-8 grid sm:grid-cols-3 gap-4">
        <div class="p-4 border border-border rounded-lg">
            <div class="text-primary mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4m0 0c4.418 0 8-1.79 8-4"/>
                </svg>
            </div>
            <p class="font-medium text-ink text-sm">MySQL Database</p>
            <p class="text-xs text-ink-tertiary mt-1">Host, port, database name, username &amp; password</p>
        </div>
        <div class="p-4 border border-border rounded-lg">
            <div class="text-primary mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <p class="font-medium text-ink text-sm">Mail Server Credentials</p>
            <p class="text-xs text-ink-tertiary mt-1">IMAP &amp; SMTP host, port, username &amp; password</p>
        </div>
        <div class="p-4 border border-border rounded-lg">
            <div class="text-primary mb-2">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
            </div>
            <p class="font-medium text-ink text-sm">Administrator Account</p>
            <p class="text-xs text-ink-tertiary mt-1">Name, email address &amp; secure password</p>
        </div>
    </div>

    {{-- App name --}}
    <div class="mt-8">
        <label for="app_name_welcome" class="block text-sm font-medium text-ink-secondary">
            Application Name
            <span class="text-ink-tertiary font-normal">(you can change this later)</span>
        </label>
        <input type="text"
               id="app_name_welcome"
               wire:model="appName"
               class="mt-1 block w-full max-w-sm rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
               placeholder="OpenMail"
               autocomplete="off">
        @error('appName')
            <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
        @enderror
    </div>

    {{-- Navigation --}}
    <div class="mt-8 flex justify-end">
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
            <span wire:loading.remove>Get Started</span>
            <span wire:loading>Loading…</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
