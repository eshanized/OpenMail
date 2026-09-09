<div class="p-8 sm:p-12">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-ink tracking-tight">Application Settings</h2>
        <p class="mt-1 text-sm text-ink-secondary">
            Configure your organization identity, domain endpoints, and timezone preferences.
        </p>
    </div>

    <div class="space-y-6 max-w-xl">
        {{-- Application Name --}}
        <div>
            <label for="app_name" class="block text-sm font-semibold text-ink">
                Application Name <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="app_name"
                       wire:model="appName"
                       class="setup-input font-medium"
                       placeholder="OpenMail"
                       required>
            </div>
            @error('appName') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Organization Name --}}
        <div>
            <label for="app_org" class="block text-sm font-semibold text-ink">
                Organization / Company Name <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="app_org"
                       wire:model="appOrg"
                       class="setup-input font-medium"
                       placeholder="Acme Corporation"
                       required>
            </div>
            <p class="mt-1.5 text-xs text-ink-tertiary">Displayed on the login screen and application headers.</p>
            @error('appOrg') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- Domain --}}
        <div>
            <label for="app_domain" class="block text-sm font-semibold text-ink">
                Mail Domain <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <input type="text"
                       id="app_domain"
                       wire:model="appDomain"
                       class="setup-input font-mono text-sm"
                       placeholder="mail.example.com"
                       required>
            </div>
            <p class="mt-1.5 text-xs text-ink-tertiary">Domain without protocol prefix, e.g. <span class="setup-code">mail.example.com</span> or <span class="setup-code">company.com</span></p>
            @error('appDomain') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>

        {{-- App URL --}}
        <div>
            <label for="app_url" class="block text-sm font-semibold text-ink">
                Application URL
            </label>
            <div class="mt-1.5">
                <input type="url"
                       id="app_url"
                       wire:model="appUrl"
                       class="setup-input font-mono text-sm"
                       placeholder="https://mail.example.com">
            </div>
            <p class="mt-1.5 text-xs text-ink-tertiary">Full public URL where OpenMail is hosted. Used for links in invitations and notification emails.</p>
        </div>

        {{-- Timezone --}}
        <div>
            <label for="app_timezone" class="block text-sm font-semibold text-ink">
                Default Timezone <span class="text-rose-500 ml-0.5">*</span>
            </label>
            <div class="mt-1.5">
                <select id="app_timezone"
                        wire:model="appTimezone"
                        class="setup-input text-sm font-medium"
                        required>
                    @foreach (timezone_identifiers_list() as $tz)
                        <option value="{{ $tz }}">{{ $tz }}</option>
                    @endforeach
                </select>
            </div>
            @error('appTimezone') <p class="mt-1.5 text-xs text-error font-medium" role="alert">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Navigation --}}
    <div class="mt-10 pt-6 border-t border-border/70 flex justify-between items-center">
        <button type="button"
                wire:click="previousStep"
                class="setup-btn-secondary">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            Back
        </button>

        <button type="button"
                wire:click="nextStep"
                wire:loading.attr="disabled"
                class="setup-btn-primary">
            <span>Continue</span>
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>
</div>
