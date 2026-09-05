<div>
    <h3 class="text-lg font-medium text-gray-900">Application Settings</h3>
    <p class="mt-2 text-sm text-gray-600">Configure your OpenMail instance.</p>

    <div class="mt-4 space-y-4">
        <div>
            <label for="app_name" class="block text-sm font-medium text-gray-700">Application Name</label>
            <input type="text" id="app_name" name="app_name" wire:model="appName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="OpenMail" required>
            @error('appName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="app_org" class="block text-sm font-medium text-gray-700">Organization Name</label>
            <input type="text" id="app_org" name="app_org" wire:model="appOrg" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required placeholder="Acme Corporation">
            @error('appOrg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="app_domain" class="block text-sm font-medium text-gray-700">Domain</label>
            <input type="text" id="app_domain" name="app_domain" wire:model="appDomain" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required placeholder="example.com">
            <p class="mt-1 text-xs text-gray-500">Your organization's domain (no protocol prefix, e.g., example.com)</p>
            @error('appDomain') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="app_timezone" class="block text-sm font-medium text-gray-700">Timezone</label>
            <select id="app_timezone" name="app_timezone" wire:model="appTimezone" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
                <option value="UTC">UTC</option>
                <option value="America/New_York">America/New_York (Eastern)</option>
                <option value="America/Chicago">America/Chicago (Central)</option>
                <option value="America/Denver">America/Denver (Mountain)</option>
                <option value="America/Los_Angeles">America/Los_Angeles (Pacific)</option>
                <option value="Europe/London">Europe/London (GMT)</option>
                <option value="Europe/Paris">Europe/Paris (CET)</option>
                <option value="Europe/Berlin">Europe/Berlin (CET)</option>
                <option value="Asia/Tokyo">Asia/Tokyo (JST)</option>
                <option value="Asia/Shanghai">Asia/Shanghai (CST)</option>
                <option value="Australia/Sydney">Australia/Sydney (AEST)</option>
            </select>
            @error('appTimezone') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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