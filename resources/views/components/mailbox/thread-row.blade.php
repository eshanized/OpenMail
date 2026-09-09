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
    class="border-b border-border-subtle last:border-b-0
        {{ $depth > 0 ? 'bg-surface-sunken/30' : '' }}"
    style="padding-left: {{ 16 + ($depth * 16) }}px;"
    role="row"
    aria-expanded="expanded"
    tabindex="0"
    @keydown="handleKeydown($event)"
>
    <div class="flex items-center px-4 py-3 hover:bg-hover transition-colors duration-75">
        {{-- Checkbox --}}
        <input
            type="checkbox"
            @if(in_array($thread->uid, $selectedUids ?? [])) checked @endif
            @click.stop="$dispatch('toggle-uid', { uid: {{ $thread->uid }} })"
            class="w-4 h-4 text-primary border-border-strong rounded focus:ring-primary focus:ring-2"
        >

        {{-- Star --}}
        <button
            @click.stop="$dispatch('toggle-star', { uid: {{ $thread->uid }} })"
            wire:click="toggleStar({{ $thread->uid }})"
            wire:loading.attr="disabled"
            class="ml-2 p-0.5 text-ink-tertiary hover:text-warning transition-colors rounded"
            title="Toggle star"
        >
            @if($thread->is_flagged)
                <svg class="w-4 h-4 fill-current text-warning" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @else
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
            @endif
        </button>

        {{-- Clickable message link to open message --}}
        <a
            href="{{ route('message.show', ['folderPath' => $thread->folder_path ?? 'INBOX', 'uid' => $thread->uid]) }}"
            wire:navigate
            class="flex items-center min-w-0 flex-1 cursor-pointer"
        >
            {{-- Sender + Unread --}}
            <div class="flex items-center min-w-0 ml-3 flex-1">
                <span class="truncate block pr-2 text-sm {{ !$thread->is_seen ? 'font-semibold text-ink' : 'text-ink-secondary' }}">
                    {{ $thread->from_display ?? 'Unknown' }}
                </span>
                @if(!$thread->is_seen)
                    @php $unreadCount = $thread->unread_count ?? 0; @endphp
                    @if($unreadCount > 1)
                        <span class="inline-flex items-center justify-center px-1.5 py-0.5 text-[10px] font-bold text-white bg-primary rounded-full flex-shrink-0">
                            {{ $unreadCount }}
                        </span>
                    @else
                        <span class="w-1.5 h-1.5 bg-primary rounded-full flex-shrink-0"></span>
                    @endif
                @endif
            </div>

            {{-- Subject + Labels --}}
            <div class="flex items-center min-w-0 mr-4 flex-1">
                <span class="truncate block pr-2 text-sm {{ !$thread->is_seen ? 'font-medium text-ink' : 'text-ink-secondary' }}">
                    {{ Str::limit($thread->subject ?? '', 60) }}
                </span>
                @php
                    $labels = $thread->labels ?? collect();
                    $displayLabels = $labels->take(3);
                    $overflowCount = max(0, $labels->count() - 3);
                @endphp
                @foreach($displayLabels as $label)
                    <span class="label-chip inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded text-white flex-shrink-0 ml-1"
                        style="background-color: {{ $label->color ?? '#6B7280' }}"
                        title="{{ $label->name }}"
                    >
                        {{ Str::limit($label->name, 10) }}
                    </span>
                @endforeach
                @if($overflowCount > 0)
                    <span class="label-chip inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-surface-sunken text-ink-tertiary flex-shrink-0 ml-1">
                        +{{ $overflowCount }}
                    </span>
                @endif
            </div>

            {{-- Date --}}
            <span class="text-xs text-ink-tertiary whitespace-nowrap ml-2">{{ $thread->formatted_date ?? '' }}</span>
        </a>

        {{-- Attachment --}}
        @if($thread->has_attachments)
            <svg class="w-3.5 h-3.5 text-ink-tertiary ml-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
            </svg>
        @endif

        {{-- Expand chevron --}}
        <div class="ml-2 flex-shrink-0 p-1 cursor-pointer hover:bg-surface-sunken rounded" @click.stop="toggleExpand()">
            <svg
                class="w-4 h-4 text-ink-tertiary transition-transform duration-150"
                :class="{ 'rotate-90': expanded }"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                stroke-width="1.5"
            >
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
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
        class="bg-surface-sunken/20"
    >
        @if(!empty($thread->children) && count($thread->children) > 0)
            @foreach($thread->children as $child)
                <x-mailbox.thread-row :thread="$child" :depth="$depth + 1" />
            @endforeach

            @if(count($thread->children) > 10)
                <div class="px-4 py-2 text-center">
                    <button class="text-xs text-primary hover:text-primary-hover font-medium transition-colors">
                        Load {{ count($thread->children) - 10 }} more messages
                    </button>
                </div>
            @endif
        @else
            <div class="px-4 py-2 ml-8 text-xs text-ink-tertiary truncate">
                {{ Str::limit($thread->snippet ?? '', 80) }}
            </div>
        @endif
    </div>
</div>
