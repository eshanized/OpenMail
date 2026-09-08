<div x-data="settingsTabs()" x-init="init()" class="max-w-4xl mx-auto">
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
                x-ref="tab-{{ $key }}"
                @keydown.arrow-right.prevent="focusNextTab('{{ $key }}')"
                @keydown.arrow-left.prevent="focusPrevTab('{{ $key }}')"
                @keydown.enter.prevent="selectTab('{{ $key }}')"
                @keydown.space.prevent="selectTab('{{ $key }}')"
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

    {{-- Live region for toast notifications --}}
    <div x-show="toastMessage" x-transition
         role="status" aria-live="polite" aria-atomic="true"
         class="fixed bottom-4 right-4 z-50 px-4 py-2 rounded-lg shadow-lg text-sm font-medium"
         :class="toastType === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
         x-text="toastMessage">
    </div>
</div>

<script>
    function settingsTabs() {
        return {
            toastMessage: '',
            toastType: 'success',
            tabs: @js(array_keys($tabs)),

            init() {
                // Listen for toast events from child components
                this.$wire.on('toast', (message, type) => {
                    this.showToast(message, type || 'success');
                });
            },

            showToast(message, type = 'success') {
                this.toastMessage = message;
                this.toastType = type;
                setTimeout(() => { this.toastMessage = ''; }, 3000);
            },

            focusNextTab(currentKey) {
                const idx = this.tabs.indexOf(currentKey);
                const next = this.tabs[(idx + 1) % this.tabs.length];
                this.$nextTick(() => {
                    const el = document.getElementById('tab-' + next);
                    if (el) el.focus();
                });
            },

            focusPrevTab(currentKey) {
                const idx = this.tabs.indexOf(currentKey);
                const prev = this.tabs[(idx - 1 + this.tabs.length) % this.tabs.length];
                this.$nextTick(() => {
                    const el = document.getElementById('tab-' + prev);
                    if (el) el.focus();
                });
            },

            selectTab(key) {
                this.$wire.setTab(key);
            }
        }
    }
</script>
