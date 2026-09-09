<div class="p-8 sm:p-10">
    <div class="mb-6">
        <h2 class="text-xl font-semibold text-ink">Application Settings</h2>
        <p class="mt-1 text-sm text-ink-secondary">Basic information about your OpenMail deployment.</p>
    </div>

    <div class="space-y-5 max-w-lg">
        {{-- Application name --}}
        <div>
            <label for="app_name" class="block text-sm font-medium text-ink-secondary">
                Application Name <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="app_name"
                   wire:model="appName"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="OpenMail"
                   required>
            @error('appName') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Organization name --}}
        <div>
            <label for="app_org" class="block text-sm font-medium text-ink-secondary">
                Organization Name <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="app_org"
                   wire:model="appOrg"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="Acme Corporation"
                   required>
            @error('appOrg') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Domain --}}
        <div>
            <label for="app_domain" class="block text-sm font-medium text-ink-secondary">
                Domain <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <input type="text"
                   id="app_domain"
                   wire:model="appDomain"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="mail.example.com"
                   required>
            <p class="mt-1 text-xs text-ink-tertiary">No protocol prefix. E.g. <code>mail.example.com</code> or <code>localhost/openmail</code></p>
            @error('appDomain') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- App URL --}}
        <div>
            <label for="app_url" class="block text-sm font-medium text-ink-secondary">Application URL</label>
            <input type="url"
                   id="app_url"
                   wire:model="appUrl"
                   class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                   placeholder="https://mail.example.com">
            <p class="mt-1 text-xs text-ink-tertiary">Full URL where OpenMail is accessible. Used for links in emails.</p>
        </div>

        {{-- Timezone --}}
        <div>
            <label for="app_timezone" class="block text-sm font-medium text-ink-secondary">
                Timezone <span class="text-danger ml-0.5" aria-hidden="true">*</span>
            </label>
            <select id="app_timezone"
                    wire:model="appTimezone"
                    class="mt-1 block w-full rounded-md border-border shadow-sm focus:border-primary focus:ring-primary sm:text-sm p-2 border"
                    required>
                @foreach (timezone_identifiers_list() as $tz)
                    <option value="{{ $tz }}">{{ $tz }}</option>
                @endforeach
            </select>
            @error('appTimezone') <p class="mt-1 text-sm text-danger" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-8 flex justify-between">
        <button type="button"
                wire:click="previousStep"
                class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2">
            ← Back
        </button>
        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="px-6 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-60">
            Continue →
        </button>
    </div>
</div>
