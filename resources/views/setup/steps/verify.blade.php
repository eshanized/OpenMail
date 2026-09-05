<div>
    <h3 class="text-lg font-medium text-gray-900">Verify & Finish</h3>
    <p class="mt-2 text-sm text-gray-600">Run the verification suite to ensure everything is configured correctly.</p>

    @if (!$verificationRun)
        <div class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-md">
            <p class="text-sm text-blue-800">Click "Run Verification" to check all configuration before finishing the installation.</p>
        </div>

        <div class="mt-4 flex justify-end">
            <button type="button" wire:click="runVerification" wire:loading.attr="disabled" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <span wire:loading.remove>Run Verification</span>
                <span wire:loading>Running checks...</span>
            </button>
        </div>
    @else
        <div class="mt-4 space-y-3">
            @foreach ($verificationResults as $check)
                <div class="p-4 border rounded-lg {{ $check['passed'] ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200' }}">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="font-medium text-gray-900 flex items-center">
                                <span class="{{ $check['passed'] ? 'text-green-600' : 'text-red-600' }} mr-2">
                                    {{ $check['passed'] ? '✓' : '✗' }}
                                </span>
                                {{ $check['label'] }}
                            </p>
                            @if (!$check['passed'] && $check['error'])
                                <p class="mt-1 text-sm text-red-700">{{ $check['error'] }}</p>
                            @endif
                        </div>

                        @if (!$check['passed'] && $check['fixable'] && $check['fixStep'])
                            <button type="button" wire:click="goToStep({{ $check['fixStep'] }})" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Fix issues → Step {{ $check['fixStep'] }}
                            </button>
                        @endif
                    </div>

                    @if (!$check['passed'] && isset($check['technical']))
                        <div class="mt-2" x-data="{ open: false }">
                            <button type="button" @click="open = !open" class="text-xs text-blue-600 hover:underline">Show technical details</button>
                            <div x-show="open" x-transition class="mt-2 p-3 bg-gray-100 rounded text-xs font-mono text-gray-700 overflow-auto max-h-40">
                                <pre>{{ json_encode($check['technical'], JSON_PRETTY_PRINT) }}</pre>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 flex justify-between">
            <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Previous
            </button>

            @if ($allVerificationPassed)
                <button type="button" wire:click="finish" wire:loading.attr="disabled" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <span wire:loading.remove>Finish Installation</span>
                    <span wire:loading>Finalizing...</span>
                </button>
            @else
                <button type="button" disabled class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-gray-400 cursor-not-allowed">
                    Fix issues above to continue
                </button>
            @endif
        </div>
    @endif
</div>