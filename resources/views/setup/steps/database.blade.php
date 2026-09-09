<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Database Configuration</h2>
        <p class="mt-1 text-sm text-gray-600">
            Configure your MySQL/MariaDB connection. OpenMail uses a separate database to store
            application state \u2014 your email remains on your mail server.
        </p>
    </div>

    {{-- Error banners --}}
    @error('dbConnection')
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800" role="alert">{{ $message }}</p>
        </div>
    @enderror
    @error('dbMigrations')
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800" role="alert">{{ $message }}</p>
        </div>
    @enderror

    <div class="grid sm:grid-cols-2 gap-4">
        {{-- Host --}}
        <div class="sm:col-span-2">
            <label for="db_host" class="block text-sm font-medium text-gray-700">
                Database Host
            </label>
            <input type="text"
                   id="db_host"
                   wire:model="dbHost"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="127.0.0.1"
                   autocomplete="off">
            <p class="mt-1 text-xs text-gray-500">Use <code>127.0.0.1</code> for local or <code>localhost</code> (they differ!). Check your hosting panel for the correct value.</p>
            @error('dbHost') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Port --}}
        <div>
            <label for="db_port" class="block text-sm font-medium text-gray-700">Port</label>
            <input type="number"
                   id="db_port"
                   wire:model="dbPort"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="3306"
                   min="1" max="65535">
            @error('dbPort') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Database name --}}
        <div>
            <label for="db_database" class="block text-sm font-medium text-gray-700">
                Database Name <span class="text-red-500 ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="db_database"
                   wire:model="dbDatabase"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="openmail"
                   required
                   autocomplete="off">
            @error('dbDatabase') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="db_username" class="block text-sm font-medium text-gray-700">Username</label>
            <input type="text"
                   id="db_username"
                   wire:model="dbUsername"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   placeholder="root"
                   autocomplete="off">
            @error('dbUsername') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="db_password" class="block text-sm font-medium text-gray-700">Password</label>
            <input type="password"
                   id="db_password"
                   wire:model="dbPassword"
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border"
                   autocomplete="new-password">
            @error('dbPassword') <p class="mt-1 text-sm text-red-600" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Test Connection --}}
    <div class="mt-6 space-y-3">
        <div class="flex items-center gap-3 flex-wrap">
            <button type="button"
                    wire:click="testDatabaseConnection"
                    wire:loading.attr="disabled"
                    wire:target="testDatabaseConnection"
                    class="px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                <span wire:loading.remove wire:target="testDatabaseConnection">Test Connection</span>
                <span wire:loading wire:target="testDatabaseConnection">Testing…</span>
            </button>

            @if ($dbTestResult)
                <span class="{{ $dbTestResult['success'] ? 'text-green-700' : 'text-red-700' }} text-sm font-medium flex items-center gap-1">
                    @if ($dbTestResult['success'])
                        ✓ Connected
                        @if (isset($dbTestResult['server_version']))
                            <span class="text-gray-500 font-normal">(MySQL {{ $dbTestResult['server_version'] }})</span>
                        @endif
                    @else
                        ✗ Failed
                    @endif
                </span>
            @endif
        </div>

        @if ($dbTestResult && !$dbTestResult['success'])
            <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-sm text-red-800 font-medium">{{ $dbTestResult['error'] }}</p>
                @if (isset($dbTestResult['technical']))
                    <div x-data="{ open: false }" class="mt-2">
                        <button type="button"
                                @click="open = !open"
                                class="text-xs text-red-600 hover:underline focus:outline-none">
                            Show technical details
                        </button>
                        <pre x-show="open"
                             x-transition
                             class="mt-2 p-3 bg-red-100 rounded text-xs font-mono text-red-900 overflow-auto max-h-32">{{ json_encode($dbTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Run Migrations --}}
    @if ($dbTestResult && $dbTestResult['success'])
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <h3 class="text-sm font-medium text-blue-900">Run Database Migrations</h3>
            <p class="mt-1 text-sm text-blue-800">
                Create the OpenMail database tables. This is safe to run more than once \u2014 already-created tables are skipped.
            </p>
            <div class="mt-3 flex items-center gap-3 flex-wrap">
                <button type="button"
                        wire:click="runMigrations"
                        wire:loading.attr="disabled"
                        wire:target="runMigrations"
                        {{ $migrationsRan ? 'disabled' : '' }}
                        class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="runMigrations">
                        {{ $migrationsRan ? '✓ Migrations Complete' : 'Run Migrations' }}
                    </span>
                    <span wire:loading wire:target="runMigrations">Running…</span>
                </button>

                @if ($migrationResult && !$migrationResult['success'])
                    <span class="text-sm text-red-700 font-medium">✗ Migration failed</span>
                @elseif ($migrationsRan)
                    <span class="text-sm text-green-700 font-medium">✓ Tables created successfully</span>
                @endif
            </div>

            @if ($migrationResult && !$migrationResult['success'])
                <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded text-xs font-mono text-red-800 overflow-auto max-h-32">
                    {{ $migrationResult['error'] ?? 'Unknown error' }}
                </div>
            @endif
        </div>
    @endif

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Continue →
        </button>
    </div>
</div>