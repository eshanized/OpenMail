<div>
    {{-- Session resume banner --}}
    @if (Session::has('openmail:setup:wizard') && $currentStep > 1 && !$installationComplete)
        <div class="mb-5 p-3.5 bg-surface-sunken border border-border rounded flex items-center justify-between flex-wrap gap-3">
            <div>
                <p class="text-xs font-semibold text-ink">Installation in progress</p>
                <p class="text-xs text-ink-secondary mt-0.5">Resume Step {{ $currentStep }}?</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" wire:click="continueFromSession"
                        class="px-3 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors cursor-pointer">
                    Continue
                </button>
                <button type="button" wire:click="resetWizard"
                        class="px-3 py-1.5 rounded border border-border text-ink-secondary hover:text-ink hover:bg-hover text-xs transition-colors cursor-pointer">
                    Start over
                </button>
            </div>
        </div>
    @endif

    {{-- Flash error banner --}}
    @if (session('error'))
        <div class="mb-5 p-3.5 bg-error-subtle border border-error/20 rounded flex items-start gap-2.5">
            <svg class="w-4 h-4 text-error flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <p class="text-xs font-medium text-error" role="alert">{{ session('error') }}</p>
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

    <nav aria-label="Setup wizard progress" class="mb-6">
        {{-- Mobile Progress Bar --}}
        <div class="sm:hidden mb-4">
            <div class="flex items-center justify-between mb-1.5">
                <span class="text-xs text-ink-tertiary uppercase tracking-wider font-medium">
                    Step {{ $currentStep }} of {{ $totalSteps }}
                </span>
                <span class="text-xs font-semibold text-ink">
                    {{ $stepLabels[$currentStep] ?? '' }}
                </span>
            </div>
            <div class="h-1.5 bg-surface-sunken border border-border rounded-full overflow-hidden">
                <div class="h-full bg-primary transition-all duration-200"
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
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center w-7 h-7 rounded-full text-xs font-semibold transition-colors
                                {{ $isCompleted ? 'bg-success text-white' : '' }}
                                {{ $isCurrent   ? 'bg-primary text-white' : '' }}
                                {{ $isFuture    ? 'bg-surface text-ink-tertiary border border-border' : '' }}"
                                aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                                @if ($isCompleted)
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $i }}
                                @endif
                            </div>
                            <span class="mt-1.5 text-[11px] tracking-tight text-center max-w-[64px] leading-tight
                                {{ $isCurrent ? 'text-ink font-semibold' : 'text-ink-tertiary' }}">
                                {{ $stepLabels[$i] ?? '' }}
                            </span>
                        </div>

                        @if ($i < $totalSteps)
                            <div class="flex-1 h-px mx-2 transition-colors {{ $isCompleted ? 'bg-border-strong' : 'bg-border' }}"></div>
                        @endif
                    </li>
                @endfor
            </ol>
        </div>
    </nav>

    {{-- Main Step Card --}}
    <div class="setup-card bg-surface-raised border border-border rounded p-4 sm:p-6 md:p-8">
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
