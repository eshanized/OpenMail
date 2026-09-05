<div>
    <h3 class="text-lg font-medium text-gray-900">Administrator Account</h3>
    <p class="mt-2 text-sm text-gray-600">Create your administrator account. This account will be used to log in to OpenMail.</p>

    <div class="mt-4 space-y-4">
        <div>
            <label for="admin_email" class="block text-sm font-medium text-gray-700">Email Address</label>
            <input type="email" id="admin_email" name="admin_email" wire:model="adminEmail" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
            <p class="mt-1 text-xs text-gray-500">This will be your login email</p>
            @error('adminEmail') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="admin_password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="admin_password" name="admin_password" wire:model="adminPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required autocomplete="new-password">
            @error('adminPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

            <div class="mt-2" x-data="{ strength: 0 }">
                <div class="h-1.5 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-full transition-all duration-300" :class="
                        strength === 0 ? 'bg-gray-200 w-0' :
                        strength === 1 ? 'bg-red-500 w-1/4' :
                        strength === 2 ? 'bg-yellow-500 w-2/4' :
                        strength === 3 ? 'bg-blue-500 w-3/4' :
                        'bg-green-500 w-full'
                    " :style="strength === 0 ? 'width: 0%' : (strength === 1 ? 'width: 25%' : (strength === 2 ? 'width: 50%' : (strength === 3 ? 'width: 75%' : 'width: 100%')))"></div>
                </div>
                <p class="text-xs mt-1" x-text="strength === 0 ? '' : (strength === 1 ? 'Weak' : (strength === 2 ? 'Fair' : (strength === 3 ? 'Good' : 'Strong')))"></p>
            </div>

            <script>
                document.addEventListener('livewire:load', () => {
                    const passwordInput = document.getElementById('admin_password');
                    if (passwordInput) {
                        passwordInput.addEventListener('input', (e) => {
                            const val = e.target.value;
                            let strength = 0;
                            if (val.length >= 8) strength++;
                            if (/[A-Z]/.test(val) && /[a-z]/.test(val)) strength++;
                            if (/[0-9]/.test(val)) strength++;
                            if (/[^A-Za-z0-9]/.test(val)) strength++;
                            if (strength > 4) strength = 4;

                            const strengthDiv = document.querySelector('[x-data] div.h-1.5 > div');
                            const strengthText = document.querySelector('[x-data] p');
                            if (strengthDiv) {
                                strengthDiv.style.width = strength === 0 ? '0%' : (strength === 1 ? '25%' : (strength === 2 ? '50%' : (strength === 3 ? '75%' : '100%')));
                                strengthDiv.className = 'h-full transition-all duration-300 ' +
                                    (strength === 1 ? 'bg-red-500' :
                                    strength === 2 ? 'bg-yellow-500' :
                                    strength === 3 ? 'bg-blue-500' : 'bg-green-500');
                            }
                            if (strengthText) {
                                strengthText.textContent = strength === 0 ? '' : (strength === 1 ? 'Weak' : (strength === 2 ? 'Fair' : (strength === 3 ? 'Good' : 'Strong')));
                            }
                        });
                    }
                });
            </script>
        </div>

        <div>
            <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input type="password" id="admin_password_confirmation" name="admin_password_confirmation" wire:model="adminPasswordConfirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required autocomplete="new-password">
            @error('adminPasswordConfirmation') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="p-3 bg-blue-50 border border-blue-200 rounded-md">
            <p class="text-sm text-blue-800"><strong>Password Requirements:</strong></p>
            <ul class="mt-1 text-sm text-blue-700 list-disc list-inside space-y-1">
                <li>At least 8 characters</li>
                <li>At least one uppercase letter (A-Z)</li>
                <li>At least one lowercase letter (a-z)</li>
                <li>At least one number (0-9)</li>
            </ul>
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Previous
        </button>
        <button type="button" wire:click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Next
        </button>
    </div>
</div>