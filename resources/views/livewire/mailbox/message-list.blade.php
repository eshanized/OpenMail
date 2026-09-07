<div x-data="{
    selected: new Set(@json($selectedUids)),
    lastChecked: null,
    toggle(uid, event) {
        if (event.shiftKey && this.lastChecked) {
            const msgs = [...document.querySelectorAll('[data-uid]')];
            const start = msgs.findIndex(m => m.dataset.uid === this.lastChecked);
            const end = msgs.findIndex(m => m.dataset.uid === uid);
            const [from, to] = [Math.min(start, end), Math.max(start, end)];
            for (let i = from; i <= to; i++) {
                this.selected.add(msgs[i].dataset.uid);
            }
        } else {
            if (this.selected.has(uid)) {
                this.selected.delete(uid);
            } else {
                this.selected.add(uid);
            }
        }
        this.lastChecked = uid;
        @this.set('selectedUids', [...this.selected]);
    },
    selectAll() {
        document.querySelectorAll('[data-uid]').forEach(el => {
            this.selected.add(el.dataset.uid);
        });
        @this.set('selectedUids', [...this.selected]);
    },
    clearSelection() {
        this.selected = new Set();
        @this.set('selectedUids', []);
    },
    openComposer() {
        @this.dispatch('openComposer', { mode: 'compose' });
    }
};">
    {{-- Toolbar with Compose button --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-2">
            <button
                @click="openComposer"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828l6.586 6.586a2 2 0 002.828-2.828L10.828 2.172a2 2 0 00-2.828 0L2.172 9.828a2 2 0 000 2.828l6.586 6.586a2 2 0 102.828-2.828z"></path>
                </svg>
                Compose
            </button>
        </div>

        {{-- Thread toggle button --}}
        <div class="flex items-center gap-2">
            <button
                wire:click="toggleThreadMode"
                class="px-3 py-1.5 rounded-lg transition-colors flex items-center gap-2
                    {{ $threadMode === 'threaded' 
                        ? 'bg-blue-50 text-blue-600 border border-blue-200' 
                        : 'text-gray-600 hover:bg-gray-100 border border-gray-200' }}"
                title="Threaded view (t)"
            >
                @if($threadMode === 'threaded')
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                @else
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                @endif
                <span class="text-xs font-medium">{{ $threadMode === 'threaded' ? 'Threaded' : 'Flat' }}</span>
            </button>
        </div>

        {{-- Bulk action toolbar --}}
        <livewire:mailbox.message-toolbar
            :selectedUids="$selectedUids"
            :folderPath="$folderPath"
        />
    </div>

    {{-- Sort controls --}}
    <div class="mb-4 flex flex-wrap items-center gap-2 text-sm">
        <span class="text-gray-500">Sort by:</span>
        <button
            wire:click="setSort('date', 'desc')"
            class="px-3 py-1 rounded {{ $sortBy === 'date' && $sortDir === 'desc' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}"
        >
            Date {{ $sortBy === 'date' ? ($sortDir === 'desc' ? '↓' : '↑') : '' }}
        </button>
        <button
            wire:click="setSort('sender', 'asc')"
            class="px-3 py-1 rounded {{ $sortBy === 'sender' && $sortDir === 'asc' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}"
        >
            Sender {{ $sortBy === 'sender' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
        </button>
        <button
            wire:click="setSort('subject', 'asc')"
            class="px-3 py-1 rounded {{ $sortBy === 'subject' && $sortDir === 'asc' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}"
        >
            Subject {{ $sortBy === 'subject' ? ($sortDir === 'asc' ? '↑' : '↓') : '' }}
        </button>
        <button
            wire:click="setSort('size', 'desc')"
            class="px-3 py-1 rounded {{ $sortBy === 'size' && $sortDir === 'desc' ? 'bg-blue-100 text-blue-700 font-medium' : 'text-gray-600 hover:bg-gray-100' }}"
        >
            Size {{ $sortBy === 'size' ? ($sortDir === 'desc' ? '↓' : '↑') : '' }}
        </button>
    </div>

    {{-- Message list --}}
    <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
        @php
            $isThreaded = $threadMode === 'threaded';
            $isEmpty = $isThreaded ? empty($messages) : $messages->isEmpty();
        @endphp
        
        @if($isEmpty)
            <div class="p-12 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                <p class="text-lg">{{ $isThreaded ? 'No conversations in this folder' : 'No messages in this folder' }}</p>
            </div>
        @else
            @if($isThreaded)
                {{-- Threaded view placeholder - full implementation in Task 2 --}}
                <div class="p-8 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                    </svg>
                    <p class="text-lg">Threaded view enabled</p>
                    <p class="text-sm mt-1">Thread rows will render here (Task 2)</p>
                    <div class="mt-4 text-xs text-gray-400">
                        {{ count($messages) }} thread root(s) found
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($messages as $message)
                        <livewire:mailbox.message-row
                            :message="$message"
                            :selected="$selectedUids->contains($message->uid)"
                            :key="$message->uid"
                        />
                    @endforeach
                </div>

                {{-- Pagination --}}
                <div class="p-4 border-t border-gray-100">
                    {{ $messages->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Loading indicator --}}
    <div wire:loading class="fixed inset-0 bg-white/80 z-50 flex items-center justify-center" style="display: none;">
        <svg class="w-8 h-8 text-blue-600 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
        </svg>
    </div>
</div>