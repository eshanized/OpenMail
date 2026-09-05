<div>
    <h3 class="text-lg font-medium text-gray-900">Welcome to OpenMail Setup</h3>
    <p class="mt-2 text-sm text-gray-600">This wizard will guide you through configuring OpenMail.</p>

    @if ($installMode === 'continue')
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm text-yellow-800">Existing installation detected. You can continue from where you left off or start fresh.</p>
        </div>
    @else
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm text-green-800">Fresh installation detected. Let's get started!</p>
        </div>
    @endif

    <div class="mt-6">
        <label for="app_name" class="block text-sm font-medium text-gray-700">Application Name</label>
        <input type="text" id="app_name" name="app_name" wire:model="appName" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm p-2 border">
        @error('appName') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="mt-4 flex justify-end">
        <button type="button" wire:click="nextStep" class="inline-flex justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            Next
        </button>
    </div>
</div>