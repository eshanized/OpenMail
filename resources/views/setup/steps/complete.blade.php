<div class="text-center">
    {{-- Success Mark --}}
    <div class="w-10 h-10 rounded-full bg-surface-sunken border border-border text-success flex items-center justify-center mx-auto mb-4">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h2 class="text-xl font-bold text-ink tracking-tight">
        Configuration Complete
    </h2>
    <p class="mt-1 text-sm text-ink-secondary max-w-sm mx-auto">
        Your OpenMail environment has been configured and locked.
    </p>

    {{-- Setup Confirmation Panel --}}
    <div class="mt-6 p-4 setup-panel max-w-sm mx-auto text-left space-y-2 border border-border rounded">
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Version</span>
            <span class="font-mono text-ink">v1.0.0</span>
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Status</span>
            <span class="font-medium text-success">Locked &amp; Active</span>
        </div>
        <div class="flex items-center justify-between text-xs">
            <span class="text-ink-tertiary">Sessions</span>
            <span class="text-ink">Hardened</span>
        </div>
    </div>

    <div class="mt-6 flex justify-center">
        <a href="{{ route('login') }}"
           class="setup-btn-primary">
            <span>Continue to Sign In &rarr;</span>
        </a>
    </div>
</div>
