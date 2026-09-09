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
        return text.replace(regex, '<mark class=\'bg-warning/20 text-warning px-0.5 rounded\'>$1</mark>');
    },

    close() {
        this.open = false;
        this.highlightedIndex = -1;
    }
}" x-init="
    window.addEventListener('keydown', (e) => {
        if (e.key === '/' && !e.target.closest('input, textarea, select, [contenteditable]')) {
            e.preventDefault();
            $refs.searchInput.focus();
        }
    });
    $wire.on('focus-search-input', () => {
        $refs.searchInput.focus();
    });
">
    <label for="search-input" class="sr-only">Search all mail</label>
    <div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
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
               class="w-full pl-9 pr-9 py-2 bg-surface border border-border rounded-lg text-sm text-ink placeholder-ink-tertiary
                      focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
               placeholder="Search all mail... (/)"
               autocomplete="off">
        <span x-show="loading" x-cloak x-transition
              class="absolute right-3 top-1/2 -translate-y-1/2 w-4 h-4 border-2 border-primary border-t-transparent rounded-full spin-animation"></span>
    </div>

    @include('livewire.mailbox.search-results-dropdown')
</div>
