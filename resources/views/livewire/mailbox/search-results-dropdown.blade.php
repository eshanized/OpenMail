<div x-show="open && results.length > 0"
     x-transition:enter="transition ease-out duration-100"
     x-transition:enter-start="opacity-0 transform -translate-y-1"
     x-transition:enter-end="opacity-100 transform translate-y-0"
     x-transition:leave="transition ease-in duration-75"
     x-transition:leave-start="opacity-100 transform translate-y-0"
     x-transition:leave-end="opacity-0 transform -translate-y-1"
     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-96 overflow-auto"
     style="display: none;">

    @foreach($results as $index => $result)
        <a href="#"
           @click.prevent="openResult({{ $result->id }})"
           wire:key="result-{{ $result->id }}"
           class="block px-4 py-3 hover:bg-gray-50 border-t border-gray-100 first:border-t-0 focus:bg-blue-50 focus:outline-none"
           :class="{ 'bg-blue-50': {{ $index }} === highlightedIndex }"
           x-ref="resultItem">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-medium text-sm flex-shrink-0">
                    {{ mb_substr($result->from_name ?? $result->from_address ?? '?', 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 text-sm">
                        <span class="font-medium truncate">{{ $result->from_name ?? $result->from_address }}</span>
                        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-gray-100 text-gray-600 flex-shrink-0">
                            {{ class_basename($result->folder_path) }}
                        </span>
                        <span class="text-gray-400 text-xs flex-shrink-0">{{ $result->formatted_date }}</span>
                    </div>
                    {{-- Subject: highlighted with <mark> tags after text sanitization --}}
                    <div class="text-sm font-medium text-gray-900 truncate mt-0.5" x-html="highlight(@js(e($result->subject ?? '')), query)"></div>
                    {{-- Snippet: sanitized via e() (HTML entity encoding) BEFORE highlight wrapping (SEC-01, T-04-07) --}}
                    <div class="text-sm text-gray-500 truncate mt-0.5" x-html="highlight(@js(e(mb_strimwidth($result->snippet ?? '', 0, 160, '...'))), query)"></div>
                </div>
            </div>
        </a>
    @endforeach

    <div class="px-4 py-2 border-t border-gray-100">
        <a href="{{ route('search', ['q' => $query]) }}"
           wire:navigate
           class="text-sm text-blue-600 hover:text-blue-800 font-medium">
            View all results →
        </a>
    </div>
</div>

{{-- Empty state: query entered but no results --}}
<div x-show="open && query.length >= 2 && results.length === 0 && !loading"
     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl p-4 text-center text-gray-500 text-sm"
     style="display: none;">
    No messages found
</div>

{{-- Loading skeleton --}}
<div x-show="open && loading"
     class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-lg shadow-xl overflow-hidden"
     style="display: none;">
    @foreach([1, 2, 3] as $i)
        <div class="px-4 py-3 border-t border-gray-100 first:border-t-0">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-gray-200 animate-pulse flex-shrink-0"></div>
                <div class="flex-1 min-w-0 space-y-2">
                    <div class="flex items-center gap-2">
                        <div class="h-3 bg-gray-200 rounded animate-pulse w-24"></div>
                        <div class="h-3 bg-gray-200 rounded animate-pulse w-12"></div>
                        <div class="h-3 bg-gray-200 rounded animate-pulse w-16"></div>
                    </div>
                    <div class="h-3 bg-gray-200 rounded animate-pulse w-3/4"></div>
                    <div class="h-3 bg-gray-200 rounded animate-pulse w-1/2"></div>
                </div>
            </div>
        </div>
    @endforeach
</div>
