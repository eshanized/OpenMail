<div class="auth-form">
    @if ($error)
        <div class="auth-error mb-5" role="alert">
            <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
            </svg>
            <span>{{ $error }}</span>
        </div>
    @endif

    <form wire:submit.prevent="login" class="auth-form-fields">
        <div class="auth-field">
            <label for="email">Email address</label>
            <div class="auth-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <rect x="3" y="5" width="18" height="14" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="m4 7 8 6 8-6"/>
                </svg>
                <input id="email" name="email" type="email" autocomplete="username" inputmode="email"
                       wire:model="email" required autofocus spellcheck="false"
                       class="auth-input" placeholder="you@company.com">
            </div>
        </div>

        <div class="auth-field" x-data="{ revealPassword: false }">
            <div class="auth-field-label-row">
                <label for="password">Password</label>
            </div>
            <div class="auth-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                    <rect x="4" y="10" width="16" height="11" rx="2"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10V7a4 4 0 118 0v3"/>
                </svg>
                <input id="password" name="password" x-bind:type="revealPassword ? 'text' : 'password'"
                       autocomplete="current-password" wire:model="password" required
                       class="auth-input auth-input-password" placeholder="Enter your password">
                <button type="button" class="auth-password-toggle" @click="revealPassword = !revealPassword"
                        x-bind:aria-label="revealPassword ? 'Hide password' : 'Show password'"
                        x-bind:title="revealPassword ? 'Hide password' : 'Show password'">
                    <svg x-show="!revealPassword" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.5-6.5 9.75-6.5 9.75 6.5 9.75 6.5-3.5 6.5-9.75 6.5S2.25 12 2.25 12z"/><circle cx="12" cy="12" r="2.75"/>
                    </svg>
                    <svg x-show="revealPassword" x-cloak viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.6 5.6A10.8 10.8 0 0112 5.5c6.25 0 9.75 6.5 9.75 6.5a15.8 15.8 0 01-3.05 3.7M6.2 6.3C3.65 8.05 2.25 12 2.25 12s3.5 6.5 9.75 6.5c1.45 0 2.72-.38 3.8-.97"/><path stroke-linecap="round" stroke-linejoin="round" d="M9.9 9.9a2.97 2.97 0 004.2 4.2"/>
                    </svg>
                </button>
            </div>
        </div>

        <button type="submit" class="auth-submit" wire:loading.attr="disabled" wire:loading.class="is-loading">
            <span wire:loading.remove wire:target="login">Sign in securely</span>
            <span wire:loading wire:target="login" class="auth-submit-loading">
                <svg class="spin-animation w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                Signing you in…
            </span>
            <svg class="auth-submit-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/>
            </svg>
        </button>
    </form>

</div>
