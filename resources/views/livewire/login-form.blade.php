<div>
    @if ($error)
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
            {{ $error }}
        </div>
    @endif

    <form wire:submit.prevent="login" class="space-y-6">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
            <input id="email" name="email" type="email" autocomplete="email" wire:model="email" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" wire:model="password" required class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm">
        </div>

        <div>
            <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                Sign in
            </button>
        </div>
    </form>

    <p class="mt-4 text-center text-sm text-gray-600">
        <a href="/install" class="font-medium text-blue-600 hover:text-blue-500">Run Setup Wizard</a>
    </p>
</div>