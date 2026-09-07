{{-- Thread Row Component per UI-SPEC §2.1-2.2 --}}
{{-- Props: $thread (thread root object), $depth (nesting depth), $isExpanded (default false) --}}
@props(['thread', 'depth' => 0, 'isExpanded' => false])

<div
    x-data="{
        expanded: @js($isExpanded),
        toggleExpand() {
            this.expanded = !this.expanded;
        },
        handleKeydown(event) {
            if (event.key === ' ' || event.key === 'Enter') {
                event.preventDefault();
                this.toggleExpand();
            } else if (event.key === 'ArrowRight' && !this.expanded) {
                event.preventDefault();
                this.expanded = true;
            } else if (event.key === 'ArrowLeft' && this.expanded) {
                event.preventDefault();
                this.expanded = false;
            }
        }
    }"
    data-uid="{{ $thread->uid }}"
    class="thread-row border-b border-gray-100 last:border-b-0
        {{ $depth > 0 ? 'bg-gray-50/50' : '' }}"
    style="padding-left: {{ 16 + ($depth * 12) }}px;"
    role="row"
    aria-expanded="expanded"
    tabindex="0"
    @keydown="handleKeydown($event)"
>
    <div class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer
        {{ !$thread->is_seen ? 'font-semibold' : '' }}"
        @click="
            if (!$event.target.closest('input[type=checkbox]') && 
                !$event.target.closest('button') && 
                !$event.target.closest('.label-chip')) {
                toggleExpand();
            }
        "
    >
        {{-- Checkbox --}}
        <input
            type="checkbox"
            @if(in_array($thread->uid, $selectedUids ?? [])) checked @endif
            @click.stop="$dispatch('toggle-uid', { uid: {{ $thread->uid }} })"
            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
        >

        {{-- Star/Flag indicator --}}
        <button
            @click.stop="$dispatch('toggle-star', { uid: {{ $thread->uid }} })"
            wire:click="toggleStar({{ $thread->uid }})"
            wire:loading.attr="disabled"
            class="ml-2 p-1 text-gray-400 hover:text-yellow-500 transition-colors"
            title="Toggle star"
        >
            @if($thread->is_flagged)
                <svg class="w-5 h-5 fill-current text-yellow-500" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                </svg>
            @else
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                </svg>
            @endif
        </button>

        {{-- Sender + Unread Badge --}}
        <div class="flex items-center min-w-0 ml-3 flex-1">
            <span class="truncate block pr-2 {{ !$thread->is_seen ? 'font-semibold text-gray-900' : 'font-normal text-gray-700' }}">
                {{ $thread->from_display ?? 'Unknown' }}
            </span>
            @if(!$thread->is_seen)
                @php $unreadCount = $thread->unread_count ?? 0; @endphp
                @if($unreadCount > 1)
                    <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-semibold text-white bg-blue-600 rounded-full flex-shrink-0">
                        {{ $unreadCount }}
                    </span>
                @else
                    <span class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"></span>
                @endif
            @endif
        </div>

        {{-- Subject + Label Chips (max 3) --}}
        <div class="flex items-center min-w-0 mr-4 flex-1">
            <span class="truncate block pr-2 {{ !$thread->is_seen ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                {{ Str::limit($thread->subject ?? '', 60) }}
            </span>
            {{-- Label chips (max 3 + overflow) --}}
            @php
                $labels = $thread->labels ?? collect();
                $displayLabels = $labels->take(3);
                $overflowCount = max(0, $labels->count() - 3);
            @endphp
            @foreach($displayLabels as $label)
                <span class="label-chip inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full text-white flex-shrink-0 ml-1"
                    style="background-color: {{ $label->color ?? '#6B7280' }}"
                    title="{{ $label->name }}"
                >
                    {{ Str::limit($label->name, 10) }}
                </span>
            @endforeach
            @if($overflowCount > 0)
                <span class="label-chip inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600 flex-shrink-0 ml-1"
                    title="{{ $overflowCount }} more label(s)"
                >
                    +{{ $overflowCount }}
                </span>
            @endif
        </div>

        {{-- Date --}}
        <span class="text-sm text-gray-500 whitespace-nowrap ml-2">{{ $thread->formatted_date ?? '' }}</span>

        {{-- Attachment icon --}}
        @if($thread->has_attachments)
            <svg class="w-4 h-4 text-gray-500 ml-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path d="M15.828 7.828a2 2 0 112.828 2.828l-7.071 7.071a2 2 0 01-2.828 0l-7.071-7.071a2 2 0 112.828-2.828L10 12.172l5.828-5.828z"></path>
            </svg>
        @endif

        {{-- Expand/Collapse Chevron --}}
        <div class="ml-2 flex-shrink-0">
            <svg
                class="w-5 h-5 text-gray-500 transition-transform duration-150"
                :class="{ 'rotate-90': expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
            >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </div>
    </div>

    {{-- Expanded Children --}}
    <div
        x-show="expanded"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 max-h-0 overflow-hidden"
        x-transition:enter-end="opacity-100 max-h-[2000px]"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 max-h-[2000px]"
        x-transition:leave-end="opacity-0 max-h-0 overflow-hidden"
        class="bg-gray-50/30"
    >
        @if(!empty($thread->children) && count($thread->children) > 0)
            @foreach($thread->children as $child)
                <x-mailbox.thread-row :thread="$child" :depth="$depth + 1" />
            @endforeach

            {{-- Load more button if > 10 messages --}}
            @if(count($thread->children) > 10)
                <div class="px-4 py-2 text-center">
                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                        Load {{ count($thread->children) - 10 }} more
                    </button>
                </div>
            @endif
        @else
            {{-- Single message thread — show snippet --}}
            <div class="px-4 py-2 ml-8 text-sm text-gray-500 truncate">
                {{ Str::limit($thread->snippet ?? '', 80) }}
            </div>
        @endif
    </div>
</div>
