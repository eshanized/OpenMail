<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-ink">Database Configuration</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Configure your MySQL/MariaDB connection. OpenMail uses a separate database to store
            application state \u2014 your email remains on your mail server.
        </p>
    </div>

    {{-- Error banners --}}
    @error('dbConnection')
        <div class="mb-4 p-3 bg-danger-subtle/50 border border-danger/20 rounded-lg">
            <p class="text-sm text-danger" role="alert">{{ $message }}</p>
        </div>
    @enderror
    @error('dbMigrations')
        <div class="mb-4 p-3 bg-danger-subtle/50 border border-danger/20 rounded-lg">
            <p class="text-sm text-danger" role="alert">{{ $message }}</p>
        </div>
    @enderror

    <div class="grid sm:grid-cols-2 gap-4">
        {{-- Host --}}
        <div class="sm:col-span-2">
            <label for="db_host" class="block text-sm font-medium text-ink-secondary">
                Database Host
            </label>
            <input type="text"
                   id="db_host"
                   wire:model="dbHost"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="127.0.0.1"
                   autocomplete="off">
            <p class="mt-1 text-xs text-ink-tertiary">Use <code>127.0.0.1</code> for local or <code>localhost</code> (they differ!). Check your hosting panel for the correct value.</p>
            @error('dbHost') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Port --}}
        <div>
            <label for="db_port" class="block text-sm font-medium text-ink-secondary">Port</label>
            <input type="number"
                   id="db_port"
                   wire:model="dbPort"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="3306"
                   min="1" max="65535">
            @error('dbPort') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Database name --}}
        <div>
            <label for="db_database" class="block text-sm font-medium text-ink-secondary">
                Database Name <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="db_database"
                   wire:model="dbDatabase"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="openmail"
                   required
                   autocomplete="off">
            @error('dbDatabase') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="db_username" class="block text-sm font-medium text-ink-secondary">Username</label>
            <input type="text"
                   id="db_username"
                   wire:model="dbUsername"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="root"
                   autocomplete="off">
            @error('dbUsername') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="db_password" class="block text-sm font-medium text-ink-secondary">Password</label>
            <input type="password"
                   id="db_password"
                   wire:model="dbPassword"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   autocomplete="new-password">
            @error('dbPassword') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Test Connection --}}
    <div class="mt-6 space-y-3">
        <div class="flex items-center gap-3 flex-wrap">
            <button type="button"
                    wire:click="testDatabaseConnection"
                    wire:loading.attr="disabled"
                    wire:target="testDatabaseConnection"
                    class="px-4 py-2 border border-border text-sm font-medium rounded-md text-ink-secondary bg-white hover:bg-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60">
                <span wire:loading.remove wire:target="testDatabaseConnection">Test Connection</span>
                <span wire:loading wire:target="testDatabaseConnection">Testing…</span>
            </button>

            @if ($dbTestResult)
                <span class="{{ $dbTestResult['success'] ? 'text-success' : 'text-danger' }} text-sm font-medium flex items-center gap-1">
                    @if ($dbTestResult['success'])
                        ✓ Connected
                        @if (isset($dbTestResult['server_version']))
                            <span class="text-ink-tertiary font-normal">(MySQL {{ $dbTestResult['server_version'] }})</span>
                        @endif
                    @else
                        ✗ Failed
                    @endif
                </span>
            @endif
        </div>

        @if ($dbTestResult && !$dbTestResult['success'])
            <div class="p-4 bg-danger-subtle/50 border border-danger/20 rounded-lg">
                <p class="text-sm text-danger font-medium">{{ $dbTestResult['error'] }}</p>
                @if (isset($dbTestResult['technical']))
                    <div x-data="{ open: false }" class="mt-2">
                        <button type="button"
                                @click="open = !open"
                                class="text-xs text-danger hover:underline focus:outline-none">
                            Show technical details
                        </button>
                        <pre x-show="open"
                             x-transition
                             class="mt-2 p-3 bg-danger-subtle/50 rounded text-xs font-mono text-danger overflow-auto max-h-32">{{ json_encode($dbTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- Run Migrations --}}
    @if ($dbTestResult && $dbTestResult['success'])
        <div class="mt-4 p-4 bg-primary-subtle/50 border border-primary/20 rounded-lg">
            <h3 class="text-sm font-medium text-primary">Run Database Migrations</h3>
            <p class="mt-1 text-sm text-primary">
                Create the OpenMail database tables. This is safe to run more than once \u2014 already-created tables are skipped.
            </p>
            <div class="mt-3 flex items-center gap-3 flex-wrap">
                <button type="button"
                        wire:click="runMigrations"
                        wire:loading.attr="disabled"
                        wire:target="runMigrations"
                        {{ $migrationsRan ? 'disabled' : '' }}
                        class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="runMigrations">
                        {{ $migrationsRan ? '✓ Migrations Complete' : 'Run Migrations' }}
                    </span>
                    <span wire:loading wire:target="runMigrations">Running…</span>
                </button>

                @if ($migrationResult && !$migrationResult['success'])
                    <span class="text-sm text-danger font-medium">✗ Migration failed</span>
                @elseif ($migrationsRan)
                    <span class="text-sm text-success font-medium">✓ Tables created successfully</span>
                @endif
            </div>

            @if ($migrationResult && !$migrationResult['success'])
                <div class="mt-3 p-3 bg-danger-subtle/50 border border-danger/20 rounded text-xs font-mono text-danger overflow-auto max-h-32">
                    {{ $migrationResult['error'] ?? 'Unknown error' }}
                </div>
            @endif
        </div>
    @endif

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-border text-ink-secondary text-sm font-medium rounded-md hover:bg-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                class="px-6 py-2.5 bg-primary text-white text-sm font-medium rounded-md hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2">
            Continue →
        </button>
    </div>
</div>
