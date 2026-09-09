@props(['selectedUids', 'folderPath', 'folders'])

<div
    x-show="selectedUids.length > 0"
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
    class="mb-4 p-3 glass-card flex flex-wrap items-center gap-3"
    x-transition
>
    <span class="text-sm font-medium text-primary">
        <span x-text="selectedUids.length"></span> selected
    </span>

    <div class="flex items-center gap-2 flex-wrap">
        {{-- Archive --}}
        <button
            wire:click="archiveSelected"
            data-loading.attr="disabled"
            class="group inline-flex items-center gap-1 rounded-lg
                       bg-linear-to-r from-blue-600 to-purple-600
                       px-3 py-1.5 text-sm font-semibold text-white
                       shadow-glow
                       transition duration-150 ease-out
                       hover:scale-[1.02] hover:shadow-glow-strong
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                       focus-visible:ring-primary
                       motion-reduce:transform-none motion-reduce:transition-none
                       disabled:opacity-50"
            title="Archive (e)"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
            </svg>
            Archive
        </button>

        {{-- Delete (Move to Trash) --}}
        <button
            wire:click="bulkDelete"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-semibold text-white bg-danger rounded-lg hover:bg-danger-hover transition-colors disabled:opacity-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-danger"
            onclick="return confirm('Move selected messages to Trash?')"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
        </button>

        {{-- Spam --}}
        <button
            wire:click="bulkMove('Spam')"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors disabled:opacity-50"
            title="Mark as Spam"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </button>

        {{-- Move to folder dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button
                @click="open = !open"
                @keydown.escape="open = false"
                @click.outside="open = false"
                class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors flex items-center gap-1"
            >
                Move to...
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div
                x-show="open"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute right-0 mt-1 w-56 bg-white rounded-lg shadow-lg border border-border py-1 z-50"
            >
                @foreach($folders as $folder)
                    @if($folder['path'] !== $folderPath)
                        <button
                            wire:click="bulkMove('{{ $folder['path'] }}')"
                            data-loading.attr="disabled"
                            class="w-full px-4 py-2 text-left text-sm text-ink-secondary hover:bg-surface-sunken disabled:opacity-50 flex items-center"
                        >
                            @if($folder['role'])
                                <span class="w-5 h-5 mr-2 text-ink-tertiary">
                                    @if($folder['role'] === 'inbox')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                    @elseif($folder['role'] === 'sent')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                    @elseif($folder['role'] === 'drafts')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828l6.586 6.586a2 2 0 002.828-2.828L10.828 2.172a2 2 0 00-2.828 0L2.172 9.828a2 2 0 000 2.828l6.586 6.586a2 2 0 102.828-2.828z"></path></svg>
                                    @elseif($folder['role'] === 'trash')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    @elseif($folder['role'] === 'spam')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                    @elseif($folder['role'] === 'archive')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
                                    @endif
                                </span>
                            @endif
                            {{ $folder['name'] }}
                        </button>
                    @endif
                @endforeach
            </div>
        </div>

        {{-- Mark read --}}
        <button
            wire:click="bulkMarkRead"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors disabled:opacity-50"
            title="Mark as read"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
            </svg>
        </button>

        {{-- Mark unread --}}
        <button
            wire:click="bulkMarkUnread"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors disabled:opacity-50"
            title="Mark as unread"
        >
            <svg class="w-4 h-4 fill-current text-primary" viewBox="0 0 24 24">
                <path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"></path>
            </svg>
        </button>

        {{-- Star/Flag --}}
        <button
            wire:click="bulkStar"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors disabled:opacity-50"
            title="Flag"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
            </svg>
        </button>

        {{-- Unstar/Unflag --}}
        <button
            wire:click="bulkUnstar"
            data-loading.attr="disabled"
            class="px-3 py-1.5 text-sm font-medium text-ink-secondary bg-white border border-border rounded-lg hover:bg-surface-sunken transition-colors disabled:opacity-50"
            title="Unflag"
        >
            <svg class="w-4 h-4 fill-current text-yellow-500" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
        </button>

        {{-- Clear selection --}}
        <button
            @click="$dispatch('clear-selection')"
            class="px-3 py-1.5 text-sm font-medium text-ink-tertiary hover:text-ink-secondary transition-colors"
            title="Clear selection"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Archive Undo Toast --}}
    <div
        x-show="archiveToast"
        x-cloak
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-2"
        class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 bg-ink text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-3"
    >
        <span class="text-sm" x-text="archiveMessage || 'Message archived.'"></span>
        <button
            @click="$wire.undoArchive(); archiveToast = false;"
            class="text-sm font-semibold text-primary hover:text-primary underline"
        >
            Undo
        </button>
    </div>
</div>
