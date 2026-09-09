<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Administrator Account</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Create the primary administrator account for OpenMail. This identity manages your application settings and user mailboxes.
        </p>
    </div>

    <div class="space-y-6 max-w-lg">
        {{-- Full Name --}}
        <div>
            <label for="admin_name" class="block text-sm font-semibold text-ink">
                Full Name <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="admin_name"
                       wire:model="adminName"
                       class="setup-input font-medium"
                       placeholder="Jane Smith"
                       autocomplete="name"
                       required>
            </div>
            @error('adminName')
                <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email Address --}}
        <div>
            <label for="admin_email" class="block text-sm font-semibold text-ink">
                Admin Email Address <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="email"
                       id="admin_email"
                       wire:model="adminEmail"
                       class="setup-input font-medium"
                       placeholder="admin@example.com"
                       autocomplete="email"
                       required>
            </div>
            <p class="mt-1.5 text-xs text-ink-tertiary">This address will be your login identifier for the webmail console.</p>
            @error('adminEmail')
                <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="admin_password" class="block text-sm font-semibold text-ink">
                Password <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="password"
                       id="admin_password"
                       wire:model="adminPassword"
                       class="setup-input font-mono"
                       autocomplete="new-password"
                       placeholder="••••••••"
                       required>
            </div>
            @error('adminPassword')
                <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p>
            @enderror

            {{-- Strength Meter --}}
            <div class="mt-3" x-data="{
                    strength: 0,
                    label: '',
                    compute(val) {
                        if (!val) { this.strength = 0; this.label = ''; return; }
                        let s = 0;
                        if (val.length >= 8) s++;
                        if (/[A-Z]/.test(val) && /[a-z]/.test(val)) s++;
                        if (/[0-9]/.test(val)) s++;
                        if (/[^A-Za-z0-9]/.test(val)) s++;
                        this.strength = Math.min(s, 4);
                        this.label = ['', 'Weak', 'Fair', 'Good', 'Strong'][this.strength] || '';
                    }
                }"
                x-init="$watch('$wire.adminPassword', v => compute(v))">
                <div class="h-1.5 bg-surface-sunken dark:bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300 rounded-full"
                         :class="{
                             'w-0': strength === 0,
                             'w-1/4 bg-rose-500': strength === 1,
                             'w-2/4 bg-amber-500': strength === 2,
                             'w-3/4 bg-blue-500': strength === 3,
                             'w-full bg-emerald-500': strength === 4,
                         }"
                         role="progressbar"
                         :aria-valuenow="strength * 25"
                         aria-valuemin="0"
                         aria-valuemax="100">
                    </div>
                </div>
                <div class="flex items-center justify-between mt-1 text-[11px]">
                    <span class="text-ink-tertiary">Password strength:</span>
                    <span class="font-semibold"
                          :class="{
                              'text-rose-500': strength === 1,
                              'text-amber-500': strength === 2,
                              'text-blue-500': strength === 3,
                              'text-emerald-500': strength === 4,
                          }"
                          x-text="label" aria-live="polite"></span>
                </div>
            </div>
        </div>

        {{-- Confirm Password --}}
        <div>
            <label for="admin_password_confirmation" class="block text-sm font-semibold text-ink">
                Confirm Password <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="password"
                       id="admin_password_confirmation"
                       wire:model="adminPasswordConfirmation"
                       class="setup-input font-mono"
                       autocomplete="new-password"
                       placeholder="••••••••"
                       required>
            </div>
            @error('adminPasswordConfirmation')
                <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Requirements Callout --}}
        <div class="setup-panel border-indigo-500/20 bg-indigo-500/[0.02]">
            <p class="text-xs font-bold text-indigo-600 dark:text-indigo-400">Security requirements:</p>
            <ul class="mt-2 text-xs text-ink-secondary space-y-1.5">
                <li class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    At least 8 characters
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    At least one uppercase (A&ndash;Z) and one lowercase letter (a&ndash;z)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    At least one digit (0&ndash;9)
                </li>
            </ul>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-10 pt-6 border-t border-border/70 flex justify-between items-center">
        <button type="button"
                wire:click="previousStep"
                class="setup-btn-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>

        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
