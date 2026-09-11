@php
    $displaySender = !empty($message->from_display) ? $message->from_display : (!empty($message->from_name) ? $message->from_name : (!empty($message->from_address) ? $message->from_address : 'Unknown'));
    $cleanLetter = preg_replace('/[^a-zA-Z0-9]/', '', $displaySender);
    $avatarChar = !empty($cleanLetter) ? strtoupper(substr($cleanLetter, 0, 1)) : strtoupper(substr($displaySender, 0, 1));
    if ($avatarChar === '') {
        $avatarChar = 'M';
    }
    $avatarIdx = abs(crc32($displaySender)) % 6;
    $isStarred = (bool) ($message->is_flagged ?? false);
@endphp
<div
    data-uid="{{ $message->uid }}"
    class="message-row message-row-card border-b border-border-subtle last:border-b-0 flex items-center transition-colors duration-75 group cursor-default
        {{ !$message->is_seen ? 'message-row-unread bg-surface-raised' : 'hover:bg-hover' }}
        {{ !$selected ? '' : '!bg-selected' }}
        focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary/30"
    tabindex="0"
>
    {{-- Selection and Star --}}
    <div class="flex items-center gap-1 shrink-0">
        {{-- Checkbox --}}
        <input
            type="checkbox"
            @if($selected) checked @endif
            @click.stop="toggle({{ $message->uid }}, $event)"
            class="mail-checkbox"
            aria-label="Select message from {{ $displaySender }}"
        >

        {{-- Star button --}}
        <button
            @click.stop="toggleStar({{ $message->uid }})"
            wire:click="toggleStar({{ $message->uid }})"
            wire:loading.attr="disabled"
            class="p-1 rounded transition-colors duration-100 star-btn shrink-0 cursor-pointer {{ $isStarred ? 'text-amber-500' : 'text-ink-tertiary/50 hover:text-amber-400' }}"
            title="Toggle star"
        >
            @if($isStarred)
                <svg class="message-row-icon fill-amber-500" viewBox="0 0 20 20">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                </svg>
            @else
                <svg class="message-row-icon fill-none stroke-current" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z"/>
                </svg>
            @endif
        </button>
    </div>

    {{-- Sender Avatar & Name --}}
    <div class="flex items-center min-w-0 w-32 sm:w-44 shrink-0">
        {{-- Avatar — neutral, no gradient --}}
        <div class="message-row-avatar rounded avatar-gradient-{{ $avatarIdx }} flex items-center justify-center font-semibold shrink-0 mr-2">
            {{ $avatarChar }}
        </div>
        <div class="min-w-0 flex items-center gap-1.5">
            <span class="truncate message-row-title {{ !$message->is_seen ? 'font-semibold text-ink' : 'font-normal text-ink-secondary' }}">
                {{ $displaySender }}
            </span>
            @if(!$message->is_seen)
                {{-- Unread dot — subtle, inline --}}
                <span class="w-1.5 h-1.5 rounded-full bg-primary shrink-0" aria-label="Unread"></span>
            @endif
        </div>
    </div>

    {{-- Message link --}}
    <a href="{{ route('message.show', ['folderPath' => $message->folder_path ?? $folderPath, 'uid' => $message->uid]) }}" wire:navigate class="flex items-center min-w-0 flex-1 cursor-pointer">
        {{-- Subject + Snippet Preview + Labels --}}
        <div class="flex items-center min-w-0 flex-1 pr-2">
            <span class="message-row-title truncate shrink-0 max-w-[55%] sm:max-w-[45%] {{ !$message->is_seen ? 'font-medium text-ink' : 'font-normal text-ink-secondary' }}">
                {{ $message->subject ?: '(no subject)' }}
            </span>
            @if(!empty($message->snippet))
                <span class="message-row-desc text-ink-tertiary truncate ml-2 font-normal hidden sm:inline">
                    &mdash; {{ Str::limit($message->snippet, 80) }}
                </span>
            @endif

            {{-- Labels — minimal chips --}}
            @php
                $rawLabels = $message->labels ?? null;
                $labels = ($rawLabels instanceof \Illuminate\Support\Collection) ? $rawLabels : collect();
                $displayLabels = $labels->take(2);
                $overflowCount = max(0, $labels->count() - 2);
            @endphp
            @foreach($displayLabels as $label)
                <span class="label-chip text-white shrink-0 ml-1.5"
                    style="background-color: {{ $label->color ?? '#78716c' }}"
                    title="{{ $label->name }}"
                >
                    {{ Str::limit($label->name, 10) }}
                </span>
            @endforeach
            @if($overflowCount > 0)
                <span class="label-chip shrink-0 ml-1 bg-surface-sunken text-ink-tertiary border border-border">
                    +{{ $overflowCount }}
                </span>
            @endif

            {{-- Attachment indicator --}}
            @if($message->has_attachments)
                <span class="text-ink-tertiary ml-2 shrink-0" title="Has attachments">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                    </svg>
                </span>
            @endif
        </div>
    </a>

    {{-- Right: Date & Hover Actions --}}
    <div class="relative flex items-center justify-end w-24 sm:w-32 shrink-0 pl-1">
        <span class="row-date-display text-xs text-ink-tertiary whitespace-nowrap tabular-nums {{ !$message->is_seen ? 'font-medium text-ink-secondary' : '' }}">
            {{ $message->formatted_date }}
        </span>

        {{-- Quick Action Buttons on Hover — minimal, no backdrop blur card --}}
        <div class="row-quick-actions absolute right-0 flex items-center gap-0.5 bg-surface-raised border border-border px-1 py-0.5 rounded shadow-xs">
            <button
                type="button"
                wire:click.stop="$parent.quickArchive({{ $message->uid }})"
                class="p-1 rounded text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                title="Archive"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                </svg>
            </button>
            <button
                type="button"
                wire:click.stop="$parent.quickDelete({{ $message->uid }})"
                class="p-1 rounded text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors cursor-pointer"
                title="Delete"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </button>
            <button
                type="button"
                wire:click.stop="$parent.quickToggleRead({{ $message->uid }})"
                class="p-1 rounded text-ink-tertiary hover:text-primary hover:bg-hover transition-colors cursor-pointer"
                title="{{ $message->is_seen ? 'Mark as unread' : 'Mark as read' }}"
            >
                @if($message->is_seen)
                    <svg class="w-3.5 h-3.5 fill-current text-ink-tertiary" viewBox="0 0 24 24">
                        <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/>
                    </svg>
                @else
                    <svg class="w-3.5 h-3.5 fill-none stroke-current" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                @endif
            </button>
        </div>
    </div>
</div>
