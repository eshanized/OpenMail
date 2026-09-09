<div x-show="open && results.length > 0"
     x-transition:enter="transition ease-out duration-100"
     x-transition:enter-start="opacity-0 transform -translate-y-1"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-75"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-1"
     class="absolute z-50 w-full mt-1 glass-card backdrop-blur-sm shadow-xl max-h-96 overflow-auto"
     style="display: none;">

    @foreach($results as $index => $result)
        <a href="#"
           @click.prevent="openResult({{ $result->id }})"
           wire:key="result-{{ $result->id }}"
           class="block px-4 py-3 hover:bg-surface-sunken border-t border-border-subtle first:border-t-0 focus:bg-primary-subtle focus:outline-none"
           :class="{ 'bg-primary-subtle': {{ $index }} === highlightedIndex }"
           x-ref="resultItem">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-primary-subtle flex items-center justify-center text-primary font-medium text-sm flex-shrink-0">
                    {{ mb_substr($result->from_name ?? $result->from_address ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium truncate">{{ $result->from_name ?? $result->from_address }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-surface-sunken text-ink-secondary flex-shrink-0">
                            {{ class_basename($result->folder_path) }}
                        </span>
                        <span class="text-ink-tertiary text-xs flex-shrink-0">{{ $result->formatted_date }}</span>
                    </div>
                    {{-- Subject: highlighted with <mark> tags after text sanitization --}}
                    <div class="text-sm font-medium text-ink truncate mt-0.5" x-html="highlight(@js(e($result->subject ?? '')), query)"></div>
                    {{-- Snippet: sanitized via e() (HTML entity encoding) BEFORE highlight wrapping (SEC-01, T-04-07) --}}
                    <div class="text-sm text-ink-tertiary truncate mt-0.5" x-html="highlight(@js(e(mb_strimwidth($result->snippet ?? '', 0, 160, '...'))), query)"></div>
                </div>
            </div>
        </a>
    @endforeach

    <div class="px-4 py-2 border-t border-border-subtle">
        <a href="{{ route('search', ['q' => $query]) }}"
           wire:navigate
           class="text-sm text-primary hover:text-primary font-medium">
            View all results →
        </a>
    </div>
</div>

{{-- Empty state: query entered but no results --}}
<div x-show="open && query.length >= 2 && results.length === 0 && !loading"
     class="absolute z-50 w-full mt-1 glass-card shadow-xl p-4 text-center text-ink-tertiary text-sm"
     style="display: none;">
    No messages found
</div>

{{-- Loading skeleton --}}
<div x-show="open && loading"
     class="absolute z-50 w-full mt-1 glass-card shadow-xl overflow-hidden"
     style="display: none;">
    @foreach([1, 2, 3] as $i)
        <div class="px-4 py-3 border-t border-border-subtle dark:border-white/5 first:border-t-0">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full animate-shimmer"
                     style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="h-3 bg-surface-sunken rounded animate-shimmer w-24"
                             style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                        <div class="h-3 bg-surface-sunken rounded animate-shimmer w-12"
                             style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                        <div class="h-3 bg-surface-sunken rounded animate-shimmer w-16"
                             style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                    </div>
                    <div class="h-3 bg-surface-sunken rounded animate-shimmer w-3/4"
                         style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.08) 50%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                    <div class="h-3 bg-surface-sunken rounded animate-shimmer w-1/2"
                         style="background: linear-gradient(90deg, rgba(0,0,0,0.04) 25%, rgba(0,0,0,0.04) 75%); background-size: 200% 100%;"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
