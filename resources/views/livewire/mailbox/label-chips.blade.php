@props(['labels' => collect(), 'maxDisplay' => 3])

@php
    $visibleLabels = $labels->take($maxDisplay);
    $overflowCount = $labels->count() - $maxDisplay;
@endphp

@if($labels->isNotEmpty())
    <div
        class="inline-flex items-center gap-1 flex-wrap"
        x-data="{ showPopover: false }"
    >
        @foreach($visibleLabels as $label)
            <a
                href="/labels/{{ $label->id }}"
                wire:navigate
                class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full text-white hover:opacity-80 transition-opacity"
                style="background-color: {{ $label->color }}"
                title="{{ $label->name }}"
            >
                {{ $label->name }}
            </a>
        @endforeach

        @if($overflowCount > 0)
            <div class="relative" x-data="{ open: false }">
                <button
                    @click="open = !open; $event.stopPropagation()"
                    @keydown.escape="open = false"
                    class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-surface-sunken text-ink-secondary hover:bg-surface-sunken transition-colors"
                >
                    +{{ $overflowCount }}
                </button>

                {{-- Overflow popover --}}
                <div
                    x-show="open"
                    x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute left-0 mt-1 z-50 glass-card shadow-lg py-1 min-w-[120px]"
                    @click.outside="open = false"
                    @keydown.escape="open = false"
                >
                    @foreach($labels->slice($maxDisplay) as $label)
                        <a
                            href="/labels/{{ $label->id }}"
                            wire:navigate
                            class="flex items-center gap-2 px-3 py-1.5 text-sm text-ink-secondary hover:bg-surface-sunken"
                            @click="open = false"
                        >
                            <span
                                class="w-2.5 h-2.5 rounded-full flex-shrink-0"
                                style="background-color: {{ $label->color }}"
                            ></span>
                            {{ $label->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
