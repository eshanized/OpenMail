<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-ink">Administrator Account</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Create the first OpenMail administrator account. Use this account to log in to OpenMail.
            This is an application identity, not a mailbox credential.
        </p>
    </div>

    <div class="space-y-5 max-w-md">
        {{-- Name --}}
        <div>
            <label for="admin_name" class="block text-sm font-medium text-ink-secondary">
                Full Name <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="admin_name"
                   wire:model="adminName"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="Jane Smith"
                   autocomplete="name"
                   required>
            @error('adminName')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="admin_email" class="block text-sm font-medium text-ink-secondary">
                Email Address <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="email"
                   id="admin_email"
                   wire:model="adminEmail"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="admin@example.com"
                   autocomplete="email"
                   required>
            <p class="mt-1 text-xs text-ink-tertiary">You'll use this address to log in.</p>
            @error('adminEmail')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="admin_password" class="block text-sm font-medium text-ink-secondary">
                Password <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="password"
                   id="admin_password"
                   wire:model="adminPassword"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   autocomplete="new-password"
                   required>
            @error('adminPassword')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror

            {{-- Strength meter --}}
            <div class="mt-2" x-data="{
                    strength: 0,
                    label: '',
                    compute(val) {
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
                <div class="h-1.5 bg-border rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300 rounded-full"
                         :class="{
                             'w-0': strength === 0,
                             'w-1/4 bg-danger': strength === 1,
                             'w-2/4 bg-warning': strength === 2,
                             'w-3/4 bg-primary': strength === 3,
                             'w-full bg-success': strength === 4,
                         }"
                         role="progressbar"
                         :aria-valuenow="strength * 25"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         :aria-label="'Password strength: ' + label">
                    </div>
                </div>
                <p class="text-xs mt-1 text-ink-tertiary" x-text="label" aria-live="polite"></p>
            </div>
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="admin_password_confirmation" class="block text-sm font-medium text-ink-secondary">
                Confirm Password <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="password"
                   id="admin_password_confirmation"
                   wire:model="adminPasswordConfirmation"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   autocomplete="new-password"
                   required>
            @error('adminPasswordConfirmation')
                <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password requirements --}}
        <div class="p-3 bg-primary-subtle/50 border border-primary/20 rounded-lg">
            <p class="text-sm font-medium text-primary">Password requirements:</p>
            <ul class="mt-1 text-xs text-primary list-disc list-inside space-y-0.5">
                <li>At least 8 characters</li>
                <li>At least one uppercase letter (A–Z)</li>
                <li>At least one lowercase letter (a–z)</li>
                <li>At least one digit (0–9)</li>
            </ul>
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
            Continue →
        </button>
    </div>
</div>
