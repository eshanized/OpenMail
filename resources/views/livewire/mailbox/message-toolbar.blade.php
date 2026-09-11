@props(['selectedUids', 'folderPath', 'folders'])

<div
    x-show="selectedUids.length > 0"
    x-cloak
    x-data="{
        selectedUids: @js($selectedUids),
        showMoveDropdown: false,
        archiveToast: false,
        archiveMessage: '',
    }"
    x-init="
        $wire.on('messages-archived', (data) => {
            archiveToast = true;
            archiveMessage = data?.message || 'Message archived.';
            setTimeout(() => { archiveToast = false; }, 5000);
        });
        $wire.on('messages-restored', () => {
            archiveToast = false;
        });
        // Keyboard shortcut: 'e' to archive
        window.addEventListener('keydown', (e) => {
            if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable) return;
            if (e.key === 'e' && selectedUids.length > 0) {
                e.preventDefault();
                $wire.archiveSelected();
            }
        });
    "
    class="bg-surface-raised border border-border rounded px-3 py-1.5 mb-2.5 flex flex-wrap items-center justify-between gap-2 shadow-2xs"
    x-transition
>
    <div class="flex items-center gap-2">
        <span class="text-xs font-medium text-ink">
            <span x-text="selectedUids.length"></span> selected
        </span>

        <button
            @click="$dispatch('clear-selection')"
            class="text-xs text-ink-tertiary hover:text-ink transition-colors cursor-pointer"
            title="Deselect all"
        >
            Deselect
        </button>
    </div>

    <div class="flex items-center gap-1.5 flex-wrap">
        {{-- Archive --}}
        <button
            wire:click="archiveSelected"
            data-loading.attr="disabled"
            class="inline-flex items-center gap-1.5 rounded bg-primary hover:bg-primary-hover px-2.5 py-1 text-xs font-medium text-white transition-colors disabled:opacity-50 cursor-pointer"
            title="Archive (e)"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            <span>Archive</span>
        </button>

        {{-- Delete (Move to Trash) --}}
        <button
            wire:click="bulkDelete"
            data-loading.attr="disabled"
            class="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-error hover:bg-error-subtle border border-error/20 rounded transition-colors disabled:opacity-50 cursor-pointer"
            onclick="return confirm('Move selected messages to Trash?')"
            title="Delete"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Delete</span>
        </button>

        {{-- Spam --}}
        <button
            wire:click="bulkMove('Spam')"
            data-loading.attr="disabled"
            class="p-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer"
            title="Mark as Spam"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </button>

        {{-- Move to folder dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                @keydown.escape="open = false"
                @click.outside="open = false"
                class="px-2 py-1 text-xs font-medium text-ink-secondary hover:text-ink bg-surface-raised border border-border rounded hover:bg-hover transition-colors flex items-center gap-1 cursor-pointer"
            >
                <span>Move to</span>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95"
                class="absolute right-0 mt-1 w-52 bg-surface-raised rounded shadow-md border border-border py-1 z-50"
            >
                <div class="px-2.5 py-1 text-[10px] font-semibold text-ink-tertiary uppercase tracking-wider">
                    Move to
                </div>
                @foreach($folders as $folder)
                    @if($folder['path'] !== $folderPath)
                        <button
                            wire:click="bulkMove('{{ $folder['path'] }}')"
                            data-loading.attr="disabled"
                            class="w-full px-2.5 py-1.5 text-left text-xs text-ink-secondary hover:bg-hover hover:text-ink disabled:opacity-50 flex items-center gap-2 cursor-pointer"
                        >
                            <span class="truncate">{{ $folder['name'] }}</span>
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Mark read --}}
        <button
            wire:click="bulkMarkRead"
            data-loading.attr="disabled"
            class="p-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer"
            title="Mark as read"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </button>

        {{-- Mark unread --}}
        <button
            wire:click="bulkMarkUnread"
            data-loading.attr="disabled"
            class="p-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer"
            title="Mark as unread"
        >
            <svg class="w-3.5 h-3.5 fill-current text-ink-tertiary" viewBox="0 0 24 24">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
            </svg>
        </button>

        {{-- Star/Flag --}}
        <button
            wire:click="bulkStar"
            data-loading.attr="disabled"
            class="p-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer star-btn"
            title="Flag"
        >
            <svg class="w-3.5 h-3.5 fill-none stroke-currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
            </svg>
        </button>

        {{-- Unstar/Unflag --}}
        <button
            wire:click="bulkUnstar"
            data-loading.attr="disabled"
            class="p-1 text-xs text-ink-secondary hover:text-ink hover:bg-hover border border-border rounded transition-colors disabled:opacity-50 cursor-pointer star-btn"
            title="Unflag"
        >
            <svg class="w-3.5 h-3.5 fill-current text-amber-500" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
        </button>
    </div>

    {{-- Archive Undo Toast --}}
    <div
        x-show="archiveToast"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-ink text-white px-3 py-2 rounded text-xs shadow-md flex items-center gap-3"
    >
        <span x-text="archiveMessage || 'Message archived.'"></span>
        <button
            @click="$wire.undoArchive(); archiveToast = false;"
            class="font-medium text-primary hover:underline cursor-pointer"
        >
            Undo
        </button>
    </div>
</div>
