<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">System Requirements</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Verifying that your PHP environment and server permissions meet the requirements for OpenMail.
        </p>
    </div>

    @php
        $checker = app(\App\Services\SystemRequirementsChecker::class);
        $allRequiredPassed = $checker->allRequiredPassed($requirements);
    @endphp

    {{-- Required Checks --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold text-ink-tertiary uppercase tracking-wider">
                Core Requirements
            </h3>
            <span class="text-xs font-semibold {{ $allRequiredPassed ? 'text-emerald-500' : 'text-rose-500' }}">
                {{ $allRequiredPassed ? 'All checks passing' : 'Action required' }}
            </span>
        </div>

        <div class="space-y-2.5">
            {{-- PHP Version --}}
            <div class="setup-panel !p-4 flex items-center justify-between gap-4 transition-all">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="text-sm font-semibold text-ink">{{ $requirements['php_version']['label'] }}</p>
                    </div>
                    <p class="text-xs text-ink-secondary mt-1">
                        Required: <span class="font-mono text-ink">{{ $requirements['php_version']['required'] }}</span>
                        <span class="mx-1 text-border">·</span>
                        Current: <span class="font-mono text-ink">{{ $requirements['php_version']['current'] }}</span>
                    </p>
                    @if (!$requirements['php_version']['passed'] && $requirements['php_version']['fix'])
                        <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium">{{ $requirements['php_version']['fix'] }}</p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    @if ($requirements['php_version']['passed'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Pass
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Fail
                        </span>
                    @endif
                </div>
            </div>

            {{-- MySQL Driver --}}
            <div class="setup-panel !p-4 flex items-center justify-between gap-4 transition-all">
                <div>
                    <p class="text-sm font-semibold text-ink">{{ $requirements['database']['label'] }}</p>
                    <p class="text-xs text-ink-secondary mt-1">
                        Current driver: <span class="font-mono text-ink">{{ $requirements['database']['current'] }}</span>
                    </p>
                    @if (!$requirements['database']['passed'] && $requirements['database']['fix'])
                        <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium">{{ $requirements['database']['fix'] }}</p>
                    @endif
                </div>
                <div class="flex-shrink-0">
                    @if ($requirements['database']['passed'])
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            Pass
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            Fail
                        </span>
                    @endif
                </div>
            </div>

            {{-- Extensions --}}
            @foreach ($requirements['extensions'] as $ext)
                <div class="setup-panel !p-4 flex items-center justify-between gap-4 transition-all">
                    <div>
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-ink">{{ $ext['label'] }}</p>
                            <span class="setup-code">ext-{{ strtolower(str_replace(' ', '', $ext['label'])) }}</span>
                        </div>
                        @if (isset($ext['description']))
                            <p class="text-xs text-ink-secondary mt-1">{{ $ext['description'] }}</p>
                        @endif
                        @if (!$ext['passed'] && isset($ext['fix']))
                            <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-medium">{{ $ext['fix'] }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        @if ($ext['passed'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Pass
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Fail
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach

            {{-- Writable Directories --}}
            @foreach ($requirements['writable'] as $path)
                <div class="setup-panel !p-4 flex items-center justify-between gap-4 transition-all">
                    <div>
                        <p class="text-sm font-semibold font-mono text-ink">{{ $path['label'] }}/</p>
                        <p class="text-xs text-ink-secondary mt-0.5">Directory write permissions</p>
                        @if (!$path['passed'] && isset($path['fix']))
                            <p class="text-xs text-rose-500 dark:text-rose-400 mt-1.5 font-mono whitespace-pre-line">{{ $path['fix'] }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        @if ($path['passed'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Writable
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Unwritable
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Recommended Settings --}}
    @if (!empty($requirements['recommended']))
        <div class="mt-8 space-y-4">
            <h3 class="text-xs font-bold text-ink-tertiary uppercase tracking-wider">
                Recommended Configuration
            </h3>

            <div class="space-y-2.5">
                @foreach ($requirements['recommended'] as $rec)
                    <div class="setup-panel !p-4 flex items-center justify-between gap-4 transition-all">
                        <div>
                            <p class="text-sm font-semibold font-mono text-ink">{{ $rec['label'] }}</p>
                            <p class="text-xs text-ink-secondary mt-1">
                                Recommended: <span class="font-mono text-ink">{{ $rec['required'] }}</span>
                                <span class="mx-1 text-border">·</span>
                                Current: <span class="font-mono text-ink">{{ $rec['current'] }}</span>
                            </p>
                            @if (!$rec['passed'] && isset($rec['fix']))
                                <p class="text-xs text-amber-500 dark:text-amber-400 mt-1.5">{{ $rec['fix'] }}</p>
                            @endif
                        </div>
                        <div class="flex-shrink-0">
                            @if ($rec['passed'])
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                    Optimal
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-500/10 text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                    Notice
                                </span>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Overall Summary Callout --}}
    <div class="mt-8">
        @if ($allRequiredPassed)
            <div class="p-4 setup-callout-success flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm font-semibold">All mandatory system requirements passed. You can proceed with confidence.</p>
            </div>
        @else
            <div class="p-4 setup-callout-error flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <p class="text-sm font-semibold">Some mandatory checks failed. Please resolve the issues above before continuing.</p>
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
                @if(!$allRequiredPassed) disabled @endif
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
