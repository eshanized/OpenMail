<div class="relative">
    @if(!$showForm)
        <button
            type="button"
            wire:click="showSaveForm"
            class="inline-flex items-center gap-1.5 rounded-lg border border-border bg-surface-raised px-3 py-1.5 text-xs font-medium text-ink-secondary transition-colors hover:bg-hover hover:text-ink"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 4.5h12l2.5 2.5v12A1.5 1.5 0 0118 20.5H6A1.5 1.5 0 014.5 19V6A1.5 1.5 0 016 4.5zM8 4.5v5h8v-5M8 20.5v-6h8v6"/>
            </svg>
            Save search
        </button>
    @else
        <form wire:submit="save" class="flex min-w-[260px] items-center gap-2 rounded-lg border border-border bg-surface-raised p-2 shadow-lg">
            <label class="sr-only" for="saved-search-name">Search name</label>
            <input
                id="saved-search-name"
                wire:model="name"
                required
                maxlength="80"
                autofocus
                placeholder="Name this search…"
                class="min-w-0 flex-1 rounded-md border border-border bg-surface px-2.5 py-1.5 text-xs text-ink focus:border-primary focus:outline-none focus:ring-1 focus:ring-primary"
            >
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-md bg-primary px-2.5 py-1.5 text-xs font-semibold text-white disabled:opacity-50">Save</button>
            <button type="button" wire:click="$set('showForm', false)" class="rounded-md border border-border px-2 py-1.5 text-xs text-ink-secondary hover:bg-hover" aria-label="Cancel saving search">Cancel</button>
        </form>
        @error('name') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
        @error('query') <p class="mt-1 text-xs text-error">{{ $message }}</p> @enderror
    @endif
</div>
