<div x-data="settingsTabs(@js(array_keys($tabs)))" x-init="init()" class="max-w-4xl mx-auto">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-xl font-bold text-ink">Settings</h1>
        <p class="text-sm text-ink-secondary mt-1">Manage your account preferences</p>
    </div>

    {{-- Tab navigation --}}
    <nav class="mb-6 border-b border-border" role="tablist" aria-label="Settings">
        <div class="flex gap-0 -mb-px overflow-x-auto">
            @foreach($tabs as $key => $label)
                <button
                    role="tab"
                    wire:click="setTab('{{ $key }}')"
                    id="tab-{{ $key }}"
                    aria-controls="panel-{{ $key }}"
                    aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                    tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                    x-ref="tab-{{ $key }}"
                    @keydown.arrow-right.prevent="focusNextTab('{{ $key }}')"
                    @keydown.arrow-left.prevent="focusPrevTab('{{ $key }}')"
                    @keydown.enter.prevent="selectTab('{{ $key }}')"
                    @keydown.space.prevent="selectTab('{{ $key }}')"
                    class="px-4 py-3 border-b-2 font-medium text-sm transition-colors whitespace-nowrap
                        focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary
                        {{ $activeTab === $key
                            ? 'border-primary text-primary'
                            : 'border-transparent text-ink-tertiary hover:text-ink-secondary hover:border-border' }}"
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </nav>

    {{-- Tab panels --}}
    <div class="bg-surface-raised rounded-xl border border-border p-6">
        <div role="tabpanel" id="panel-{{ $activeTab }}" aria-labelledby="tab-{{ $activeTab }}">
            @switch($activeTab)
                @case('profile')
                    @livewire('settings.profile-tab')
                    @break
                @case('mail')
                    @livewire('settings.mail-tab')
                    @break
                @case('appearance')
                    @livewire('settings.appearance-tab')
                    @break
                @case('security')
                    @livewire('settings.security-tab')
                    @break
                @case('signatures')
                    @livewire('settings.signatures-tab')
                    @break
            @endswitch
        </div>
    </div>

    {{-- Toast notifications --}}
    <div x-show="toastMessage" x-transition
         role="status" aria-live="polite" aria-atomic="true"
         class="fixed bottom-4 right-4 z-50 px-4 py-2.5 rounded-lg shadow-lg text-sm font-medium max-w-xs"
         :class="toastType === 'success' ? 'bg-success text-white' : 'bg-error text-white'"
         x-text="toastMessage">
    </div>
</div>
