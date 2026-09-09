<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-gray-900">System Requirements</h2>
        <p class="mt-1 text-sm text-gray-600">Checking that your server meets the requirements to run OpenMail.</p>
    </div>

    @php
        $checker = app(\App\Services\SystemRequirementsChecker::class);
        $allRequiredPassed = $checker->allRequiredPassed($requirements);
    @endphp

    {{-- PHP Version --}}
    <div class="space-y-3">
        <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Required</h3>

        {{-- PHP version --}}
        <div class="flex items-start justify-between p-3 border rounded-lg
            {{ $requirements['php_version']['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $requirements['php_version']['label'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">
                    Required: {{ $requirements['php_version']['required'] }} &bull;
                    Current: {{ $requirements['php_version']['current'] }}
                </p>
                @if (!$requirements['php_version']['passed'] && $requirements['php_version']['fix'])
                    <p class="text-xs text-red-700 mt-1">{{ $requirements['php_version']['fix'] }}</p>
                @endif
            </div>
            <span class="{{ $requirements['php_version']['passed'] ? 'text-green-700' : 'text-red-700' }} text-sm font-bold flex-shrink-0 ml-3" aria-label="{{ $requirements['php_version']['passed'] ? 'Pass' : 'Fail' }}">
                {{ $requirements['php_version']['passed'] ? '✓ Pass' : '✗ Fail' }}
            </span>
        </div>

        {{-- MySQL driver --}}
        <div class="flex items-start justify-between p-3 border rounded-lg
            {{ $requirements['database']['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
            <div>
                <p class="text-sm font-medium text-gray-900">{{ $requirements['database']['label'] }}</p>
                <p class="text-xs text-gray-500 mt-0.5">Current: {{ $requirements['database']['current'] }}</p>
                @if (!$requirements['database']['passed'] && $requirements['database']['fix'])
                    <p class="text-xs text-red-700 mt-1">{{ $requirements['database']['fix'] }}</p>
                @endif
            </div>
            <span class="{{ $requirements['database']['passed'] ? 'text-green-700' : 'text-red-700' }} text-sm font-bold flex-shrink-0 ml-3">
                {{ $requirements['database']['passed'] ? '✓ Pass' : '✗ Fail' }}
            </span>
        </div>

        {{-- PHP Extensions --}}
        @foreach ($requirements['extensions'] as $ext)
            <div class="flex items-start justify-between p-3 border rounded-lg
                {{ $ext['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $ext['label'] }}</p>
                    @if (isset($ext['description']))
                        <p class="text-xs text-gray-500 mt-0.5">{{ $ext['description'] }}</p>
                    @endif
                    @if (!$ext['passed'] && isset($ext['fix']))
                        <p class="text-xs text-red-700 mt-1">{{ $ext['fix'] }}</p>
                    @endif
                </div>
                <span class="{{ $ext['passed'] ? 'text-green-700' : 'text-red-700' }} text-sm font-bold flex-shrink-0 ml-3">
                    {{ $ext['passed'] ? '✓ Pass' : '✗ Fail' }}
                </span>
            </div>
        @endforeach

        {{-- Writable directories --}}
        @foreach ($requirements['writable'] as $path)
            <div class="flex items-start justify-between p-3 border rounded-lg
                {{ $path['passed'] ? 'border-green-200 bg-green-50' : 'border-red-200 bg-red-50' }}">
                <div>
                    <p class="text-sm font-medium text-gray-900 font-mono text-xs">{{ $path['label'] }}/</p>
                    @if (!$path['passed'] && isset($path['fix']))
                        <p class="text-xs text-red-700 mt-1 whitespace-pre-line">{{ $path['fix'] }}</p>
                    @endif
                </div>
                <span class="{{ $path['passed'] ? 'text-green-700' : 'text-red-700' }} text-sm font-bold flex-shrink-0 ml-3">
                    {{ $path['passed'] ? '✓ Pass' : '✗ Fail' }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Recommended --}}
    @if (!empty($requirements['recommended']))
        <div class="mt-6 space-y-3">
            <h3 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Recommended</h3>
            @foreach ($requirements['recommended'] as $rec)
                <div class="flex items-start justify-between p-3 border rounded-lg
                    {{ $rec['passed'] ? 'border-gray-200 bg-gray-50' : 'border-amber-200 bg-amber-50' }}">
                    <div>
                        <p class="text-sm font-medium text-gray-900 font-mono text-xs">{{ $rec['label'] }}</p>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Required: {{ $rec['required'] }} &bull; Current: {{ $rec['current'] }}
                        </p>
                        @if (!$rec['passed'] && isset($rec['fix']))
                            <p class="text-xs text-amber-800 mt-1">{{ $rec['fix'] }}</p>
                        @endif
                    </div>
                    <span class="{{ $rec['passed'] ? 'text-green-700' : 'text-amber-700' }} text-sm font-bold flex-shrink-0 ml-3">
                        {{ $rec['passed'] ? '✓ Pass' : '⚠ Warn' }}
                    </span>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Summary callout --}}
    <div class="mt-6 p-4 rounded-lg {{ $allRequiredPassed ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
        @if ($allRequiredPassed)
            <p class="text-sm text-green-800 font-medium">✓ All required checks passed. You can proceed.</p>
        @else
            <p class="text-sm text-red-800 font-medium">✗ Some required checks failed. Fix the issues above before continuing.</p>
        @endif
    </div>

    {{-- Navigation --}}
    <div class="mt-6 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                @if(!$allRequiredPassed) disabled @endif
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
            Continue →
        </button>
    </div>
</div>