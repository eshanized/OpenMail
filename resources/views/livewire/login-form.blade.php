<div>
    {{-- Error state — clean, not a colorful block --}}
    @if ($error)
        <div class="mb-5 p-3 bg-error-subtle border border-error/15 rounded text-sm text-error flex items-start gap-2" role="alert">
            <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    <form wire:submit.prevent="login" class="space-y-4">
        {{-- Email --}}
        <div>
            <label for="email" class="block text-sm font-medium text-ink mb-1">Email address</label>
            <input
                id="email"
                name="email"
                type="email"
                autocomplete="email"
                wire:model="email"
                required
                autofocus
                class="w-full px-3 py-2 bg-surface-raised border border-border-strong rounded text-sm text-ink placeholder-ink-tertiary
                       focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary
                       transition-colors"
                placeholder="you@company.com"
            >
        </div>

        {{-- Password --}}
        <div>
            <label for="password" class="block text-sm font-medium text-ink mb-1">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                wire:model="password"
                required
                class="w-full px-3 py-2 bg-surface-raised border border-border-strong rounded text-sm text-ink placeholder-ink-tertiary
                       focus:outline-none focus:ring-2 focus:ring-primary/20 focus:border-primary
                       transition-colors"
                placeholder="Enter your password"
            >
        </div>

        {{-- Submit --}}
        <button
            type="submit"
            class="w-full flex items-center justify-center gap-2 py-2 px-4 bg-primary hover:bg-primary-hover text-white text-sm font-medium rounded
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary/40
                   transition-colors mt-1"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-70"
        >
            <span wire:loading.remove wire:target="login">Sign in</span>
            <span wire:loading wire:target="login" class="flex items-center gap-2">
                <svg class="spin-animation w-3.5 h-3.5" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Signing in…
            </span>
        </button>
    </form>

    <div class="mt-4 text-center">
        <a href="/install" class="text-xs text-ink-tertiary hover:text-ink-secondary transition-colors">
            Run Setup Wizard
        </a>
    </div>
</div>
