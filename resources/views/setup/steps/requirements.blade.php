<div>
    <h3 class="text-lg font-medium text-gray-900">System Requirements</h3>
    <p class="mt-2 text-sm text-gray-600">Checking if your server meets the requirements.</p>

    <div class="mt-4 space-y-3">
        <!-- PHP Version -->
        <div class="flex items-center justify-between p-3 border rounded-md">
            <div>
                <p class="text-sm font-medium text-gray-700">{{ $requirements['php_version']['label'] }}</p>
                <p class="text-xs text-gray-500">Required: {{ $requirements['php_version']['required'] }} | Current: {{ $requirements['php_version']['current'] }}</p>
            </div>
            <span :class="$requirements['php_version']['passed'] ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                {{ $requirements['php_version']['passed'] ? '✓ Passed' : '✗ Failed' }}
            </span>
        </div>

        <!-- Extensions -->
        @foreach ($requirements['extensions'] as $ext)
            <div class="flex items-center justify-between p-3 border rounded-md">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $ext['label'] }}</p>
                </div>
                <span :class="$ext['passed'] ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                    {{ $ext['passed'] ? '✓ Passed' : '✗ Failed' }}
                </span>
            </div>
        @endforeach

        <!-- Writable Directories -->
        @foreach ($requirements['writable'] as $path)
            <div class="flex items-center justify-between p-3 border rounded-md">
                <div>
                    <p class="text-sm font-medium text-gray-700">{{ $path['label'] }}</p>
                </div>
                <span :class="$path['passed'] ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                    {{ $path['passed'] ? '✓ Passed' : '✗ Failed' }}
                </span>
            </div>
        @endforeach

        <!-- Database Driver -->
        <div class="flex items-center justify-between p-3 border rounded-md">
            <div>
                <p class="text-sm font-medium text-gray-700">{{ $requirements['database']['label'] }}</p>
            </div>
            <span :class="$requirements['database']['passed'] ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium">
                {{ $requirements['database']['passed'] ? '✓ Passed' : '✗ Failed' }}
            </span>
        </div>
    </div>

    <div class="mt-4 flex justify-between">
        <button type="button" wire:click="previousStep" class="inline-flex justify-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Previous
        </button>
        <button type="button" wire:click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Next
        </button>
    </div>
</div>