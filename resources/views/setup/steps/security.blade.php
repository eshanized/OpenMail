<div>
    <h3 class="text-lg font-medium text-gray-900">Security Defaults</h3>
    <p class="mt-2 text-sm text-gray-600">Review and confirm the security configuration for your OpenMail instance.</p>

    <div class="mt-4 space-y-6">
        <!-- HTTPS Enforcement -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">HTTPS Enforcement</h4>
            <p class="mt-1 text-sm text-gray-600">
                OpenMail will enforce HTTPS for all connections. This ensures all data is encrypted in transit.
            </p>
            <div class="mt-2 flex items-center">
                <input type="checkbox" id="https_enabled" wire:model="httpsEnabled" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" checked disabled>
                <label for="https_enabled" class="ml-2 text-sm text-gray-700">HTTPS enforcement enabled (recommended)</label>
            </div>
            <p class="mt-2 text-xs text-gray-500">Current request: {{ request()->isSecure() ? 'Secure (HTTPS)' : 'Insecure (HTTP)' }}</p>
        </div>

        <!-- Cookie Settings -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Cookie Settings</h4>
            <p class="mt-1 text-sm text-gray-600">
                Secure cookie configuration to protect session data.
            </p>
            <div class="mt-2 space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" checked disabled>
                    <span class="ml-2 text-sm text-gray-700">HttpOnly (prevents XSS access to cookies)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" checked disabled>
                    <span class="ml-2 text-sm text-gray-700">SameSite=Lax (CSRF protection)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="secure_cookies" wire:model="secureCookies" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" :checked="request()->isSecure()" disabled>
                    <span class="ml-2 text-sm text-gray-700">Secure flag (cookies only sent over HTTPS)</span>
                </label>
            </div>
            <p class="mt-2 text-xs text-gray-500">Secure flag: {{ request()->isSecure() ? 'Enabled (HTTPS detected)' : 'Disabled (HTTP detected - enable after HTTPS setup)' }}</p>
        </div>

        <!-- Session Lifetime -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Session Lifetime</h4>
            <p class="mt-1 text-sm text-gray-600">
                Sessions expire after 24 hours of inactivity. This is standard for webmail applications.
            </p>
            <div class="mt-2">
                <label for="session_lifetime" class="block text-sm font-medium text-gray-700">Session Lifetime (minutes)</label>
                <input type="number" id="session_lifetime" value="1440" class="mt-1 block w-full max-w-xs rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" disabled>
            </div>
        </div>

        <!-- Login Throttling -->
        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Login Throttling (Progressive Delays)</h4>
            <p class="mt-1 text-sm text-gray-600">
                Protection against brute-force attacks with increasing delays after failed attempts.
            </p>
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Failed Attempts</th>
                            <th class="px-3 py-2 text-left font-medium text-gray-700">Delay</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr><td class="px-3 py-2 text-gray-700">1-2</td><td class="px-3 py-2 text-gray-700">No delay</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2 text-gray-700">3</td><td class="px-3 py-2 text-yellow-600">5 seconds</td></tr>
                        <tr><td class="px-3 py-2 text-gray-700">4</td><td class="px-3 py-2 text-gray-700">5 seconds</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2 text-gray-700">5</td><td class="px-3 py-2 text-orange-600">30 seconds</td></tr>
                        <tr><td class="px-3 py-2 text-gray-700">6</td><td class="px-3 py-2 text-gray-700">30 seconds</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2 text-gray-700">7</td><td class="px-3 py-2 text-red-600">5 minutes</td></tr>
                        <tr><td class="px-3 py-2 text-gray-700">8-9</td><td class="px-3 py-2 text-red-600">5 minutes</td></tr>
                        <tr class="bg-gray-50"><td class="px-3 py-2 text-gray-700">10+</td><td class="px-3 py-2 text-red-700">15 minutes (lockout)</td></tr>
                    </tbody>
                </table>
            </div>
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