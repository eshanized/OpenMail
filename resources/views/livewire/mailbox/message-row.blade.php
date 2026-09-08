<div
    data-uid="{{ $message->uid }}"
    class="message-row flex items-center hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0
        {{ !$selected ? 'font-semibold' : '' }}"
    @click="toggle({{ $message->uid }}, $event)"
    wire:navigate="{{ route('message.show', ['folderPath' => $message->folder_path ?? $folderPath, 'uid' => $message->uid]) }}"
>
    {{-- Checkbox --}}
    <input
        type="checkbox"
        @if($selected) checked @endif
        @click.stop="toggle({{ $message->uid }}, $event)"
        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 focus:ring-2"
    >

    {{-- Star/Flag indicator --}}
    <button
        @click.stop="toggleStar({{ $message->uid }})"
        wire:click="toggleStar({{ $message->uid }})"
        wire:loading.attr="disabled"
        class="ml-2 p-1 text-gray-400 hover:text-yellow-500 transition-colors"
        title="Toggle star"
    >
        @if($message->is_flagged)
            <svg class="w-5 h-5 fill-current text-yellow-500" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
        @else
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 20 20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
            </svg>
        @endif
    </button>

    {{-- Message content --}}
    <div class="flex-1 min-w-0 ml-3">
        {{-- From and subject row --}}
        <div class="flex items-center justify-between">
            <div class="flex items-center min-w-0 mr-4">
                <span class="truncate block pr-2 {{ !$message->is_seen ? 'font-semibold text-gray-900' : 'font-normal text-gray-700' }}">
                    {{ $message->from_display }}
                </span>
                @if(!$message->is_seen)
                    <span class="w-2 h-2 bg-blue-600 rounded-full flex-shrink-0"></span>
                @endif
            </div>
            <span class="text-sm text-gray-500 whitespace-nowrap ml-2">{{ $message->formatted_date }}</span>
        </div>

        {{-- Subject and snippet row --}}
        <div class="flex items-center justify-between mt-1">
            <span class="truncate block pr-4 {{ !$message->is_seen ? 'font-semibold text-gray-900' : 'text-gray-700' }}">
                {{ Str::limit($message->subject, 60) }}
            </span>
            <div class="flex items-center space-x-2 ml-2">
                @if($message->has_attachments)
                    <svg class="w-4 h-4 text-gray-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M15.828 7.828a2 2 0 112.828 2.828l-7.071 7.071a2 2 0 01-2.828 0l-7.071-7.071a2 2 0 112.828-2.828L10 12.172l5.828-5.828z"></path>
                    </svg>
                @endif
            </div>
        </div>

        {{-- Snippet --}}
        @if($message->snippet)
            <div class="text-sm text-gray-500 truncate mt-1">
                {{ Str::limit($message->snippet, 80) }}
            </div>
        @endif
    </div>
</div>