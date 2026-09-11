<div x-data="settingsTabs(@js(array_keys($tabs)))" x-init="init()" class="max-w-5xl mx-auto py-2">
    {{-- Top Bar / Breadcrumb & Status --}}
    <div class="mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pb-3 border-b border-border">
        <div>
            <a href="{{ route('mailbox') }}" wire:navigate class="inline-flex items-center gap-1.5 text-xs text-ink-tertiary hover:text-ink transition-colors mb-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back to Mailbox
            </a>
            <h1 class="text-xl font-bold text-ink tracking-tight">Settings & Preferences</h1>
        </div>

        {{-- Account info --}}
        <div class="text-xs text-ink-tertiary">
            Signed in as <span class="font-medium text-ink">{{ auth()->user()->email }}</span>
        </div>
    </div>

    {{-- Main Two-Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        {{-- Left: Settings Navigation Sidebar --}}
        <aside class="lg:col-span-4 xl:col-span-3 space-y-3">
            <nav class="space-y-0.5" role="tablist" aria-label="Settings Categories">
                @php
                    $tabIcons = [
                        'profile' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>',
                        'mail' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>',
                        'appearance' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>',
                        'security' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>',
                        'signatures' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>',
                    ];
                @endphp

                @foreach($tabs as $key => $label)
                    <button
                        role="tab"
                        wire:click="setTab('{{ $key }}')"
                        id="tab-{{ $key }}"
                        aria-controls="panel-{{ $key }}"
                        aria-selected="{{ $activeTab === $key ? 'true' : 'false' }}"
                        tabindex="{{ $activeTab === $key ? '0' : '-1' }}"
                        x-ref="tab-{{ $key }}"
                        @keydown.arrow-down.prevent="focusNextTab('{{ $key }}')"
                        @keydown.arrow-up.prevent="focusPrevTab('{{ $key }}')"
                        @keydown.enter.prevent="selectTab('{{ $key }}')"
                        @keydown.space.prevent="selectTab('{{ $key }}')"
                        class="flex items-center gap-2.5 w-full px-3 py-2 rounded text-left transition-colors cursor-pointer text-xs
                            {{ $activeTab === $key
                                ? 'bg-primary-subtle text-primary font-medium'
                                : 'text-ink-secondary hover:text-ink hover:bg-hover' }}"
                    >
                        <svg class="w-4 h-4 shrink-0 text-ink-tertiary {{ $activeTab === $key ? 'text-primary' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            {!! $tabIcons[$key] ?? '' !!}
                        </svg>
                        <span class="flex-1">{{ $label }}</span>
                    </button>
                @endforeach
            </nav>
        </aside>

        {{-- Right: Tab Panels Area --}}
        <main class="lg:col-span-8 xl:col-span-9">
            <div class="bg-surface-raised border border-border rounded p-6">
                <div role="tabpanel" id="panel-{{ $activeTab }}" aria-labelledby="tab-{{ $activeTab }}">
                    <div class="pb-4 mb-5 border-b border-border">
                        <h2 class="text-base font-semibold text-ink">{{ $tabs[$activeTab] ?? 'Settings' }}</h2>
                    </div>

                    {{-- Dynamic Child Component --}}
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
        </main>
    </div>

    {{-- Toast Notifications — subtle confirmation --}}
    <div x-show="toastMessage" x-transition
         role="status" aria-live="polite" aria-atomic="true"
         class="fixed bottom-5 right-5 z-50 px-3 py-2 rounded shadow-md text-xs font-medium max-w-sm flex items-center gap-2 bg-ink text-white"
         x-text="toastMessage">
    </div>
</div>
