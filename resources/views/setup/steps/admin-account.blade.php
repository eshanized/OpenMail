<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Administrator Account</h2>
        <p class="mt-1 text-sm text-gray-600">
            Create the first OpenMail administrator account. Use this account to log in to OpenMail.
            This is an application identity, not a mailbox credential.
        </p>
    </div>

    <div class="space-y-5 max-w-md">
        {{-- Name --}}
        <div>
            <label for="admin_name" class="block text-sm font-medium text-gray-700">
                Full Name <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="admin_name"
                   wire:model="adminName"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="Jane Smith"
                   autocomplete="name"
                   required>
            @error('adminName')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div>
            <label for="admin_email" class="block text-sm font-medium text-gray-700">
                Email Address <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="email"
                   id="admin_email"
                   wire:model="adminEmail"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="admin@example.com"
                   autocomplete="email"
                   required>
            <p class="mt-1 text-xs text-gray-500">You'll use this address to log in.</p>
            @error('adminEmail')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="admin_password" class="block text-sm font-medium text-gray-700">
                Password <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="password"
                   id="admin_password"
                   wire:model="adminPassword"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   autocomplete="new-password"
                   required>
            @error('adminPassword')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
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
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300 rounded-full"
                         :class="{
                             'w-0': strength === 0,
                             'w-1/4 bg-red-500': strength === 1,
                             'w-2/4 bg-yellow-500': strength === 2,
                             'w-3/4 bg-blue-500': strength === 3,
                             'w-full bg-green-500': strength === 4,
                         }"
                         role="progressbar"
                         :aria-valuenow="strength * 25"
                         aria-valuemin="0"
                         aria-valuemax="100"
                         :aria-label="'Password strength: ' + label">
                    </div>
                </div>
                <p class="text-xs mt-1 text-gray-500" x-text="label" aria-live="polite"></p>
            </div>
        </div>

        {{-- Confirm password --}}
        <div>
            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700">
                Confirm Password <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="password"
                   id="admin_password_confirmation"
                   wire:model="adminPasswordConfirmation"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   autocomplete="new-password"
                   required>
            @error('adminPasswordConfirmation')
                <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password requirements --}}
        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
            <p class="text-sm font-medium text-blue-900">Password requirements:</p>
            <ul class="mt-1 text-xs text-blue-800 list-disc list-inside space-y-0.5">
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