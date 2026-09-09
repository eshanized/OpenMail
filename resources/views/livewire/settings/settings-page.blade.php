<div x-data="settingsTabs(@js(array_keys($tabs)))" x-init="init()" class="max-w-6xl mx-auto py-2">
    {{-- Top Bar / Breadcrumb & Status --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 pb-5 border-b border-border/70">
        <div>
            <a href="{{ route('mailbox') }}" wire:navigate class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-ink-tertiary hover:text-primary transition-colors group mb-2">
                <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
                Back to Mailbox
            </a>
            <h1 class="text-2xl sm:text-3xl font-bold text-ink tracking-tight">Settings & Preferences</h1>
            <p class="text-sm text-ink-secondary mt-1">Manage your identity, reading preferences, interface appearance, and account security.</p>
        </div>

        {{-- Connection Status Badge --}}
        <div class="flex items-center gap-2 self-start sm:self-center">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-surface-raised border border-border text-xs font-medium text-ink shadow-xs">
                <span class="relative flex h-2 w-2">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                </span>
                <span class="text-ink-secondary">Mailbox:</span>
                <span class="font-semibold text-ink font-mono text-[11px]">{{ auth()->user()->email ?? 'Active' }}</span>
            </div>
        </div>
    </div>

    {{-- Main Two-Column Layout --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8 items-start">
        {{-- Left: Settings Navigation Sidebar --}}
        <aside class="lg:col-span-4 xl:col-span-3 space-y-4">
            <div class="settings-card p-2 shadow-xs border border-border">
                <nav class="flex flex-col gap-1" role="tablist" aria-label="Settings Categories">
                    @php
                        $tabMeta = [
                            'profile' => [
                                'desc' => 'Identity & personal details',
                                'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>'
                            ],
                            'mail' => [
                                'desc' => 'Pagination & reply options',
                                'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>'
                            ],
                            'appearance' => [
                                'desc' => 'Theme & message density',
                                'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z"/>'
                            ],
                            'security' => [
                                'desc' => 'Active devices & sessions',
                                'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>'
                            ],
                            'signatures' => [
                                'desc' => 'Outgoing email signatures',
                                'svg' => '<path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>'
                            ],
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
                            class="flex items-center gap-3 w-full px-3.5 py-2.5 rounded-xl text-left transition-all duration-150 group cursor-pointer
                                {{ $activeTab === $key
                                    ? 'bg-primary/10 text-primary font-semibold ring-1 ring-primary/25 shadow-2xs'
                                    : 'text-ink-secondary hover:text-ink hover:bg-hover' }}"
                        >
                            <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 transition-colors
                                {{ $activeTab === $key
                                    ? 'bg-primary text-white shadow-2xs'
                                    : 'bg-surface-sunken text-ink-tertiary group-hover:text-ink' }}">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    {!! $tabMeta[$key]['svg'] ?? '' !!}
                                </svg>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium leading-tight {{ $activeTab === $key ? 'text-primary font-semibold' : 'text-ink' }}">
                                    {{ $label }}
                                </div>
                                <div class="text-[11px] text-ink-tertiary truncate mt-0.5">
                                    {{ $tabMeta[$key]['desc'] ?? '' }}
                                </div>
                            </div>
                            <svg class="w-4 h-4 shrink-0 transition-transform duration-150 {{ $activeTab === $key ? 'text-primary translate-x-0.5' : 'text-ink-tertiary/40 opacity-0 group-hover:opacity-100' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                            </svg>
                        </button>
                    @endforeach
                </nav>
            </div>

            {{-- User Identity Overview Card --}}
            <div class="settings-card p-4 shadow-xs border border-border">
                <div class="flex items-center gap-3">
                    @php
                        $userName = auth()->user()->name ?: Str::before(auth()->user()->email, '@');
                        $userInitial = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $userName) ?: 'U', 0, 1));
                        $avatarIdx = abs(crc32(auth()->user()->email ?? 'user')) % 6;
                    @endphp
                    <div class="w-10 h-10 rounded-xl avatar-gradient-{{ $avatarIdx }} flex items-center justify-center text-sm font-bold shadow-2xs shrink-0 ring-2 ring-primary/20">
                        {{ $userInitial }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-xs font-semibold text-ink truncate">{{ $userName }}</div>
                        <div class="text-[11px] text-ink-tertiary truncate font-mono mt-0.5">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-t border-border-subtle flex items-center justify-between text-[11px] text-ink-tertiary">
                    <span class="inline-flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                        Tonmoy Org
                    </span>
                    <span class="px-1.5 py-0.5 rounded bg-surface-sunken font-mono text-[10px] text-ink-secondary">Single Tenant</span>
                </div>
            </div>
        </aside>

        {{-- Right: Tab Panels Area --}}
        <main class="lg:col-span-8 xl:col-span-9">
            <div class="settings-card p-6 sm:p-8 shadow-xs border border-border">
                <div role="tabpanel" id="panel-{{ $activeTab }}" aria-labelledby="tab-{{ $activeTab }}" class="animate-fade-in">
                    {{-- Active Tab Header --}}
                    <div class="flex items-center gap-3.5 pb-6 mb-6 border-b border-border/70">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 ring-1 ring-primary/20">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                {!! $tabMeta[$activeTab]['svg'] ?? '' !!}
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-ink tracking-tight">{{ $tabs[$activeTab] ?? 'Settings' }}</h2>
                            <p class="text-xs text-ink-secondary mt-0.5">{{ $tabMeta[$activeTab]['desc'] ?? 'Configure your account preferences' }}</p>
                        </div>
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

    {{-- Toast Notifications --}}
    <div x-show="toastMessage" x-transition
         role="status" aria-live="polite" aria-atomic="true"
         class="fixed bottom-5 right-5 z-50 px-4 py-3 rounded-xl shadow-xl text-sm font-medium max-w-sm flex items-center gap-2.5 backdrop-blur-md border"
         :class="toastType === 'success' ? 'bg-emerald-500/90 border-emerald-400/40 text-white' : 'bg-red-500/90 border-red-400/40 text-white'"
         x-text="toastMessage">
    </div>
</div>
