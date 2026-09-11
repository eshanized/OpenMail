<div x-show="open && results.length > 0"
     x-transition:enter="transition ease-out duration-100"
     x-transition:enter-start="opacity-0 transform -translate-y-1"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-75"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-1"
     class="absolute z-50 w-full mt-1 bg-surface-raised border border-border shadow-lg rounded max-h-96 overflow-auto"
     style="display: none;">

    @foreach($results as $index => $result)
        <a href="#"
           @click.prevent="openResult({{ $result->id }})"
           wire:key="result-{{ $result->id }}"
           class="block px-3 py-2.5 hover:bg-hover border-t border-border-subtle first:border-t-0 focus:bg-hover focus:outline-none"
           :class="{ 'bg-hover': {{ $index }} === highlightedIndex }"
           x-ref="resultItem">
            <div class="flex items-start gap-2.5">
                <div class="w-6 h-6 rounded bg-surface-sunken border border-border flex items-center justify-center text-ink-secondary text-xs font-semibold flex-shrink-0">
                    {{ mb_substr($result->from_name ?? $result->from_address ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-1.5 text-xs">
                        <span class="font-medium text-ink truncate">{{ $result->from_name ?? $result->from_address }}</span>
                        <span class="text-[10px] text-ink-tertiary">
                            in {{ class_basename($result->folder_path) }}
                        </span>
                        <span class="text-ink-tertiary text-[10px] ml-auto tabular-nums flex-shrink-0">{{ $result->formatted_date }}</span>
                    </div>
                    {{-- Subject --}}
                    <div class="text-xs font-medium text-ink truncate mt-0.5" x-html="highlight(@js(e($result->subject ?? '')), query)"></div>
                    {{-- Snippet --}}
                    <div class="text-xs text-ink-tertiary truncate mt-0.5" x-html="highlight(@js(e(mb_strimwidth($result->snippet ?? '', 0, 160, '...'))), query)"></div>
                </div>
            </div>
        </a>
    @endforeach

    <div class="px-3 py-2 border-t border-border bg-surface-sunken">
        <a href="{{ route('search', ['q' => $query]) }}"
           wire:navigate
           class="text-xs text-primary hover:text-primary-hover font-medium">
            View all results &rarr;
        </a>
    </div>
</div>

{{-- Empty state: query entered but no results --}}
<div x-show="open && query.length >= 2 && results.length === 0 && !loading"
     class="absolute z-50 w-full mt-1 bg-surface-raised border border-border shadow-md rounded p-3 text-center text-ink-tertiary text-xs"
     style="display: none;">
    No messages found
</div>

{{-- Loading skeleton --}}
<div x-show="open && loading"
     class="absolute z-50 w-full mt-1 bg-surface-raised border border-border shadow-md rounded overflow-hidden"
     style="display: none;">
    @foreach([1, 2, 3] as $i)
        <div class="px-3 py-2.5 border-t border-border-subtle first:border-t-0">
            <div class="flex items-start gap-2.5">
                <div class="w-6 h-6 rounded skeleton flex-shrink-0"></div>
                <div class="flex-1 min-w-0 space-y-1.5">
                    <div class="skeleton h-2.5 rounded w-28"></div>
                    <div class="skeleton h-2.5 rounded w-full"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
