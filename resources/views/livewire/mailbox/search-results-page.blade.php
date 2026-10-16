@extends('layouts.app')

@section('content')
<div x-data="{ mobileFiltersOpen: false }" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 w-full">
    {{-- Mobile Filter Drawer (lg:hidden) --}}
    <div
        x-show="mobileFiltersOpen"
        x-cloak
        class="fixed inset-0 z-50 lg:hidden"
        role="dialog"
        aria-modal="true"
        aria-label="Search Filters"
        @keydown.escape.window="mobileFiltersOpen = false"
    >
        <div
            x-show="mobileFiltersOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-end="opacity-0"
            @click="mobileFiltersOpen = false"
            class="fixed inset-0 bg-black/50 backdrop-blur-xs"
        ></div>

        <div
            x-show="mobileFiltersOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-end="translate-x-full"
            class="fixed inset-y-0 right-0 w-full max-w-xs bg-surface-raised border-l border-border shadow-2xl flex flex-col z-10"
        >
            <div class="flex items-center justify-between px-4 py-3.5 border-b border-border bg-surface-raised">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-semibold text-ink">Filters</h3>
                </div>
                <button
                    type="button"
                    @click="mobileFiltersOpen = false"
                    class="p-1.5 text-ink-tertiary hover:text-ink rounded-lg hover:bg-hover transition-colors cursor-pointer"
                    aria-label="Close filters"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="GET" action="{{ route('search') }}" id="mobile-filter-form" class="flex-1 overflow-y-auto p-4 space-y-4">
                <input type="hidden" name="q" value="{{ $query }}">

                {{-- Folder Filter --}}
                <div>
                    <label for="mobile-filter-folder" class="block text-xs font-semibold text-ink-secondary mb-1.5">Folder</label>
                    <select name="folder" id="mobile-filter-folder"
                            class="w-full text-xs border border-border rounded-lg bg-surface text-ink p-2.5 focus:border-primary focus:outline-none">
                        <option value="">All folders</option>
                        @foreach($folders as $folder)
                            <option value="{{ $folder }}" {{ ($filters['folder'] ?? '') === $folder ? 'selected' : '' }}>
                                {{ class_basename($folder) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Range --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-secondary mb-1.5">Date Range</label>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label for="mobile_date_from" class="block text-[10px] text-ink-tertiary mb-0.5">From</label>
                            <input type="date" name="date_from" id="mobile_date_from"
                                   value="{{ $filters['date_from'] ?? '' }}"
                                   class="w-full text-xs border border-border rounded-lg bg-surface text-ink p-2 focus:border-primary focus:outline-none">
                        </div>
                        <div>
                            <label for="mobile_date_to" class="block text-[10px] text-ink-tertiary mb-0.5">To</label>
                            <input type="date" name="date_to" id="mobile_date_to"
                                   value="{{ $filters['date_to'] ?? '' }}"
                                   class="w-full text-xs border border-border rounded-lg bg-surface text-ink p-2 focus:border-primary focus:outline-none">
                        </div>
                    </div>
                </div>

                {{-- Has Attachment Toggle --}}
                <div>
                    <label class="flex items-center gap-2.5 py-1 cursor-pointer">
                        <input type="checkbox" name="has_attachment" value="1"
                               {{ ($filters['has_attachment'] ?? false) ? 'checked' : '' }}
                               class="mail-checkbox w-4 h-4">
                        <span class="text-xs text-ink font-medium">Has attachment</span>
                    </label>
                </div>

                {{-- Read/Unread/Flagged --}}
                <div>
                    <label class="block text-xs font-semibold text-ink-secondary mb-1.5">Status</label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="radio" name="is_seen" value=""
                                   {{ !isset($filters['is_seen']) ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4">
                            <span class="text-xs text-ink">All</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="radio" name="is_seen" value="0"
                                   {{ (isset($filters['is_seen']) && $filters['is_seen'] === false) ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4">
                            <span class="text-xs text-ink">Unread only</span>
                        </label>
                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="radio" name="is_seen" value="1"
                                   {{ (isset($filters['is_seen']) && $filters['is_seen'] === true) ? 'checked' : '' }}
                                   class="accent-primary w-4 h-4">
                            <span class="text-xs text-ink">Read only</span>
                        </label>
                    </div>
                </div>

                {{-- Flagged --}}
                <div>
                    <label class="flex items-center gap-2.5 py-1 cursor-pointer">
                        <input type="checkbox" name="is_flagged" value="1"
                               {{ ($filters['is_flagged'] ?? false) ? 'checked' : '' }}
                               class="mail-checkbox w-4 h-4">
                        <span class="text-xs text-ink font-medium">Flagged only</span>
                    </label>
                </div>

                {{-- Labels --}}
                @if($labels->isNotEmpty())
                <div>
                    <label class="block text-xs font-semibold text-ink-secondary mb-1.5">Labels</label>
                    <div class="space-y-2 max-h-36 overflow-y-auto pr-1">
                        @foreach($labels as $label)
                            <label class="flex items-center gap-2.5 cursor-pointer">
                                <input type="checkbox" name="labels[]" value="{{ $label->id }}"
                                       {{ in_array($label->id, $filters['labels'] ?? []) ? 'checked' : '' }}
                                       class="mail-checkbox w-4 h-4">
                                <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                                <span class="text-xs text-ink truncate">{{ $label->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Actions --}}
                <div class="pt-3 border-t border-border flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2.5 px-3 text-xs font-semibold rounded-lg bg-primary text-primary-contrast shadow hover:opacity-90 transition-opacity text-center cursor-pointer">
                        Apply Filters
                    </button>
                    <a href="{{ route('search', ['q' => $query]) }}" wire:navigate class="py-2.5 px-3 text-xs font-medium rounded-lg border border-border text-ink hover:bg-hover transition-colors text-center">
                        Reset
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="flex gap-5">
        {{-- Filter Sidebar --}}
        <aside class="w-64 flex-shrink-0 hidden lg:block">
            <div class="bg-surface-raised rounded border border-border p-4 sticky top-4">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-ink mb-3">Filters</h3>

                <form method="GET" action="{{ route('search') }}" id="filter-form">
                    <input type="hidden" name="q" value="{{ $query }}">

                    {{-- Folder Filter --}}
                    <div class="mb-3.5">
                        <label for="filter-folder" class="block text-xs font-medium text-ink-secondary mb-1">Folder</label>
                        <select name="folder" id="filter-folder"
                                class="w-full text-xs border border-border rounded bg-surface-raised text-ink p-1.5 focus:border-primary focus:outline-none"
                                onchange="this.form.submit()">
                            <option value="">All folders</option>
                            @foreach($folders as $folder)
                                <option value="{{ $folder }}" {{ ($filters['folder'] ?? '') === $folder ? 'selected' : '' }}>
                                    {{ class_basename($folder) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Date Range Presets --}}
                    <div class="mb-3.5">
                        <label class="block text-xs font-medium text-ink-secondary mb-1">Date Range</label>
                        <div class="flex flex-wrap gap-1">
                            @php
                                $datePresets = [
                                    'today' => 'Today',
                                    'week' => 'Week',
                                    'month' => 'Month',
                                    'year' => 'Year',
                                ];
                                $activePreset = request('date_preset', '');
                            @endphp
                            @foreach($datePresets as $preset => $label)
                                <a href="{{ route('search', array_merge(request()->query(), ['date_preset' => $preset, 'date_from' => null, 'date_to' => null])) }}"
                                   wire:navigate
                                   class="px-2 py-0.5 text-xs rounded {{ $activePreset === $preset ? 'bg-primary-subtle text-primary font-medium' : 'bg-surface-sunken text-ink-tertiary hover:text-ink' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-1.5">
                            <div>
                                <label for="date_from" class="block text-[10px] text-ink-tertiary">From</label>
                                <input type="date" name="date_from" id="date_from"
                                       value="{{ $filters['date_from'] ?? '' }}"
                                       class="w-full text-xs border border-border rounded bg-surface-raised text-ink p-1 focus:border-primary focus:outline-none"
                                       onchange="this.form.submit()">
                            </div>
                            <div>
                                <label for="date_to" class="block text-[10px] text-ink-tertiary">To</label>
                                <input type="date" name="date_to" id="date_to"
                                       value="{{ $filters['date_to'] ?? '' }}"
                                       class="w-full text-xs border border-border rounded bg-surface-raised text-ink p-1 focus:border-primary focus:outline-none"
                                       onchange="this.form.submit()">
                            </div>
                        </div>
                    </div>

                    {{-- Has Attachment Toggle --}}
                    <div class="mb-3.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="has_attachment" value="1"
                                   {{ ($filters['has_attachment'] ?? false) ? 'checked' : '' }}
                                   onchange="this.form.submit()"
                                   class="mail-checkbox">
                            <span class="text-xs text-ink-secondary">Has attachment</span>
                        </label>
                    </div>

                    {{-- Read/Unread/Flagged --}}
                    <div class="mb-3.5">
                        <label class="block text-xs font-medium text-ink-secondary mb-1">Status</label>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value=""
                                       {{ !isset($filters['is_seen']) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="accent-primary">
                                <span class="text-xs text-ink-secondary">All</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value="0"
                                       {{ (isset($filters['is_seen']) && $filters['is_seen'] === false) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="accent-primary">
                                <span class="text-xs text-ink-secondary">Unread</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value="1"
                                       {{ (isset($filters['is_seen']) && $filters['is_seen'] === true) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="accent-primary">
                                <span class="text-xs text-ink-secondary">Read</span>
                            </label>
                        </div>
                    </div>

                    {{-- Flagged Filter --}}
                    <div class="mb-3.5">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_flagged" value="1"
                                   {{ ($filters['is_flagged'] ?? false) ? 'checked' : '' }}
                                   onchange="this.form.submit()"
                                   class="mail-checkbox">
                            <span class="text-xs text-ink-secondary">Flagged only</span>
                        </label>
                    </div>

                    {{-- Labels Multi-Select --}}
                    @if($labels->isNotEmpty())
                    <div class="mb-3.5">
                        <label class="block text-xs font-medium text-ink-secondary mb-1">Labels</label>
                        <div class="space-y-1 max-h-36 overflow-y-auto">
                            @foreach($labels as $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="labels[]" value="{{ $label->id }}"
                                           {{ in_array($label->id, $filters['labels'] ?? []) ? 'checked' : '' }}
                                           onchange="this.form.submit()"
                                           class="mail-checkbox">
                                    <span class="w-2.5 h-2.5 rounded-full flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                                    <span class="text-xs text-ink-secondary truncate">{{ $label->name }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </form>
            </div>
        </aside>

        {{-- Results List --}}
        <div class="flex-1 min-w-0">
            {{-- Active Filter Chips --}}
            @php
                $activeFilters = [];
                if (!empty($filters['folder'])) $activeFilters[] = ['key' => 'folder', 'label' => 'Folder: ' . class_basename($filters['folder']), 'params' => ['folder' => null]];
                if (!empty($filters['date_from'])) $activeFilters[] = ['key' => 'date_from', 'label' => 'From: ' . $filters['date_from'], 'params' => ['date_from' => null]];
                if (!empty($filters['date_to'])) $activeFilters[] = ['key' => 'date_to', 'label' => 'To: ' . $filters['date_to'], 'params' => ['date_to' => null]];
                if (!empty($filters['has_attachment'])) $activeFilters[] = ['key' => 'has_attachment', 'label' => 'Has attachment', 'params' => ['has_attachment' => null]];
                if (isset($filters['is_seen']) && $filters['is_seen'] === false) $activeFilters[] = ['key' => 'is_seen', 'label' => 'Unread', 'params' => ['is_seen' => null]];
                if (isset($filters['is_seen']) && $filters['is_seen'] === true) $activeFilters[] = ['key' => 'is_seen', 'label' => 'Read', 'params' => ['is_seen' => null]];
                if (!empty($filters['is_flagged'])) $activeFilters[] = ['key' => 'is_flagged', 'label' => 'Flagged', 'params' => ['is_flagged' => null]];
                if (!empty($filters['labels'])) {
                    foreach($filters['labels'] as $labelId) {
                        $label = $labels->firstWhere('id', $labelId);
                        if ($label) {
                            $activeFilters[] = ['key' => 'labels', 'label' => $label->name, 'params' => ['labels' => array_diff($filters['labels'], [$labelId])]];
                        }
                    }
                }
            @endphp

            @if(!empty($activeFilters))
                <div class="flex flex-wrap gap-1.5 mb-3">
                    @foreach($activeFilters as $filter)
                        @php
                            $remainingParams = array_merge(request()->query(), $filter['params']);
                            $remainingParams = array_filter($remainingParams, fn($v) => $v !== null);
                            if (isset($remainingParams['labels']) && is_array($remainingParams['labels'])) {
                                $remainingParams['labels'] = array_values($remainingParams['labels']);
                            }
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs bg-surface-sunken border border-border text-ink-secondary">
                            {{ $filter['label'] }}
                            <a href="{{ route('search', $remainingParams) }}" wire:navigate
                               class="text-ink-tertiary hover:text-ink" aria-label="Remove filter">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Results Header --}}
            <div class="flex items-center justify-between mb-3 gap-2">
                <h2 class="text-xs text-ink-secondary truncate">
                    {{ $results->total() }} result{{ $results->total() !== 1 ? 's' : '' }} for
                    <span class="font-semibold text-ink">"{{ $query }}"</span>
                </h2>

                <button
                    type="button"
                    @click="mobileFiltersOpen = true"
                    class="lg:hidden inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-border bg-surface-raised text-ink hover:bg-hover transition-colors shrink-0 cursor-pointer"
                    aria-label="Open search filters"
                >
                    <svg class="w-3.5 h-3.5 text-ink-secondary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    <span>Filters</span>
                    @if(!empty($activeFilters))
                        <span class="inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold rounded-full bg-primary text-primary-contrast">{{ count($activeFilters) }}</span>
                    @endif
                </button>
            </div>

            {{-- Results --}}
            @if($results->isEmpty())
                <div class="bg-surface-raised rounded border border-border p-12 text-center">
                    <svg class="w-10 h-10 mx-auto mb-3 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.25" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-sm font-semibold text-ink mb-1">No messages found</h3>
                    <p class="text-xs text-ink-tertiary max-w-sm mx-auto">
                        Try adjusting your search terms or filters. Search matches subject, sender, recipients, and message body.
                    </p>
                </div>
            @else
                <div class="bg-surface-raised rounded border border-border overflow-hidden">
                    <div class="divide-y divide-border-subtle">
                        @foreach($results as $result)
                            @php
                                $displaySender = $result->from_name ?? $result->from_address;
                                $avatarChar = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $displaySender) ?: 'M', 0, 1));
                                $avatarIdx = abs(crc32($result->from_address ?? $displaySender)) % 6;
                            @endphp
                            <a href="{{ route('message.show', ['folderPath' => $result->folder_path, 'uid' => $result->uid]) }}"
                               wire:navigate
                               class="block px-4 py-2.5 hover:bg-hover transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="w-8 h-8 rounded avatar-gradient-{{ $avatarIdx }} flex items-center justify-center font-semibold text-xs flex-shrink-0">
                                        {{ $avatarChar }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-ink truncate">{{ $displaySender }}</span>
                                            <span class="text-[11px] text-ink-tertiary flex-shrink-0 tabular-nums">{{ $result->formatted_date }}</span>
                                            @if($result->has_attachments)
                                                <svg class="w-3.5 h-3.5 text-ink-tertiary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- Subject --}}
                                        <div class="text-xs font-medium text-ink truncate mt-0.5">
                                            {!! app(\App\Services\SearchService::class)->highlightMatches(
                                                e($result->subject ?? ''),
                                                $query
                                            ) !!}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="text-[10px] text-ink-tertiary">
                                                in {{ class_basename($result->folder_path) }}
                                            </span>
                                            <span class="text-xs text-ink-tertiary truncate">
                                                {!! app(\App\Services\SearchService::class)->highlightMatches(
                                                    app(\App\Services\MessageSanitizer::class)->sanitizeText(mb_strimwidth($result->snippet ?? '', 0, 140, '...')),
                                                    $query
                                                ) !!}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    <div class="px-4 py-2.5 border-t border-border">
                        {{ $results->withQueryString()->links('pagination::tailwind') }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
