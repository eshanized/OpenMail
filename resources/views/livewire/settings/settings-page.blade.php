<div class="max-w-4xl mx-auto">
    {{-- Tab navigation --}}
    <nav class="mb-6 border-b border-gray-200 dark:border-gray-700" role="tablist" aria-label="Settings">
        @foreach($tabs as $key => $label)
            <button
                role="tab"
                wire:click="setTab('{{ $key }}')"
                id="tab-{{ $key }}"
                aria-controls="panel-{{ $key }}"
                aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                class="px-4 py-3 border-b-2 font-medium text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                    {{ $activeTab === $key
                        ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                        : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}"
            >
                {{ $label }}
            </button>
        @endforeach
    </nav>

    {{-- Tab panels --}}
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
