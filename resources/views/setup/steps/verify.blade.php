<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Verify &amp; Finish Installation</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Execute final automated checks to ensure all database connections, mail servers, and configuration settings are verified before locking the installation.
        </p>
    </div>

    {{-- Run Verification Initial CTA --}}
    @if (!$verificationRun)
        <div class="setup-panel text-center py-10 px-6 border-dashed">
            <div class="w-12 h-12 rounded-xl bg-indigo-500/10 text-indigo-500 flex items-center justify-center mx-auto mb-4">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-ink">Ready for System Health Check</h3>
            <p class="text-xs text-ink-secondary max-w-md mx-auto mt-1 mb-6">
                Click below to validate your database credentials, schema status, and mail server connectivity.
            </p>
            <button type="button"
                    wire:click="runVerification"
                    wire:loading.attr="disabled"
                    wire:target="runVerification"
                    class="setup-btn-primary">
                <span wire:loading.remove wire:target="runVerification">Run Pre-Flight Checks</span>
                <span wire:loading wire:target="runVerification" class="inline-flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                    Verifying System…
                </span>
            </button>
        </div>
    @endif

    {{-- Verification Results --}}
    @if ($verificationRun)
        @php
            $allPassed = collect($verificationResults)->every(fn($c) => $c['passed']);
        @endphp

        <div class="space-y-3">
            @foreach ($verificationResults as $check)
                <div class="setup-panel !p-4 flex items-start justify-between gap-4 transition-all">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <p class="text-sm font-semibold text-ink">
                                {{ $check['label'] }}
                            </p>
                        </div>

                        @if (!$check['passed'] && isset($check['error']))
                            <p class="mt-1 text-xs text-rose-500 dark:text-rose-400 font-medium">
                                {{ $check['error'] }}
                            </p>
                        @endif

                        @if (!$check['passed'] && isset($check['fixStep']) && $check['fixStep'])
                            <button type="button"
                                    wire:click="goToStep({{ $check['fixStep'] }})"
                                    class="mt-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline focus:outline-none flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11 15l-3-3m0 0l3-3m-3 3h8"/></svg>
                                Go to Step {{ $check['fixStep'] }} to adjust settings
                            </button>
                        @endif

                        @if (isset($check['technical']) && $check['technical'])
                            <div x-data="{ open: false }" class="mt-2">
                                <button type="button"
                                        @click="open = !open"
                                        class="text-[11px] text-ink-tertiary hover:text-ink focus:outline-none flex items-center gap-1">
                                    <span x-text="open ? 'Hide technical logs' : 'Show technical logs'"></span>
                                    <svg class="w-3 h-3 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </button>
                                <pre x-show="open"
                                     x-transition
                                     class="mt-1.5 p-2.5 bg-black/40 rounded-lg text-xs font-mono text-ink-secondary overflow-auto max-h-32 border border-border/60">{{ json_encode($check['technical'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>

                    <div class="flex-shrink-0">
                        @if ($check['passed'])
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                Pass
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-600 dark:text-rose-400 border border-rose-500/20">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                Failed
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Re-run Verification --}}
        <div class="mt-4 flex justify-end">
            <button type="button"
                    wire:click="runVerification"
                    wire:loading.attr="disabled"
                    class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                Re-run pre-flight checks
            </button>
        </div>

        {{-- Summary Banner --}}
        <div class="mt-6">
            @if ($allPassed)
                <div class="p-4 setup-callout-success flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-emerald-600 dark:text-emerald-400">All checks passed! OpenMail is ready to launch.</p>
                        <p class="mt-0.5 text-xs text-ink-secondary">Click "Complete Installation" to finalize setup, seed your administrator account, and activate OpenMail.</p>
                    </div>
                </div>
            @else
                <div class="p-4 setup-callout-error flex items-start gap-3.5">
                    <svg class="w-5 h-5 text-rose-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-bold text-rose-600 dark:text-rose-400">Some pre-flight checks failed.</p>
                        <p class="mt-0.5 text-xs text-ink-secondary">Please review and resolve the flagged items above before completing installation.</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Finish Button --}}
        @if ($allPassed)
            <div class="mt-8 flex justify-end">
                <button type="button"
                        wire:click="finish"
                        wire:loading.attr="disabled"
                        wire:target="finish"
                        class="setup-btn-primary !bg-gradient-to-r !from-emerald-600 !via-emerald-500 !to-teal-600 !shadow-emerald-500/25 hover:!shadow-emerald-500/40 !px-8 !py-3 text-base">
                    <span wire:loading.remove wire:target="finish" class="inline-flex items-center gap-2">
                        Complete Installation
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </span>
                    <span wire:loading wire:target="finish" class="inline-flex items-center gap-2">
                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        Finalizing Setup…
                    </span>
                </button>
            </div>
        @endif
    @endif

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
    </div>
</div>
