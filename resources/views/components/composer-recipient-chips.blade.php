{{-- Recipient Chips Component with Autocomplete --}}
{{-- Props: $field (to/cc/bcc), $value (string), $label (string), $showSuggestions (bool) --}}

@php
    $fieldName = $field;
    $chips = collect(array_filter(array_map('trim', explode(',', $value))))->map(function ($email) {
        $name = '';
        if (preg_match('/^(.+?)\s*<(.+?)>$/', $email, $matches)) {
            $name = trim($matches[1]);
            $email = trim($matches[2]);
        }
        return ['email' => $email, 'name' => $name];
    })->values()->toArray();
@endphp

<div x-data="recipientChips({
    field: '{{ $fieldName }}',
    initialChips: @js($chips),
    label: '{{ $label }}',
    showSuggestions: {{ $showSuggestions ? 'true' : 'false' }}
})"
    class="w-full relative"
    @keydown.window="handleKeydown($event)"
    @composer-commit-recipients.window="addChipFromInput()"
>
    <label for="{{ $fieldName }}-chips-input" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">{{ $label }}</label>

    {{-- Chips Display --}}
    <div
        class="flex flex-wrap items-center gap-1.5 min-h-[42px] px-3 py-1.5 rounded-xl border border-border bg-surface hover:border-border-strong focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/25 transition-all"
        x-ref="chipsContainer"
        @click="focusInput()"
        role="listbox"
        aria-label="{{ $label }} recipients"
    >
        <template x-for="(chip, index) in chips" :key="chip.email + index">
            <span class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-0.5 bg-primary/10 text-primary border border-primary/20 rounded-lg text-xs font-medium shadow-2xs" role="option" :aria-selected="false">
                <span class="font-medium text-ink" x-text="chip.name || chip.email"></span>
                <span class="text-ink-tertiary font-mono text-[11px]" x-show="chip.name" x-text="'<'+chip.email+'>'"></span>
                <button
                    type="button"
                    @click.prevent="removeChip(index)"
                    class="text-ink-tertiary hover:text-error hover:bg-error-subtle p-0.5 rounded transition-colors cursor-pointer"
                    :aria-label="'Remove ' + (chip.name || chip.email)"
                    tabindex="-1"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        </template>

        {{-- Input Field --}}
        <input
            type="text"
            :id="'{{ $fieldName }}-chips-input'"
            x-ref="input"
            x-model="inputValue"
            @input="onInput()"
            @keydown="onKeydown($event)"
            @focus="showDropdown = true; fetchSuggestions()"
            @blur="setTimeout(() => { showDropdown = false; addChipFromInput(); }, 150)"
            class="flex-1 min-w-[140px] px-1 py-1 border-0 focus:outline-none text-sm text-ink bg-transparent placeholder-ink-tertiary"
            :placeholder="chips.length === 0 ? 'name@example.com' : ''"
            autocomplete="email"
            aria-label="{{ $label }} input"
            aria-activedescendant=""
            aria-autocomplete="list"
            aria-controls="{{ $fieldName }}-suggestions"
            role="combobox"
        >
    </div>

    {{-- Autocomplete Dropdown --}}
    <div
        x-show="showDropdown && suggestions.length > 0"
        x-cloak
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 transform -translate-y-1"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-end="opacity-0"
        class="absolute z-50 mt-1 w-full max-w-md bg-surface-raised border border-border rounded-xl shadow-xl overflow-hidden backdrop-blur-md"
        :id="'{{ $fieldName }}-suggestions'"
        role="listbox"
        x-ref="dropdown"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.email + index">
            <button
                type="button"
                @click="selectSuggestion(suggestion)"
                @mousedown.prevent="focusInput()"
                class="w-full px-3 py-2 text-left text-sm hover:bg-hover transition-colors flex items-center gap-2.5 cursor-pointer"
                :class="{ 'bg-primary-subtle text-primary': selectedIndex === index }"
                :aria-selected="selectedIndex === index"
                role="option"
                :id="'{{ $fieldName }}-suggestion-' + index"
            >
                <div class="w-8 h-8 rounded-full bg-primary/10 text-primary flex items-center justify-center font-bold text-xs ring-1 ring-primary/20 shrink-0"
                    x-text="suggestion.name ? suggestion.name.charAt(0).toUpperCase() : suggestion.email.charAt(0).toUpperCase()">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-ink truncate text-xs" x-text="suggestion.name || suggestion.email"></p>
                    <p class="text-[11px] text-ink-tertiary truncate font-mono" x-show="suggestion.name" x-text="suggestion.email"></p>
                </div>
                <span class="text-[10px] text-ink-tertiary font-mono" x-text="suggestion.frequency"></span>
            </button>
        </template>

        <div x-show="suggestions.length === 0 && inputValue.length > 0" class="px-3 py-2.5 text-xs text-ink-tertiary">
            No matching contacts — press Enter to add
        </div>
    </div>

    {{-- Error Display --}}
    @error($field)
        <p class="mt-1 text-xs text-error">{{ $message }}</p>
    @enderror

    {{-- Hidden input for Livewire sync --}}
    <input type="hidden" :name="{{ $fieldName }}" wire:model="{{ $fieldName }}">

    <script>
        function recipientChips(config) {
            return {
                field: config.field,
                chips: config.initialChips || [],
                inputValue: '',
                suggestions: [],
                showDropdown: false,
                selectedIndex: -1,
                debounceTimer: null,

                init() {
                    this.$watch('chips', (value) => {
                        this.updateHiddenInput();
                    });
                    this.$watch('$wire.' + this.field, (value) => {
                        this.syncFromWire(value);
                    });
                },

                syncFromWire(value) {
                    if (!value) return;
                    const newChips = value.split(',').map(e => e.trim()).filter(Boolean).map(email => {
                        let name = '';
                        if (email.match(/^(.+?)\s*<(.+?)>$/)) {
                            const matches = email.match(/^(.+?)\s*<(.+?)>$/);
                            name = matches[1].trim();
                            email = matches[2].trim();
                        }
                        return { email, name };
                    });
                    // Only update if different to avoid infinite loop
                    const currentEmails = this.chips.map(c => c.email).join(',');
                    const newEmails = newChips.map(c => c.email).join(',');
                    if (currentEmails !== newEmails) {
                        this.chips = newChips;
                    }
                },

                updateHiddenInput() {
                    const value = this.chips.map(c => c.name ? `${c.name} <${c.email}>` : c.email).join(', ');
                    this.$wire.set(this.field, value);
                },

                onInput() {
                    clearTimeout(this.debounceTimer);
                    this.debounceTimer = setTimeout(() => {
                        this.fetchSuggestions();
                    }, 200);
                    this.showDropdown = true;
                    this.selectedIndex = -1;
                },

                async fetchSuggestions() {
                    if (this.inputValue.length < 1) {
                        this.suggestions = [];
                        return;
                    }
                    try {
                        const response = await this.$wire.searchContacts(this.inputValue);
                        this.suggestions = response || [];
                    } catch (e) {
                        console.error('Autocomplete fetch failed:', e);
                        this.suggestions = [];
                    }
                },

                onKeydown(event) {
                    if (!this.showDropdown || this.suggestions.length === 0) {
                        if (event.key === 'Enter' || event.key === ',') {
                            event.preventDefault();
                            this.addChipFromInput();
                        }
                        if (event.key === 'Backspace' && this.inputValue === '' && this.chips.length > 0) {
                            this.removeChip(this.chips.length - 1);
                        }
                        return;
                    }

                    switch (event.key) {
                        case 'ArrowDown':
                            event.preventDefault();
                            this.selectedIndex = Math.min(this.selectedIndex + 1, this.suggestions.length - 1);
                            this.scrollToSelected();
                            break;
                        case 'ArrowUp':
                            event.preventDefault();
                            this.selectedIndex = Math.max(this.selectedIndex - 1, 0);
                            this.scrollToSelected();
                            break;
                        case 'Enter':
                        case 'Tab':
                            event.preventDefault();
                            if (this.selectedIndex >= 0) {
                                this.selectSuggestion(this.suggestions[this.selectedIndex]);
                            } else {
                                this.addChipFromInput();
                            }
                            break;
                        case 'Escape':
                            this.showDropdown = false;
                            this.selectedIndex = -1;
                            break;
                        case 'Backspace':
                            if (this.inputValue === '' && this.chips.length > 0) {
                                event.preventDefault();
                                this.removeChip(this.chips.length - 1);
                            }
                            break;
                        case ',':
                            event.preventDefault();
                            this.addChipFromInput();
                            break;
                    }
                },

                scrollToSelected() {
                    const option = this.$refs.dropdown?.querySelector('[aria-selected="true"]');
                    if (option) {
                        option.scrollIntoView({ block: 'nearest' });
                    }
                },

                addChipFromInput() {
                    const value = this.inputValue.trim();
                    if (!value) return;

                    // Simple email validation
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        // Check if it's "Name <email>" format
                        const match = value.match(/^(.+?)\s*<([^>]+)>$/);
                        if (match && emailRegex.test(match[2].trim())) {
                            const email = match[2].trim();
                            const name = match[1].trim();
                            if (!this.chips.some(c => c.email.toLowerCase() === email.toLowerCase())) {
                                this.chips = [...this.chips, { email, name }];
                            }
                            this.inputValue = '';
                            this.showDropdown = false;
                            this.selectedIndex = -1;
                            this.updateHiddenInput();
                        }
                        return;
                    }

                    // Check for duplicate
                    if (this.chips.some(c => c.email.toLowerCase() === value.toLowerCase())) {
                        this.inputValue = '';
                        return;
                    }

                    this.chips = [...this.chips, { email: value, name: '' }];
                    this.inputValue = '';
                    this.showDropdown = false;
                    this.selectedIndex = -1;
                    this.updateHiddenInput();
                },

                removeChip(index) {
                    this.chips.splice(index, 1);
                    this.updateHiddenInput();
                },

                selectSuggestion(suggestion) {
                    // Check for duplicate
                    if (this.chips.some(c => c.email.toLowerCase() === suggestion.email.toLowerCase())) {
                        this.inputValue = '';
                        this.showDropdown = false;
                        return;
                    }

                    this.chips.push({ email: suggestion.email, name: suggestion.name });
                    this.inputValue = '';
                    this.showDropdown = false;
                    this.selectedIndex = -1;
                    this.updateHiddenInput();
                    this.focusInput();
                },

                focusInput() {
                    this.$refs.input?.focus();
                },

                handleKeydown(event) {
                    // Global keydown handler for chip removal with backspace
                }
            }
        }
    </script>
</div>