<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div class="flex gap-6">
        {{-- Filter Sidebar --}}
        <aside class="w-72 flex-shrink-0 hidden lg:block">
            <div class="bg-white rounded-lg border border-border p-4 sticky top-6">
                <h3 class="text-sm font-semibold text-ink mb-4">Filters</h3>

                <form method="GET" action="{{ route('search') }}" id="filter-form">
                    <input type="hidden" name="q" value="{{ $query }}">

                    {{-- Folder Filter --}}
                    <div class="mb-4">
                        <label for="filter-folder" class="block text-xs font-medium text-ink-secondary mb-1">Folder</label>
                        <select name="folder" id="filter-folder"
                                class="w-full text-sm border-border rounded-md focus:ring-primary focus:border-primary"
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
                    <div class="mb-4">
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
                                   class="px-2 py-1 text-xs rounded {{ $activePreset === $preset ? 'bg-primary-subtle text-primary font-medium' : 'bg-surface-sunken text-ink-secondary hover:bg-surface-sunken' }}">
                                    {{ $label }}
                                </a>
                            @endforeach
                        </div>
                        <div class="mt-2 grid grid-cols-2 gap-2">
                            <div>
                                <label for="date_from" class="block text-xs text-ink-tertiary">From</label>
                                <input type="date" name="date_from" id="date_from"
                                       value="{{ $filters['date_from'] ?? '' }}"
                                       class="w-full text-xs border-border rounded focus:ring-primary focus:border-primary"
                                       onchange="this.form.submit()">
                            </div>
                            <div>
                                <label for="date_to" class="block text-xs text-ink-tertiary">To</label>
                                <input type="date" name="date_to" id="date_to"
                                       value="{{ $filters['date_to'] ?? '' }}"
                                       class="w-full text-xs border-border rounded focus:ring-primary focus:border-primary"
                                       onchange="this.form.submit()">
                            </div>
                        </div>
                    </div>

                    {{-- Has Attachment Toggle --}}
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="has_attachment" value="1"
                                   {{ ($filters['has_attachment'] ?? false) ? 'checked' : '' }}
                                   onchange="this.form.submit()"
                                   class="w-4 h-4 text-primary border-border rounded focus:ring-primary">
                            <span class="text-sm text-ink-secondary">Has attachment</span>
                        </label>
                    </div>

                    {{-- Read/Unread/Flagged --}}
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-ink-secondary mb-1">Status</label>
                        <div class="space-y-1">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value=""
                                       {{ !isset($filters['is_seen']) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="w-4 h-4 text-primary border-border focus:ring-primary">
                                <span class="text-sm text-ink-secondary">All</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value="0"
                                       {{ (isset($filters['is_seen']) && $filters['is_seen'] === false) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="w-4 h-4 text-primary border-border focus:ring-primary">
                                <span class="text-sm text-ink-secondary">Unread</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="radio" name="is_seen" value="1"
                                       {{ (isset($filters['is_seen']) && $filters['is_seen'] === true) ? 'checked' : '' }}
                                       onchange="this.form.submit()"
                                       class="w-4 h-4 text-primary border-border focus:ring-primary">
                                <span class="text-sm text-ink-secondary">Read</span>
                            </label>
                        </div>
                    </div>

                    {{-- Flagged Filter --}}
                    <div class="mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_flagged" value="1"
                                   {{ ($filters['is_flagged'] ?? false) ? 'checked' : '' }}
                                   onchange="this.form.submit()"
                                   class="w-4 h-4 text-primary border-border rounded focus:ring-primary">
                            <span class="text-sm text-ink-secondary">Flagged only</span>
                        </label>
                    </div>

                    {{-- Labels Multi-Select --}}
                    @if($labels->isNotEmpty())
                    <div class="mb-4">
                        <label class="block text-xs font-medium text-ink-secondary mb-1">Labels</label>
                        <div class="space-y-1 max-h-40 overflow-y-auto">
                            @foreach($labels as $label)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="labels[]" value="{{ $label->id }}"
                                           {{ in_array($label->id, $filters['labels'] ?? []) ? 'checked' : '' }}
                                           onchange="this.form.submit()"
                                           class="w-4 h-4 text-primary border-border rounded focus:ring-primary">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: {{ $label->color }}"></span>
                                    <span class="text-sm text-ink-secondary truncate">{{ $label->name }}</span>
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
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach($activeFilters as $filter)
                        @php
                            $remainingParams = array_merge(request()->query(), $filter['params']);
                            // Remove null values
                            $remainingParams = array_filter($remainingParams, fn($v) => $v !== null);
                            // Re-index labels array
                            if (isset($remainingParams['labels']) && is_array($remainingParams['labels'])) {
                                $remainingParams['labels'] = array_values($remainingParams['labels']);
                            }
                        @endphp
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-surface-sunken text-ink font-medium">
                            {{ $filter['label'] }}
                            <a href="{{ route('search', $remainingParams) }}" wire:navigate
                               class="ml-0.5 text-ink-tertiary hover:text-ink-secondary" aria-label="Remove filter">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </a>
                        </span>
                    @endforeach
                </div>
            @endif

            {{-- Results Header --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm text-ink-secondary">
                    {{ $results->total() }} result{{ $results->total() !== 1 ? 's' : '' }} for
                    <span class="font-medium text-ink">"{{ $query }}"</span>
                </h2>
            </div>

            {{-- Results --}}
            @if($results->isEmpty())
                {{-- Empty State --}}
                <div class="bg-white rounded-lg border border-border p-12 text-center">
                    <svg class="w-16 h-16 mx-auto mb-4 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <h3 class="text-lg font-medium text-ink mb-2">No messages found</h3>
                    <p class="text-sm text-ink-tertiary max-w-md mx-auto">
                        Try adjusting your search terms or filters. Search looks through subject, sender, recipients, and message body.
                    </p>
                </div>
            @else
                {{-- Results List --}}
                <div class="bg-white rounded-lg border border-border overflow-hidden">
                    <div class="divide-y divide-border">
                        @foreach($results as $result)
                            <a href="{{ route('message.show', ['folderPath' => $result->folder_path, 'uid' => $result->uid]) }}"
                               wire:navigate
                               class="block px-4 py-3 hover:bg-surface-sunken transition-colors">
                                <div class="flex items-start gap-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-subtle flex items-center justify-center text-primary font-medium text-sm flex-shrink-0">
                                        {{ mb_substr($result->from_name ?? $result->from_address ?? '?', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-ink truncate">{{ $result->from_name ?? $result->from_address }}</span>
                                            <span class="text-xs text-ink-tertiary flex-shrink-0">{{ $result->formatted_date }}</span>
                                            @if($result->has_attachments)
                                                <svg class="w-4 h-4 text-ink-tertiary flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                                </svg>
                                            @endif
                                        </div>
                                        {{-- Subject: sanitized via e() (HTML entity encoding) THEN highlighted (SEC-01, T-04-07) --}}
                                        <div class="text-sm font-medium text-ink truncate mt-0.5">
                                            {!! app(\App\Services\SearchService::class)->highlightMatches(
                                                e($result->subject ?? ''),
                                                $query
                                            ) !!}
                                        </div>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium rounded bg-surface-sunken text-ink-secondary">
                                                {{ class_basename($result->folder_path) }}
                                            </span>
                                            {{-- Snippet: sanitized via MessageSanitizer THEN highlighted (SEC-01, T-04-07) --}}
                                            <span class="text-sm text-ink-tertiary truncate">
                                                {!! app(\App\Services\SearchService::class)->highlightMatches(
                                                    app(\App\Services\MessageSanitizer::class)->sanitizeText(mb_strimwidth($result->snippet ?? '', 0, 160, '...')),
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
                    <div class="px-4 py-3 border-t border-border-subtle">
                        {{ $results->withQueryString()->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
