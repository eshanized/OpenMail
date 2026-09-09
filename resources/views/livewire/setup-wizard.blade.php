<div>
    {{-- Session resume banner --}}
    @if (Session::has('openmail:setup:wizard') && $currentStep > 1 && !$installationComplete)
        <div class="mb-6 p-4 bg-info-subtle border border-info/20 rounded-xl">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-sm font-medium text-info">Installation in progress</p>
                    <p class="text-xs text-ink-secondary mt-0.5">You were on Step {{ $currentStep }}. Continue where you left off?</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="continueFromSession"
                            class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2 transition-colors">
                        Continue
                    </button>
                    <button wire:click="resetWizard"
                            class="px-4 py-2 bg-surface-raised border border-border text-ink-secondary text-sm font-medium rounded-lg hover:bg-hover focus:outline-none focus:ring-2 focus:ring-border-strong focus:ring-offset-2 transition-colors">
                        Start Over
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash messages --}}
    @if (session('error'))
        <div class="mb-4 p-4 bg-error-subtle border border-error/20 rounded-xl">
            <p class="text-sm text-error" role="alert">{{ session('error') }}</p>
        </div>
    @endif

    {{-- Progress indicator --}}
    @php
        $stepLabels = [
            1 => 'Welcome',
            2 => 'Requirements',
            3 => 'Database',
            4 => 'Mail Server',
            5 => 'Application',
            6 => 'Administrator',
            7 => 'Security',
            8 => 'Verify & Finish',
        ];
    @endphp

    <nav aria-label="Setup wizard progress" class="mb-8">
        {{-- Mobile --}}
        <div class="sm:hidden flex items-center justify-between mb-3">
            <div class="text-sm text-ink-secondary font-medium">
                Step {{ $currentStep }} of {{ $totalSteps }}
            </div>
            <div class="text-sm text-ink font-semibold">{{ $stepLabels[$currentStep] ?? '' }}</div>
        </div>
        <div class="sm:hidden h-1.5 bg-surface-sunken rounded-full overflow-hidden">
            <div class="h-full bg-primary rounded-full transition-all duration-300"
                 style="width: {{ ($currentStep / $totalSteps) * 100 }}%"></div>
        </div>

        {{-- Desktop: step bubbles --}}
        <ol class="hidden sm:flex items-center" role="list">
            @for ($i = 1; $i <= $totalSteps; $i++)
                <li class="flex items-center {{ $i < $totalSteps ? 'flex-1' : '' }}">
                    @php
                        $isCompleted = in_array($i, $completedSteps);
                        $isCurrent   = $i === $currentStep;
                        $isFuture    = $i > $currentStep && ! $isCompleted;
                    @endphp

                    <div class="flex flex-col items-center">
                        <div class="flex items-center justify-center w-8 h-8 rounded-full text-xs font-semibold transition-all duration-200
                            {{ $isCompleted ? 'bg-success text-white' : '' }}
                            {{ $isCurrent  ? 'bg-primary text-white shadow-md ring-4 ring-primary/10' : '' }}
                            {{ $isFuture   ? 'bg-surface-sunken text-ink-tertiary border border-border' : '' }}"
                            aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                            @if ($isCompleted)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        <span class="mt-1.5 text-[10px] {{ $isCurrent ? 'text-primary font-semibold' : 'text-ink-tertiary' }} text-center max-w-[56px] leading-tight">
                            {{ $stepLabels[$i] ?? '' }}
                        </span>
                    </div>

                    @if ($i < $totalSteps)
                        <div class="flex-1 h-0.5 mx-1.5 {{ $isCompleted ? 'bg-success' : 'bg-border' }} rounded-full"></div>
                    @endif
                </li>
            @endfor
        </ol>
    </nav>

    {{-- Step card --}}
    <div class="bg-surface-raised rounded-xl border border-border shadow-sm">
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
