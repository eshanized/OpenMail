<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">Verify &amp; Finish Installation</h2>
        <p class="mt-1 text-sm text-gray-600">
            Run a final verification to confirm everything is ready. Fix any failed checks before completing.
        </p>
    </div>

    {{-- Run verification button --}}
    @if (!$verificationRun)
        <div class="flex items-center gap-3">
            <button type="button"
                    wire:click="runVerification"
                    wire:loading.attr="disabled"
                    wire:target="runVerification"
                    class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
                <span wire:loading.remove wire:target="runVerification">Run Verification Checks</span>
                <span wire:loading wire:target="runVerification">Running checks…</span>
            </button>
        </div>
    @endif

    {{-- Verification results --}}
    @if ($verificationRun)
        @php
            $allPassed = collect($verificationResults)->every(fn($c) => $c['passed']);
        @endphp

        <div class="space-y-3">
            @foreach ($verificationResults as $check)
                <div class="flex items-start justify-between p-4 border rounded-lg
                    {{ $check['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                    <div class="flex-1">
                        <p class="text-sm font-medium {{ $check['passed'] ? 'text-green-900' : 'text-red-900' }}">
                            {{ $check['label'] }}
                        </p>
                        @if (!$check['passed'] && isset($check['error']))
                            <p class="mt-1 text-sm {{ $check['passed'] ? 'text-green-700' : 'text-red-700' }}">
                                {{ $check['error'] }}
                            </p>
                        @endif
                        @if (!$check['passed'] && isset($check['fixStep']) && $check['fixStep'])
                            <button type="button"
                                    wire:click="goToStep({{ $check['fixStep'] }})"
                                    class="mt-2 text-xs text-blue-700 hover:underline focus:outline-none">
                                ← Go to Step {{ $check['fixStep'] }} to fix this
                            </button>
                        @endif
                        @if (isset($check['technical']) && $check['technical'])
                            <div x-data="{ open: false }" class="mt-2">
                                <button type="button"
                                        @click="open = !open"
                                        class="text-xs text-gray-500 hover:underline focus:outline-none">
                                    Show technical details
                                </button>
                                <pre x-show="open"
                                     x-transition
                                     class="mt-1 p-2 bg-gray-100 rounded text-xs font-mono text-gray-700 overflow-auto max-h-32">{{ json_encode($check['technical'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        @endif
                    </div>
                    <div class="ml-4 flex-shrink-0">
                        @if ($check['passed'])
                            <span class="text-green-700 font-semibold text-sm" aria-label="Pass">✓ Pass</span>
                        @else
                            <span class="text-red-700 font-semibold text-sm" aria-label="Fail">✗ Fail</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Re-run button --}}
        <div class="mt-4">
            <button type="button"
                    wire:click="runVerification"
                    wire:loading.attr="disabled"
                    class="text-sm text-blue-700 hover:underline focus:outline-none">
                Re-run verification
            </button>
        </div>

        {{-- Summary --}}
        <div class="mt-6 p-4 rounded-lg {{ $allPassed ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
            @if ($allPassed)
                <p class="text-sm font-semibold text-green-900">✓ All checks passed! OpenMail is ready to install.</p>
                <p class="mt-1 text-sm text-green-700">Clicking "Complete Installation" will create your administrator account and lock the installation.</p>
            @else
                <p class="text-sm font-semibold text-red-900">Some checks failed. Fix the issues above and re-run verification before completing installation.</p>
            @endif
        </div>

        {{-- Complete installation --}}
        @if ($allPassed)
            <div class="mt-6">
                <button type="button"
                        wire:click="finish"
                        wire:loading.attr="disabled"
                        wire:target="finish"
                        class="w-full sm:w-auto px-8 py-3 bg-green-600 text-white text-sm font-semibold rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-60">
                    <span wire:loading.remove wire:target="finish">Complete Installation →</span>
                    <span wire:loading wire:target="finish">Installing…</span>
                </button>
            </div>
        @endif
    @endif

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
    </div>
</div>