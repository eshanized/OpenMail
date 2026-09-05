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
    class="w-full"
    @keydown.window="handleKeydown($event)"
>
    <label for="{{ $fieldName }}-chips" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>

    {{-- Chips Display --}}
    <div
        class="flex flex-wrap gap-1.5 min-h-[42px] p-2 border border-gray-300 rounded-md bg-white focus-within:ring-2 focus-within:ring-blue-500 focus-within:border-transparent"
        x-ref="chipsContainer"
        @click="focusInput()"
        role="listbox"
        aria-label="{{ $label }} recipients"
    >
        <template x-for="(chip, index) in chips" :key="chip.email + index">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-blue-50 border border-blue-200 rounded-full text-sm" role="option" :aria-selected="false">
                <span class="font-medium text-gray-900" x-text="chip.name || chip.email"></span>
                <span class="text-gray-500" x-show="chip.name" x-text="'<'+chip.email+'>'"></span>
                <button
                    type="button"
                    @click.prevent="removeChip(index)"
                    class="text-gray-400 hover:text-red-600 p-0.5 rounded hover:bg-gray-200 transition-colors"
                    :aria-label="'Remove ' + (chip.name || chip.email)"
                    tabindex="-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </span>
        </template>

        {{-- Input Field --}}
        <input
            type="text"
            :id="'{{ $fieldName }}-chips'-input"
            x-ref="input"
            x-model="inputValue"
            @input="onInput()"
            @keydown="onKeydown($event)"
            @focus="showDropdown = true; fetchSuggestions()"
            @blur.debounce.200="showDropdown = false"
            class="flex-1 min-w-[120px] px-2 py-1 border-0 focus:outline-none text-sm bg-transparent"
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
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 transform -translate-y-1"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-end="opacity-0"
        class="absolute z-50 mt-1 w-full max-w-md bg-white border border-gray-200 rounded-md shadow-lg overflow-hidden"
        :id="'{{ $fieldName }}-suggestions'"
        role="listbox"
        x-ref="dropdown"
    >
        <template x-for="(suggestion, index) in suggestions" :key="suggestion.email + index">
            <button
                type="button"
                @click="selectSuggestion(suggestion)"
                @mousedown.prevent="focusInput()"
                class="w-full px-3 py-2 text-left text-sm hover:bg-gray-50 transition-colors flex items-center gap-2"
                :class="{ 'bg-blue-50': selectedIndex === index }"
                :aria-selected="selectedIndex === index"
                role="option"
                :id="'{{ $fieldName }}-suggestion-' + index"
            >
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium text-sm"
                    x-text="suggestion.name ? suggestion.name.charAt(0).toUpperCase() : suggestion.email.charAt(0).toUpperCase()">
                </div>
                <div class="flex-1 min-w-0">
                    <p class="font-medium text-gray-900 truncate" x-text="suggestion.name || suggestion.email"></p>
                    <p class="text-xs text-gray-500 truncate" x-show="suggestion.name" x-text="suggestion.email"></p>
                </div>
                <span class="text-xs text-gray-400" x-text="suggestion.frequency"></span>
            </button>
        </template>

        <div x-show="suggestions.length === 0 && inputValue.length > 0" class="px-3 py-2 text-sm text-gray-500">
            No matching contacts — type to add manually
        </div>
    </div>

    {{-- Error Display --}}
    @error($field)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
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
                            this.chips.push({ email: match[2].trim(), name: match[1].trim() });
                        }
                        return;
                    }

                    // Check for duplicate
                    if (this.chips.some(c => c.email.toLowerCase() === value.toLowerCase())) {
                        this.inputValue = '';
                        return;
                    }

                    this.chips.push({ email: value, name: '' });
                    this.inputValue = '';
                    this.showDropdown = false;
                    this.selectedIndex = -1;
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