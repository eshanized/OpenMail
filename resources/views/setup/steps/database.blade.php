<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Database Configuration</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Connect OpenMail to your MySQL or MariaDB database. OpenMail stores application state and preferences &mdash; your email messages remain on your mail server.
        </p>
    </div>

    {{-- Connection/Migration Error Banners --}}
    @error('dbConnection')
        <div class="mb-6 p-4 setup-callout-error flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm font-medium" role="alert">{{ $message }}</p>
        </div>
    @enderror

    @error('dbMigrations')
        <div class="mb-6 p-4 setup-callout-error flex items-start gap-3 shadow-sm">
            <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm font-medium" role="alert">{{ $message }}</p>
        </div>
    @enderror

    {{-- Form Grid --}}
    <div class="grid sm:grid-cols-2 gap-5">
        {{-- Host --}}
        <div class="sm:col-span-2">
            <label for="db_host" class="block text-sm font-semibold text-ink">
                Database Host <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="db_host"
                       wire:model="dbHost"
                       class="setup-input font-mono"
                       placeholder="127.0.0.1"
                       autocomplete="off">
            </div>
            <p class="mt-1.5 text-xs text-ink-tertiary">
                Usually <span class="setup-code">127.0.0.1</span> or <span class="setup-code">localhost</span>. Check your cPanel / hosting panel if hosted remotely.
            </p>
            @error('dbHost') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Port --}}
        <div>
            <label for="db_port" class="block text-sm font-semibold text-ink">
                Port <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="number"
                       id="db_port"
                       wire:model="dbPort"
                       class="setup-input font-mono"
                       placeholder="3306"
                       min="1" max="65535">
            </div>
            @error('dbPort') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Database Name --}}
        <div>
            <label for="db_database" class="block text-sm font-semibold text-ink">
                Database Name <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="db_database"
                       wire:model="dbDatabase"
                       class="setup-input font-mono"
                       placeholder="openmail"
                       required
                       autocomplete="off">
            </div>
            @error('dbDatabase') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Username --}}
        <div>
            <label for="db_username" class="block text-sm font-semibold text-ink">
                Username <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="db_username"
                       wire:model="dbUsername"
                       class="setup-input font-mono"
                       placeholder="root"
                       autocomplete="off">
            </div>
            @error('dbUsername') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Password --}}
        <div>
            <label for="db_password" class="block text-sm font-semibold text-ink">
                Password
            </label>
            <div class="mt-1.5">
                <input type="password"
                       id="db_password"
                       wire:model="dbPassword"
                       class="setup-input font-mono"
                       autocomplete="new-password"
                       placeholder="••••••••">
            </div>
            @error('dbPassword') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Actions & Verification Panels --}}
    <div class="mt-8 pt-6 border-t border-border/70 space-y-4">
        {{-- Test Connection Section --}}
        <div class="setup-panel">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <h3 class="text-sm font-bold text-ink">Test Connection</h3>
                    <p class="text-xs text-ink-secondary mt-0.5">Verify database connectivity before proceeding.</p>
                </div>

                <div class="flex items-center gap-3">
                    <button type="button"
                            wire:click="testDatabaseConnection"
                            wire:loading.attr="disabled"
                            wire:target="testDatabaseConnection"
                            class="setup-btn-secondary !py-2 !px-4 text-xs font-semibold">
                        <span wire:loading.remove wire:target="testDatabaseConnection">Test Connection</span>
                        <span wire:loading wire:target="testDatabaseConnection" class="inline-flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Testing…
                        </span>
                    </button>

                    @if ($dbTestResult)
                        <div class="flex items-center gap-1.5">
                            @if ($dbTestResult['success'])
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Connected
                                    @if (isset($dbTestResult['server_version']))
                                        <span class="opacity-70 font-mono">({{ $dbTestResult['server_version'] }})</span>
                                    @endif
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                    Connection Failed
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Failed connection details --}}
            @if ($dbTestResult && !$dbTestResult['success'])
                <div class="mt-4 p-3.5 setup-callout-error">
                    <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">{{ $dbTestResult['error'] }}</p>
                    @if (isset($dbTestResult['technical']))
                        <div x-data="{ open: false }" class="mt-2.5">
                            <button type="button"
                                    @click="open = !open"
                                    class="text-xs text-rose-500 dark:text-rose-400 font-medium hover:underline focus:outline-none flex items-center gap-1">
                                <span x-text="open ? 'Hide technical details' : 'Show technical details'"></span>
                                <svg class="w-3.5 h-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <pre x-show="open"
                                 x-transition
                                 class="mt-2 p-3 bg-black/40 rounded-lg text-xs font-mono text-rose-300 overflow-auto max-h-36 border border-rose-500/20">{{ json_encode($dbTestResult['technical'], JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>

                {{-- Offer to Create Missing Database --}}
                @if (!empty($dbTestResult['database_missing']))
                    <div class="mt-4 p-4 setup-callout-warning">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded bg-amber-500/15 text-amber-500 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-bold text-amber-600 dark:text-amber-400">Database Does Not Exist</h4>
                                <p class="mt-1 text-xs text-ink-secondary">
                                    The database <code class="setup-code">{{ $dbDatabase }}</code> was not found on the server. OpenMail can attempt to create it automatically using your database credentials.
                                </p>
                                <div class="mt-3 flex items-center gap-3 flex-wrap">
                                    <button type="button"
                                            wire:click="createDatabase"
                                            wire:loading.attr="disabled"
                                            wire:target="createDatabase"
                                            class="inline-flex items-center gap-2 px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-medium rounded transition-colors">
                                        <span wire:loading.remove wire:target="createDatabase">Create Database</span>
                                        <span wire:loading wire:target="createDatabase">Creating…</span>
                                    </button>

                                    @if ($createDbResult && !$createDbResult['success'])
                                        <span class="text-xs text-rose-500 dark:text-rose-400 font-semibold">✗ {{ $createDbResult['error'] }}</span>
                                    @elseif ($createDbResult && $createDbResult['success'])
                                        <span class="text-xs text-emerald-500 dark:text-emerald-400 font-semibold">✓ Database created successfully!</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @endif
        </div>

        {{-- Migrations Section (Shown upon successful connection) --}}
        @if ($dbTestResult && $dbTestResult['success'])
            <div class="setup-panel border-border bg-surface-raised">
                <div class="flex items-center justify-between flex-wrap gap-4">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-sm font-bold text-ink">Database Migrations</h3>
                            @if ($migrationsRan)
                                <span class="px-2 py-0.5 rounded text-[10px] font-medium uppercase bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">Ready</span>
                            @endif
                        </div>
                        <p class="text-xs text-ink-secondary mt-1">
                            Set up the database tables required for sessions, preferences, and caching.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <button type="button"
                                wire:click="runMigrations"
                                wire:loading.attr="disabled"
                                wire:target="runMigrations"
                                {{ $migrationsRan ? 'disabled' : '' }}
                                class="setup-btn-primary !py-1.5 !px-3.5 text-xs font-medium">
                            <span wire:loading.remove wire:target="runMigrations">
                                {{ $migrationsRan ? '✓ Migrations Installed' : 'Run Migrations' }}
                            </span>
                            <span wire:loading wire:target="runMigrations" class="inline-flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Running Migrations…
                            </span>
                        </button>
                    </div>
                </div>

                @if ($migrationResult && !$migrationResult['success'])
                    <div class="mt-4 p-3.5 setup-callout-error">
                        <p class="text-xs font-semibold text-rose-600 dark:text-rose-400">Migration failed</p>
                        <pre class="mt-2 p-2.5 bg-black/40 rounded text-xs font-mono text-rose-300 overflow-auto max-h-32 border border-rose-500/20">{{ $migrationResult['error'] ?? 'Unknown error' }}</pre>
                    </div>
                @endif
            </div>
        @endif
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
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
