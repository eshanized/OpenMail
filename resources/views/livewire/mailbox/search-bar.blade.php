<div class="relative" x-data="{
    query: @entangle('query'),
    results: @entangle('results'),
    loading: @entangle('loading'),
    open: @entangle('open'),
    highlightedIndex: @entangle('highlightedIndex'),
    debounceTimer: null,
    
    debouncedSearch() {
        clearTimeout(this.debounceTimer);
        this.loading = true;
        this.debounceTimer = setTimeout(() => {
            @this.call('instantSearch');
        }, 300);
    },
    
    focusFirstResult() {
        if (this.results.length > 0) {
            this.highlightedIndex = 0;
            this.$refs.resultItem?.[0]?.focus();
        }
    },
    
    navigateResults(direction) {
        if (direction === 'down') {
            this.highlightedIndex = Math.min(this.highlightedIndex + 1, this.results.length - 1);
        } else {
            this.highlightedIndex = Math.max(this.highlightedIndex - 1, 0);
        }
        this.$refs.resultItem?.[this.highlightedIndex]?.focus();
    },
    
    openResult(id) {
        @this.call('openResult', id);
    },
    
    highlight(text, query) {
        if (!query || !text) return text || '';
        const escaped = query.replace(/[.*+?^${}()|[\]\\]/g, '\\\\$&');
        const regex = new RegExp('(' + escaped + ')', 'gi');
        return text.replace(regex, '<mark class=\"bg-yellow-100 text-yellow-900 px-0.5 rounded\">$1</mark>');
    },
    
    close() {
        this.open = false;
        this.highlightedIndex = -1;
    }
}" x-init="
    // '/' shortcut focuses search input
    window.addEventListener('keydown', (e) => {
        if (e.key === '/' && !e.target.closest('input, textarea, select, [contenteditable]')) {
            e.preventDefault();
            $refs.searchInput.focus();
        }
    });
    
    // Listen for focus-search-input event from Livewire
    $wire.on('focus-search-input', () => {
        $refs.searchInput.focus();
    });
">
    <label for="search-input" class="sr-only">Search all mail</label>
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text"
               id="search-input"
               x-ref="searchInput"
               x-model="query"
               @input.debounce.300ms="debouncedSearch()"
               @keydown.escape="close()"
               @keydown.arrow-down.prevent="open && highlightedIndex < results.length - 1 ? navigateResults('down') : focusFirstResult()"
               @keydown.arrow-up.prevent="navigateResults('up')"
               @keydown.enter.prevent="highlightedIndex >= 0 ? openResult(results[highlightedIndex].id) : null"
               @focus="query.length >= 2 && results.length > 0 && (open = true)"
               @click.outside="close()"
               wire:model="query"
               class="w-full pl-10 pr-10 py-2 bg-white border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
               placeholder="Search all mail (shortcut: /)"
               autocomplete="off">
        <span x-show="loading" x-transition class="absolute right-3 top-1/2 -translate-y-1/2 w-5 h-5 border-2 border-blue-500 border-t-transparent rounded-full animate-spin"></span>
    </div>

    {{-- Instant search dropdown --}}
    @include('livewire.mailbox.search-results-dropdown')
</div>
