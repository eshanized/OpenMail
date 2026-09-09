<div>
    <h3 class="text-lg font-medium text-ink">Security Defaults</h3>
    <p class="mt-2 text-sm text-ink-secondary">Review and confirm the security configuration for your OpenMail instance.</p>

    <div class="mt-4 space-y-6">
        <!-- HTTPS Enforcement -->
        <div class="p-4 bg-surface-sunken border border-border rounded-lg">
            <h4 class="text-sm font-medium text-ink">HTTPS Enforcement</h4>
            <p class="mt-1 text-sm text-ink-secondary">
                OpenMail will enforce HTTPS for all connections. This ensures all data is encrypted in transit.
            </p>
            <div class="mt-2 flex items-center">
                <input type="checkbox" id="https_enabled" wire:model="httpsEnabled" class="h-4 w-4 text-primary border-border rounded focus:ring-primary" checked disabled>
                <label for="https_enabled" class="ml-2 text-sm text-ink-secondary">HTTPS enforcement enabled (recommended)</label>
            </div>
            <p class="mt-2 text-xs text-ink-tertiary">Current request: {{ request()->isSecure() ? 'Secure (HTTPS)' : 'Insecure (HTTP)' }}</p>
        </div>

        <!-- Cookie Settings -->
        <div class="p-4 bg-surface-sunken border border-border rounded-lg">
            <h4 class="text-sm font-medium text-ink">Cookie Settings</h4>
            <p class="mt-1 text-sm text-ink-secondary">
                Secure cookie configuration to protect session data.
            </p>
            <div class="mt-2 space-y-2">
                <label class="flex items-center">
                    <input type="checkbox" class="h-4 w-4 text-primary border-border rounded focus:ring-primary" checked disabled>
                    <span class="ml-2 text-sm text-ink-secondary">HttpOnly (prevents XSS access to cookies)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" class="h-4 w-4 text-primary border-border rounded focus:ring-primary" checked disabled>
                    <span class="ml-2 text-sm text-ink-secondary">SameSite=Lax (CSRF protection)</span>
                </label>
                <label class="flex items-center">
                    <input type="checkbox" id="secure_cookies" wire:model="secureCookies" class="h-4 w-4 text-primary border-border rounded focus:ring-primary" :checked="request()->isSecure()" disabled>
                    <span class="ml-2 text-sm text-ink-secondary">Secure flag (cookies only sent over HTTPS)</span>
                </label>
            </div>
            <p class="mt-2 text-xs text-ink-tertiary">Secure flag: {{ request()->isSecure() ? 'Enabled (HTTPS detected)' : 'Disabled (HTTP detected - enable after HTTPS setup)' }}</p>
        </div>

        <!-- Session Lifetime -->
        <div class="p-4 bg-surface-sunken border border-border rounded-lg">
            <h4 class="text-sm font-medium text-ink">Session Lifetime</h4>
            <p class="mt-1 text-sm text-ink-secondary">
                Sessions expire after 24 hours of inactivity. This is standard for webmail applications.
            </p>
            <div class="mt-2">
                <label for="session_lifetime" class="block text-sm font-medium text-ink-secondary">Session Lifetime (minutes)</label>
                <input type="number" id="session_lifetime" value="1440" class="mt-1 block w-full max-w-xs rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border" disabled>
            </div>
        </div>

        <!-- Login Throttling -->
        <div class="p-4 bg-surface-sunken border border-border rounded-lg">
            <h4 class="text-sm font-medium text-ink">Login Throttling (Progressive Delays)</h4>
            <p class="mt-1 text-sm text-ink-secondary">
                Protection against brute-force attacks with increasing delays after failed attempts.
            </p>
            <div class="mt-2 overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-surface-sunken">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-ink-secondary">Failed Attempts</th>
                            <th class="px-3 py-2 text-left font-medium text-ink-secondary">Delay</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr><td class="px-3 py-2 text-ink-secondary">1-2</td><td class="px-3 py-2 text-ink-secondary">No delay</td></tr>
                        <tr class="bg-surface-sunken"><td class="px-3 py-2 text-ink-secondary">3</td><td class="px-3 py-2 text-warning">5 seconds</td></tr>
                        <tr><td class="px-3 py-2 text-ink-secondary">4</td><td class="px-3 py-2 text-ink-secondary">5 seconds</td></tr>
                        <tr class="bg-surface-sunken"><td class="px-3 py-2 text-ink-secondary">5</td><td class="px-3 py-2 text-warning">30 seconds</td></tr>
                        <tr><td class="px-3 py-2 text-ink-secondary">6</td><td class="px-3 py-2 text-ink-secondary">30 seconds</td></tr>
                        <tr class="bg-surface-sunken"><td class="px-3 py-2 text-ink-secondary">7</td><td class="px-3 py-2 text-danger">5 minutes</td></tr>
                        <tr><td class="px-3 py-2 text-ink-secondary">8-9</td><td class="px-3 py-2 text-danger">5 minutes</td></tr>
                        <tr class="bg-surface-sunken"><td class="px-3 py-2 text-ink-secondary">10+</td><td class="px-3 py-2 text-danger">15 minutes (lockout)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-border shadow-sm text-sm font-medium rounded-md text-ink-secondary bg-white hover:bg-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            Previous
        </button>
        <button type="button" wire:click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            Next
        </button>
    </div>
</div>
