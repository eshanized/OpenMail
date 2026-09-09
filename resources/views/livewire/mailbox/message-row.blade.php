<div
    data-uid="{{ $message->uid }}"
    class="message-row flex items-center transition-colors duration-75
        {{ !$selected
            ? 'hover:bg-hover'
            : 'bg-selected' }}
        focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary"
    tabindex="0"
>
    {{-- Checkbox --}}
    <input
        type="checkbox"
        @if($selected) checked @endif
        @click.stop="toggle({{ $message->uid }}, $event)"
        class="w-4 h-4 text-primary border-border-strong rounded focus:ring-primary focus:ring-2"
    >

    {{-- Star --}}
    <button
        @click.stop="toggleStar({{ $message->uid }})"
        wire:click="toggleStar({{ $message->uid }})"
        wire:loading.attr="disabled"
        class="ml-2 p-0.5 text-ink-tertiary hover:text-warning transition-colors rounded"
        title="Toggle star"
    >
        @if($message->is_flagged)
            <svg class="w-4 h-4 fill-current text-warning" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
            </svg>
        @else
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 20 20" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
            </svg>
        @endif
    </button>

    {{-- Message content --}}
    <a href="{{ route('message.show', ['folderPath' => $message->folder_path ?? $folderPath, 'uid' => $message->uid]) }}" wire:navigate class="flex-1 min-w-0 ml-3 block">
        {{-- Row 1: From + Date --}}
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center min-w-0 gap-1.5">
                <span class="truncate text-sm {{ !$message->is_seen ? 'font-semibold text-ink' : 'text-ink-secondary' }}">
                    {{ $message->from_display }}
                </span>
                @if(!$message->is_seen)
                    <span class="w-1.5 h-1.5 rounded-full bg-primary flex-shrink-0"></span>
                @endif
            </div>
            <span class="text-xs text-ink-tertiary whitespace-nowrap flex-shrink-0">
                {{ $message->formatted_date }}
            </span>
        </div>

        {{-- Row 2: Subject --}}
        <div class="flex items-center justify-between gap-3 mt-0.5">
            <span class="truncate text-sm {{ !$message->is_seen ? 'font-medium text-ink' : 'text-ink-secondary' }}">
                {{ Str::limit($message->subject, 70) }}
            </span>
            <div class="flex items-center gap-1.5 flex-shrink-0">
                @if($message->has_attachments)
                    <svg class="w-3.5 h-3.5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                    </svg>
                @endif
            </div>
        </div>

        {{-- Row 3: Snippet --}}
        @if($message->snippet)
            <div class="text-xs text-ink-tertiary truncate mt-0.5">
                {{ Str::limit($message->snippet, 90) }}
            </div>
        @endif
    </a>
</div>
