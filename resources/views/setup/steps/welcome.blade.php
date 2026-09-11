<div class="space-y-8">
    {{-- Hero Section --}}
    <div>
        <h1 class="text-2xl font-bold text-ink tracking-tight">
            Welcome to OpenMail
        </h1>
        <p class="mt-1.5 text-sm text-ink-secondary leading-relaxed">
            A self-hosted webmail application for organizations. This wizard connects OpenMail to your existing mail infrastructure and database in a few minutes.
        </p>
    </div>

    {{-- Installation Mode Notice --}}
    <div>
        @if ($installMode === 'continue')
            <div class="p-3.5 setup-callout-warning flex items-start gap-3 rounded" role="status">
                <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <p class="text-xs font-semibold">Existing configuration detected</p>
                    <p class="mt-0.5 text-xs text-ink-secondary">A partial installation was detected. You can resume or start over.</p>
                </div>
            </div>
        @else
            <div class="p-3.5 bg-surface-sunken border border-border rounded flex items-center gap-3" role="status">
                <svg class="w-4 h-4 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div>
                    <p class="text-xs font-semibold text-ink">Ready to install</p>
                    <p class="text-xs text-ink-secondary">OpenMail will connect to your existing database and mail servers.</p>
                </div>
            </div>
        @endif
    </div>

    {{-- What You'll Need --}}
    <div>
        <h3 class="text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-3">
            What you will need
        </h3>
        <div class="grid sm:grid-cols-3 gap-3">
            <div class="p-3.5 bg-surface-sunken border border-border rounded">
                <h4 class="font-semibold text-ink text-xs mb-1">1. MySQL Database</h4>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Host, port, database name, username and password.
                </p>
            </div>

            <div class="p-3.5 bg-surface-sunken border border-border rounded">
                <h4 class="font-semibold text-ink text-xs mb-1">2. Mail Server</h4>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    IMAP (incoming) and SMTP (outgoing) hosts and ports.
                </p>
            </div>

            <div class="p-3.5 bg-surface-sunken border border-border rounded">
                <h4 class="font-semibold text-ink text-xs mb-1">3. Admin Account</h4>
                <p class="text-xs text-ink-secondary leading-relaxed">
                    Organization name and administrator email credentials.
                </p>
            </div>
        </div>
    </div>

    {{-- App Name Configuration --}}
    <div class="pt-6 border-t border-border max-w-sm">
        <label for="app_name_welcome" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1">
            Application Name
        </label>
        <input type="text"
               id="app_name_welcome"
               wire:model="appName"
               class="setup-input text-xs"
               placeholder="OpenMail"
               autocomplete="off">
        @error('appName')
            <p class="mt-1 text-xs text-error" role="alert">{{ $message }}</p>
        @enderror
    </div>

    {{-- Navigation --}}
    <div class="pt-5 border-t border-border flex justify-end">
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="setup-btn-primary">
            <span wire:loading.remove>Get Started &rarr;</span>
            <span wire:loading>Starting…</span>
        </button>
    </div>
</div>
