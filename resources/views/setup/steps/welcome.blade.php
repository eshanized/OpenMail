<div class="p-8 sm:p-12">
    {{-- Hero Section --}}
    <div class="text-center max-w-xl mx-auto">
        <div class="inline-flex p-3.5 rounded-2xl bg-gradient-to-br from-indigo-500/10 via-blue-500/10 to-purple-500/10 border border-indigo-500/20 mb-5 shadow-inner">
            <div class="w-14 h-14 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-blue-500 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <svg class="w-7 h-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
        </div>

        <h1 class="text-3xl sm:text-4xl font-extrabold text-ink tracking-tight">
            Welcome to <span class="bg-gradient-to-r from-indigo-500 via-blue-500 to-indigo-600 bg-clip-text text-transparent">OpenMail</span>
        </h1>
        <p class="mt-3 text-base text-ink-secondary leading-relaxed">
            A secure, modern, self-hosted webmail platform. This wizard will guide you through setting up your environment in just a few minutes.
        </p>
    </div>

    {{-- Installation Mode Notice --}}
    <div class="mt-8 max-w-2xl mx-auto">
        @if ($installMode === 'continue')
            <div class="p-4 setup-callout-warning flex items-start gap-3.5 shadow-sm" role="status">
                <div class="w-8 h-8 rounded-lg bg-amber-500/15 flex items-center justify-center flex-shrink-0 text-amber-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-amber-600 dark:text-amber-400">Existing configuration detected</p>
                    <p class="mt-0.5 text-xs text-ink-secondary">A partial installation was detected. You can resume seamlessly or start over anytime.</p>
                </div>
            </div>
        @else
            <div class="p-4 setup-callout-success flex items-center gap-3.5 shadow-sm" role="status">
                <div class="w-8 h-8 rounded-lg bg-emerald-500/15 flex items-center justify-center flex-shrink-0 text-emerald-500">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-emerald-600 dark:text-emerald-400">Ready for Fresh Installation</p>
                    <p class="text-xs text-ink-secondary">System is ready. OpenMail will connect to your existing database and mail servers.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- What You'll Need Cards --}}
    <div class="mt-10">
        <h3 class="text-xs font-bold uppercase tracking-wider text-ink-tertiary text-center mb-4">
            Prerequisites &amp; Credentials
        </h3>
        <div class="grid sm:grid-cols-3 gap-4">
            <div class="setup-panel group hover:border-indigo-500/30 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-500 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582 4 8 4m0 0c4.418 0 8-1.79 8-4"/>
                    </svg>
                </div>
                <h4 class="font-bold text-ink text-sm">MySQL Database</h4>
                <p class="text-xs text-ink-secondary mt-1 leading-relaxed">
                    Host, port, database name, username and password.
                </p>
            </div>

            <div class="setup-panel group hover:border-indigo-500/30 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-500 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h4 class="font-bold text-ink text-sm">Mail Server</h4>
                <p class="text-xs text-ink-secondary mt-1 leading-relaxed">
                    IMAP (incoming) and SMTP (outgoing) endpoints and ports.
                </p>
            </div>

            <div class="setup-panel group hover:border-indigo-500/30 transition-all duration-200">
                <div class="w-10 h-10 rounded-xl bg-purple-500/10 border border-purple-500/20 text-purple-500 flex items-center justify-center mb-3 group-hover:scale-105 transition-transform">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <h4 class="font-bold text-ink text-sm">Admin Account</h4>
                <p class="text-xs text-ink-secondary mt-1 leading-relaxed">
                    Organization name, administrator email and master password.
                </p>
            </div>
        </div>
    </div>

    {{-- App Name Configuration --}}
    <div class="mt-10 pt-8 border-t border-border/70 max-w-lg">
        <label for="app_name_welcome" class="block text-sm font-semibold text-ink">
            Application Name
            <span class="text-ink-tertiary font-normal text-xs ml-1">(you can customize this later)</span>
        </label>
        <div class="mt-2">
            <input type="text"
                   id="app_name_welcome"
                   wire:model="appName"
                   class="setup-input font-medium"
                   placeholder="OpenMail"
                   autocomplete="off">
        </div>
        @error('appName')
            <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p>
        @enderror
    </div>

    {{-- Navigation --}}
    <div class="mt-10 pt-6 border-t border-border/70 flex justify-end">
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="setup-btn-primary">
            <span wire:loading.remove>Get Started</span>
            <span wire:loading>Starting…</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
