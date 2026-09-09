<div>
    {{-- ─────────────────────────────────────────────────────────────────────
         Session resume banner
    ───────────────────────────────────────────────────────────────────────── --}}
    @if (Session::has('openmail:setup:wizard') && $currentStep > 1 && !$installationComplete)
        <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
            <div class="flex items-center justify-between flex-wrap gap-3">
                <div>
                    <p class="text-sm font-medium text-blue-900">Installation in progress</p>
                    <p class="text-sm text-blue-700">You were on Step {{ $currentStep }}. Continue where you left off?</p>
                </div>
                <div class="flex gap-2">
                    <button wire:click="continueFromSession"
                            class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Continue
                    </button>
                    <button wire:click="resetWizard"
                            class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
                        Start Over
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Flash messages --}}
    @if (session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800" role="alert">{{ session('error') }}</p>
        </div>
    @endif

    {{-- ─────────────────────────────────────────────────────────────────────
         Progress indicator
    ───────────────────────────────────────────────────────────────────────── --}}
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
        {{-- Mobile: simple text indicator --}}
        <div class="sm:hidden text-sm text-gray-600 font-medium mb-2">
            Step {{ $currentStep }} of {{ $totalSteps }}: {{ $stepLabels[$currentStep] ?? '' }}
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
                        <div class="flex items-center justify-center w-9 h-9 rounded-full border-2 text-sm font-medium transition-colors
                            {{ $isCompleted ? 'bg-green-600 border-green-600 text-white' : '' }}
                            {{ $isCurrent  ? 'bg-blue-600 border-blue-600 text-white' : '' }}
                            {{ $isFuture   ? 'bg-white border-gray-300 text-gray-400' : '' }}"
                            aria-current="{{ $isCurrent ? 'step' : 'false' }}">
                            @if ($isCompleted)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $i }}
                            @endif
                        </div>
                        <span class="mt-1 text-xs {{ $isCurrent ? 'text-blue-700 font-semibold' : 'text-gray-500' }} text-center max-w-[56px] leading-tight">
                            {{ $stepLabels[$i] ?? '' }}
                        </span>
                    </div>

                    @if ($i < $totalSteps)
                        <div class="flex-1 h-0.5 mx-1 {{ $isCompleted ? 'bg-green-400' : 'bg-gray-200' }}"></div>
                    @endif
                </li>
            @endfor
        </ol>
    </nav>

    {{-- ─────────────────────────────────────────────────────────────────────
         Step card
    ───────────────────────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        {{-- Step 1: Welcome --}}
        @if ($currentStep === 1)
            @include('setup.steps.welcome')
        @endif

        {{-- Step 2: System Requirements --}}
        @if ($currentStep === 2)
            @include('setup.steps.requirements')
        @endif

        {{-- Step 3: Database --}}
        @if ($currentStep === 3)
            @include('setup.steps.database')
        @endif

        {{-- Step 4: Mail Configuration --}}
        @if ($currentStep === 4)
            @include('setup.steps.mail-config')
        @endif

        {{-- Step 5: Application Settings --}}
        @if ($currentStep === 5)
            @include('setup.steps.app-settings')
        @endif

        {{-- Step 6: Administrator Account --}}
        @if ($currentStep === 6)
            @include('setup.steps.admin-account')
        @endif

        {{-- Step 7: Security --}}
        @if ($currentStep === 7)
            @include('setup.steps.security')
        @endif

        {{-- Step 8: Verify & Finish --}}
        @if ($currentStep === 8)
            @include('setup.steps.verify')
        @endif
    </div>
</div>
