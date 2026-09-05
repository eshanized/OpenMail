<div>
    <h3 class="text-lg font-medium text-gray-900">Database Configuration</h3>
    <p class="mt-2 text-sm text-gray-600">Configure your database connection.</p>

    <div class="mt-4 space-y-4">
        <div>
            <label for="db_host" class="block text-sm font-medium text-gray-700">Host</label>
            <input type="text" id="db_host" name="db_host" wire:model="dbHost" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="127.0.0.1">
            @error('dbHost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_port" class="block text-sm font-medium text-gray-700">Port</label>
            <input type="number" id="db_port" name="db_port" wire:model="dbPort" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" value="3306">
            @error('dbPort') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_database" class="block text-sm font-medium text-gray-700">Database Name</label>
            <input type="text" id="db_database" name="db_database" wire:model="dbDatabase" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border" required>
            @error('dbDatabase') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text" id="db_username" name="db_username" wire:model="dbUsername" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
            @error('dbUsername') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="db_password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password" id="db_password" name="db_password" wire:model="dbPassword" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
            @error('dbPassword') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <button type="button" wire:click="testDatabaseConnection" wire:loading.attr="disabled" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <span wire:loading.remove>Test Connection</span>
                <span wire:loading>Testing...</span>
            </button>

            @if ($dbTestResult)
                <span class="ml-3 text-sm" :class="$dbTestResult['success'] ? 'text-green-600' : 'text-red-600'">
                    {{ $dbTestResult['success'] ? '✓ Connected successfully! Migrations ran.' : '✗ ' . $dbTestResult['error'] }}
                </span>

                @if (!$dbTestResult['success'] && isset($dbTestResult['technical']))
                    <div class="mt-2">
                        <button type="button" x-data="{ open: false }" @click="open = !open" class="text-xs text-blue-600 hover:underline">Show technical details</button>
                        <div x-show="open" x-transition class="mt-2 p-3 bg-gray-100 rounded text-xs font-mono text-gray-700 overflow-auto max-h-40">
                            <pre>{{ json_encode($dbTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Previous
        </button>
        <button type="button" wire:click="nextStep" :disabled="!$dbTestResult || !$dbTestResult['success']" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed">
            Next
        </button>
    </div>
</div>