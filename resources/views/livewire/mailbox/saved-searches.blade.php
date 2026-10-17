<div class="space-y-3">
    @if($savedSearches->isEmpty())
        <div class="rounded-xl border border-dashed border-border bg-surface-raised px-5 py-12 text-center">
            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-surface-sunken text-ink-tertiary">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.2 4.2"/>
                </svg>
            </div>
            <h2 class="text-sm font-semibold text-ink">No saved searches yet</h2>
            <p class="mt-1 text-xs text-ink-tertiary">Run a search, choose Save search, and it will appear here with its filters.</p>
            <a href="{{ route('mailbox') }}" wire:navigate class="mt-3 inline-flex rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary-hover">Back to mailbox</a>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-border bg-surface-raised divide-y divide-border">
            @foreach($savedSearches as $savedSearch)
                <article wire:key="saved-search-{{ $savedSearch->id }}" class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                            <circle cx="10.8" cy="10.8" r="6.8"/><path stroke-linecap="round" stroke-linejoin="round" d="m16 16 4.2 4.2"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-ink">{{ $savedSearch->name }}</h3>
                        <p class="mt-1 truncate text-xs text-ink-secondary">Query: {{ $savedSearch->query }}</p>
                        @if($savedSearch->filters)
                            <p class="mt-1 text-xs text-ink-tertiary">
                                Filters: {{ collect($savedSearch->filters)->map(fn ($value, $key) => $key === 'labels' ? 'labels '.count((array) $value) : $key.' '.(is_bool($value) ? ($value ? 'yes' : 'no') : (is_array($value) ? count($value).' selected' : $value)))->implode(' · ') }}
                            </p>
                        @endif
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <button type="button" wire:click="runSearch({{ $savedSearch->id }})" class="rounded-lg bg-primary px-3.5 py-2 text-xs font-semibold text-white hover:bg-primary-hover">Run search</button>
                        <button type="button" wire:click="deleteSearch({{ $savedSearch->id }})" wire:confirm="Delete this saved search?" class="rounded-lg border border-border px-3 py-2 text-xs font-medium text-error hover:bg-error/5">Delete</button>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="px-1">{{ $savedSearches->links() }}</div>
    @endif
</div>
