@props(['thread', 'depth' => 0, 'isExpanded' => false, 'selectedUids' => []])

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
    class="border-b border-border-subtle last:border-b-0 message-row-card transition-colors duration-75
        {{ $depth > 0 ? 'bg-surface-sunken/40' : '' }}
        {{ !$thread->is_seen ? 'message-row-unread bg-surface-raised font-medium' : '' }}"
    style="padding-left: {{ 12 + ($depth * 16) }}px;"
    role="row"
    aria-expanded="expanded"
    tabindex="0"
    @keydown="handleKeydown($event)"
>
    @php
        $displaySender = !empty($thread->from_display) ? $thread->from_display : (!empty($thread->from_name) ? $thread->from_name : (!empty($thread->from_address) ? $thread->from_address : 'Unknown'));
        $cleanLetter = preg_replace('/[^a-zA-Z0-9]/', '', $displaySender);
        $avatarChar = !empty($cleanLetter) ? strtoupper(substr($cleanLetter, 0, 1)) : strtoupper(substr($displaySender, 0, 1));
        if ($avatarChar === '') {
            $avatarChar = 'M';
        }
        $avatarIdx = abs(crc32($displaySender)) % 6;
        $isStarred = (bool) ($thread->is_flagged ?? false);
    @endphp
    <div class="flex items-center px-3 py-2.5 hover:bg-hover transition-colors duration-75 group gap-2.5">
        {{-- Selection and Star --}}
        <div class="flex items-center gap-1.5 shrink-0">
            {{-- Checkbox --}}
            <input
                type="checkbox"
                @if(in_array($thread->uid, $selectedUids ?? [])) checked @endif
                @click.stop="$dispatch('toggle-uid', { uid: {{ $thread->uid }} })"
                class="mail-checkbox"
                aria-label="Select conversation from {{ $displaySender }}"
            >

            {{-- Star button --}}
            <button
                @click.stop="$dispatch('toggle-star', { uid: {{ $thread->uid }} })"
                wire:click="toggleStar({{ $thread->uid }})"
                wire:loading.attr="disabled"
                class="p-1 rounded transition-all duration-150 star-btn shrink-0 cursor-pointer {{ $isStarred ? 'text-amber-400' : 'text-ink-tertiary/70 hover:text-amber-400' }}"
                title="Toggle star"
            >
                @if($isStarred)
                    <svg class="w-4 h-4 fill-amber-400 text-amber-400 drop-shadow-xs" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                    </svg>
                @else
                    <svg class="w-4 h-4 fill-none stroke-currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                    </svg>
                @endif
            </button>
        </div>

        {{-- Sender Avatar & Name --}}
        <div class="flex items-center min-w-0 w-36 sm:w-52 shrink-0">
            <div class="w-7 h-7 rounded-lg avatar-gradient-{{ $avatarIdx }} flex items-center justify-center text-xs font-bold shadow-2xs shrink-0 mr-2.5">
                {{ $avatarChar }}
            </div>
            <div class="flex items-center min-w-0 truncate">
                <span class="truncate text-sm {{ !$thread->is_seen ? 'font-bold text-ink' : 'font-medium text-ink-secondary' }}">
                    {{ $displaySender }}
                </span>
                @if(!$thread->is_seen)
                    @php $unreadCount = $thread->unreadCount ?? ($thread->unread_count ?? 0); @endphp
                    @if($unreadCount > 1)
                        <span class="ml-1.5 inline-flex items-center justify-center px-1.5 py-0.2 text-[10px] font-bold text-white bg-primary rounded-full shrink-0 shadow-2xs">
                            {{ $unreadCount }}
                        </span>
                    @else
                        <span class="ml-1.5 w-2 h-2 bg-primary rounded-full shrink-0 shadow-2xs ring-2 ring-primary/20"></span>
                    @endif
                @endif
            </div>
        </div>

        {{-- Clickable message link to open message --}}
        <a
            href="{{ route('message.show', ['folderPath' => $thread->folder_path ?? 'INBOX', 'uid' => $thread->uid]) }}"
            wire:navigate
            class="flex items-center min-w-0 flex-1 cursor-pointer"
        >
            {{-- Subject + Snippet preview + Labels --}}
            <div class="flex items-center min-w-0 flex-1 pr-2">
                <span class="text-sm truncate shrink-0 max-w-[65%] sm:max-w-[55%] {{ !$thread->is_seen ? 'font-semibold text-ink' : 'font-normal text-ink/90' }}">
                    {{ $thread->subject ?: '(no subject)' }}
                </span>
                @if(!empty($thread->snippet))
                    <span class="text-xs text-ink-tertiary truncate ml-2 font-normal hidden sm:inline">
                        — {{ Str::limit($thread->snippet, 80) }}
                    </span>
                @endif

                {{-- Labels --}}
                @php
                    $rawLabels = $thread->labels ?? null;
                    $labels = ($rawLabels instanceof \Illuminate\Support\Collection) ? $rawLabels : collect();
                    $displayLabels = $labels->take(2);
                    $overflowCount = max(0, $labels->count() - 2);
                @endphp
                @foreach($displayLabels as $label)
                    <span class="label-chip inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded text-white shrink-0 ml-1.5 shadow-2xs"
                        style="background-color: {{ $label->color ?? '#6B7280' }}"
                        title="{{ $label->name }}"
                    >
                        {{ Str::limit($label->name, 12) }}
                    </span>
                @endforeach
                @if($overflowCount > 0)
                    <span class="label-chip inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium rounded bg-surface-sunken text-ink-tertiary shrink-0 ml-1">
                        +{{ $overflowCount }}
                    </span>
                @endif

                {{-- Attachment clip --}}
                @if($thread->has_attachments)
                    <span class="text-ink-tertiary ml-2 shrink-0 p-0.5" title="Has attachments">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                        </svg>
                    </span>
                @endif
            </div>
        </a>

        {{-- Right: Formatted Date & Hover Actions --}}
        <div class="relative flex items-center justify-end w-28 sm:w-36 shrink-0 pl-1">
            {{-- Date display (Crossfades on hover) --}}
            <span class="row-date-display text-xs {{ !$thread->is_seen ? 'font-semibold text-primary' : 'text-ink-tertiary' }} whitespace-nowrap font-mono text-[11px]">
                {{ $thread->formatted_date ?? '' }}
            </span>

            {{-- Hover quick action buttons --}}
            <div class="row-quick-actions absolute right-0 flex items-center gap-1 bg-surface-raised/95 backdrop-blur-xs px-1 py-0.5 rounded-lg border border-border-subtle shadow-xs">
                <button
                    type="button"
                    wire:click.stop="quickArchive({{ $thread->uid }})"
                    class="p-1 rounded text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                    title="Archive"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                    </svg>
                </button>
                <button
                    type="button"
                    wire:click.stop="quickDelete({{ $thread->uid }})"
                    class="p-1 rounded text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors cursor-pointer"
                    title="Delete"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </button>
                <button
                    type="button"
                    wire:click.stop="quickToggleRead({{ $thread->uid }})"
                    class="p-1 rounded text-ink-tertiary hover:text-primary hover:bg-hover transition-colors cursor-pointer"
                    title="{{ $thread->is_seen ? 'Mark as unread' : 'Mark as read' }}"
                >
                    @if($thread->is_seen)
                        <svg class="w-3.5 h-3.5 fill-current text-primary" viewBox="0 0 24 24">
                            <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                        </svg>
                    @else
                        <svg class="w-3.5 h-3.5 fill-none stroke-currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    @endif
                </button>
            </div>

            {{-- Expand chevron --}}
            <button
                type="button"
                class="ml-1 p-1 rounded-md text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer shrink-0"
                @click.stop="toggleExpand()"
                aria-label="Toggle thread messages"
            >
                <svg
                    class="w-3.5 h-3.5 transition-transform duration-150"
                    :class="{ 'rotate-90': expanded }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                    stroke-width="2"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                </svg>
            </button>
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
        class="border-l-2 border-primary/20 bg-surface-sunken/30 pl-3 py-1"
    >
        @if(!empty($thread->children) && count($thread->children) > 0)
            @foreach($thread->children as $child)
                <x-mailbox.thread-row :thread="$child" :depth="$depth + 1" :selectedUids="$selectedUids" />
            @endforeach

            @if(count($thread->children) > 10)
                <div class="px-4 py-2 text-center">
                    <button class="text-xs text-primary hover:text-primary-hover font-medium transition-colors cursor-pointer">
                        Load {{ count($thread->children) - 10 }} more messages
                    </button>
                </div>
            @endif
        @else
            <div class="px-4 py-2 text-xs text-ink-tertiary">
                {{ $thread->snippet ?: 'No additional message details.' }}
            </div>
        @endif
    </div>
</div>
