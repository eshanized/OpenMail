<div>
    {{-- Session resume banner --}}
    @if (Session::has('openmail:setup:wizard') && $currentStep > 1 && !$installationComplete)
        <div class="mb-6 p-4 setup-callout-info rounded-xl shadow-sm flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-500/15 flex items-center justify-center flex-shrink-0 text-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-ink">Installation in progress</p>
                    <p class="text-xs text-ink-secondary mt-0.5">You were on Step {{ $currentStep }}. Would you like to resume where you left off?</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="continueFromSession"
                        class="setup-btn-primary !py-2 !px-4 text-xs font-semibold">
                    Continue
                </button>
                <button type="button" wire:click="resetWizard"
                        class="setup-btn-secondary !py-2 !px-4 text-xs font-medium">
                    Start Over
                </button>
            </div>
        </div>
    @endif

    {{-- Flash error banner --}}
    @if (session('error'))
        <div class="mb-6 p-4 setup-callout-error rounded-xl shadow-sm flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-sm font-medium" role="alert">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Stepper Navigation --}}
    @php
        $stepLabels = [
            1 => 'Welcome',
            2 => 'Requirements',
            3 => 'Database',
            4 => 'Mail Server',
            5 => 'Application',
            6 => 'Admin',
            7 => 'Security',
            8 => 'Verify',
        ];
    @endphp

    <nav aria-label="Setup wizard progress" class="mb-8">
        {{-- Mobile Progress Bar --}}
        <div class="sm:hidden mb-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider">
                    Step {{ $currentStep }} of {{ $totalSteps }}
                </span>
                <span class="text-sm font-bold text-ink">
                    {{ $stepLabels[$currentStep] ?? '' }}
                </span>
            </div>
            <div class="h-2 bg-surface-sunken dark:bg-white/5 border border-border/60 rounded-full overflow-hidden p-0.5">
                <div class="h-full bg-gradient-to-r from-indigo-500 to-blue-500 rounded-full transition-all duration-300 shadow-sm"
                     style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
            </div>
        </div>

        {{-- Desktop Stepper --}}
        <div class="hidden sm:block">
            <ol class="flex items-center justify-between" role="list">
                @for ($i = 1; $i <= $totalSteps; $i++)
                    @php
                        $isCompleted = in_array($i, $completedSteps);
                        $isCurrent   = $i === $currentStep;
                        $isFuture    = $i > $currentStep && ! $isCompleted;
                    @endphp

                    <li class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                        <div class="flex flex-col items-center group">
                            <div class="flex items-center justify-center w-9 h-9 rounded-full text-xs font-bold transition-all duration-300 relative
                                {{ $isCompleted ? 'bg-success text-white shadow-md shadow-emerald-500/20 ring-2 ring-emerald-500/30' : '' }}
                                {{ $isCurrent   ? 'bg-gradient-to-tr from-indigo-600 to-blue-500 text-white shadow-lg shadow-indigo-500/35 ring-4 ring-indigo-500/20 scale-105' : '' }}
                                {{ $isFuture    ? 'bg-surface-raised dark:bg-white/[0.04] text-ink-tertiary border border-border dark:border-white/10' : '' }}"
                                aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                                @if ($isCompleted)
                                    <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $i }}
                                @endif
                            </div>
                            <span class="mt-2 text-[11px] font-medium tracking-tight text-center max-w-[68px] leading-tight transition-colors duration-200
                                {{ $isCurrent ? 'text-indigo-600 dark:text-indigo-400 font-bold' : '' }}
                                {{ $isCompleted ? 'text-ink font-semibold' : '' }}
                                {{ $isFuture ? 'text-ink-tertiary' : '' }}">
                                {{ $stepLabels[$i] ?? '' }}
                            </span>
                        </div>

                        @if ($i < $totalSteps)
                            <div class="flex-1 h-0.5 mx-2 rounded-full transition-colors duration-300
                                {{ $isCompleted ? 'bg-emerald-500/80 dark:bg-emerald-400/70' : 'bg-border dark:bg-white/10' }}"></div>
                        @endif
                    </li>
                @endfor
            </ol>
        </div>
    </nav>

    {{-- Main Step Card --}}
    <div class="setup-card">
        @if ($currentStep === 1)
            @include('setup.steps.welcome')
        @endif

        @if ($currentStep === 2)
            @include('setup.steps.requirements')
        @endif

        @if ($currentStep === 3)
            @include('setup.steps.database')
        @endif

        @if ($currentStep === 4)
            @include('setup.steps.mail-config')
        @endif

        @if ($currentStep === 5)
            @include('setup.steps.app-settings')
        @endif

        @if ($currentStep === 6)
            @include('setup.steps.admin-account')
        @endif

        @if ($currentStep === 7)
            @include('setup.steps.security')
        @endif

        @if ($currentStep === 8)
            @include('setup.steps.verify')
        @endif
    </div>
</div>
