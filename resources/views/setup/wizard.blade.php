@extends('layouts.setup')

@section('content')
    <div>
        @if (session('openmail:wizard'))
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-800">Installation in progress</p>
                        <p class="text-sm text-blue-700">You were on step {{ session('openmail:wizard.currentStep') }}. What would you like to do?</p>
                    </div>
                    <div class="flex space-x-2">
                        <button wire:click="continueFromSession" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">
                            Continue
                        </button>
                        <button wire:click="resetWizard" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 text-sm">
                            Start Over
                        </button>
                    </div>
                </div>
            </div>
        @endif

        <nav class="mb-8" aria-label="Setup wizard progress">
            <ol class="flex items-center justify-between">
                @for ($i = 1; $i <= 8; $i++)
                    <li class="flex items-center">
                        <div class="flex items-center justify-center w-10 h-10 rounded-full border-2
                            @if ($i < 1) bg-blue-600 border-blue-600 text-white
                            @elseif ($i === 1) bg-blue-600 border-blue-600 text-white
                            @else bg-white border-gray-300 text-gray-500
                            @endif
                            text-sm font-medium">
                            {{ $i }}
                        </div>
                        @if ($i < 8)
                            <div class="w-full sm:w-24 h-0.5
                                @if ($i < 1) bg-blue-600 @else bg-gray-200 @endif
                                ml-2 mr-2"></div>
                        @endif
                    </li>
                @endfor
            </ol>
            <div class="mt-4 text-center text-sm text-gray-500">
                <span x-text="stepLabels[currentStep] || 'Step ' + currentStep"></span>
            </div>
        </nav>

        <div class="space-y-6">
            <!-- Step 1: Welcome -->
            <div x-show="currentStep === 1" x-transition>
                @include('setup.steps.welcome')
            </div>

            <!-- Step 2: System Requirements -->
            <div x-show="currentStep === 2" x-transition>
                @include('setup.steps.requirements')
            </div>

            <!-- Step 3: Database Config -->
            <div x-show="currentStep === 3" x-transition>
                @include('setup.steps.database')
            </div>

            <!-- Step 4: Mail Config -->
            <div x-show="currentStep === 4" x-transition>
                @include('setup.steps.mail-config')
            </div>

            <!-- Step 5: App Settings -->
            <div x-show="currentStep === 5" x-transition>
                @include('setup.steps.app-settings')
            </div>

            <!-- Step 6: Admin Account -->
            <div x-show="currentStep === 6" x-transition>
                @include('setup.steps.admin-account')
            </div>

            <!-- Step 7: Security -->
            <div x-show="currentStep === 7" x-transition>
                @include('setup.steps.security')
            </div>

            <!-- Step 8: Verify & Finish -->
            <div x-show="currentStep === 8" x-transition>
                @include('setup.steps.verify')
            </div>
        </div>
    </div>
@endsection