@php
    $isInbox = in_array(strtolower($folderPath ?? ''), ['inbox']);
    $defaultThreadMode = $isInbox ? 'threaded' : 'flat';
@endphp
<div x-data="{
    selected: new Set(@json($selectedUids)),
    lastChecked: null,
    currentFolder: {{ Js::from($folderPath) }},
    defaultThreadMode: {{ Js::from($defaultThreadMode) }},
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
        if (typeof Livewire !== 'undefined') {
            Livewire.dispatch('openComposer', { mode: 'compose' });
        } else {
            @this.dispatch('openComposer', { mode: 'compose' });
        }
    },
    initThreadMode() {
        const key = 'openmail:threadMode:' + this.currentFolder;
        const saved = localStorage.getItem(key);
        const mode = saved || this.defaultThreadMode;
        @this.set('threadMode', mode);
        window.addEventListener('save-thread-mode', (e) => {
            if (e.detail && e.detail.folderPath === this.currentFolder) {
                localStorage.setItem(key, e.detail.mode);
            }
        });
        let lastToggle = 0;
        window.addEventListener('keydown', (e) => {
            if (e.key === 't' && !e.target.closest('input, textarea, select')) {
                const now = Date.now();
                if (now - lastToggle > 100) {
                    lastToggle = now;
                    @this.call('toggleThreadMode');
                }
            }
        });
        window.addEventListener('load-thread-mode', (e) => {
            if (e.detail && e.detail.folderPath === this.currentFolder) {
                const folderKey = 'openmail:threadMode:' + e.detail.folderPath;
                const saved = localStorage.getItem(folderKey);
                @this.call('onThreadModeLoaded', saved || this.defaultThreadMode);
            }
        });
    }
}" x-init="initThreadMode()">

    {{-- Toolbar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button
                @click="openComposer"
                class="inline-flex items-center gap-2 rounded-xl bg-primary hover:bg-primary-hover
                       px-4 py-2 text-sm font-semibold text-white
                       shadow-xs hover:shadow-md
                       transition-all duration-150
                       focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                       focus-visible:ring-primary
                       active:scale-[0.98] cursor-pointer"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2.25">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                <span>Compose</span>
            </button>
        </div>

        {{-- Search bar --}}
        <div class="flex-1 max-w-md mx-2">
            <livewire:mailbox.search-bar />
        </div>

        {{-- Thread toggle --}}
        <div class="flex items-center gap-2">
            <button
                wire:click="toggleThreadMode"
                class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all duration-150 flex items-center gap-1.5 border cursor-pointer
                    {{ $threadMode === 'threaded'
                        ? 'bg-primary-subtle text-primary border-primary/20'
                        : 'text-ink-secondary border-border hover:bg-hover hover:text-ink' }}"
                title="Toggle thread view (t)"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                </svg>
                <span>{{ $threadMode === 'threaded' ? 'Threaded' : 'Flat' }}</span>
            </button>
        </div>

        {{-- Bulk action toolbar --}}
        <livewire:mailbox.message-toolbar
            :selectedUids="$selectedUids"
            :folderPath="$folderPath"
        />
    </div>

    {{-- Sort controls --}}
    <div class="mb-3 flex flex-wrap items-center gap-1.5 text-xs">
        <span class="text-ink-tertiary mr-1 font-medium text-[11px] uppercase tracking-wider">Sort:</span>
        <div class="inline-flex items-center rounded-lg p-0.5 bg-surface-sunken border border-border-subtle">
            @foreach([
                ['key' => 'date', 'dir' => 'desc', 'label' => 'Date'],
                ['key' => 'sender', 'dir' => 'asc', 'label' => 'Sender'],
                ['key' => 'subject', 'dir' => 'asc', 'label' => 'Subject'],
                ['key' => 'size', 'dir' => 'desc', 'label' => 'Size'],
            ] as $sort)
                <button
                    wire:click="setSort('{{ $sort['key'] }}', '{{ $sort['dir'] }}')"
                    class="px-2.5 py-1 rounded-md transition-all duration-100 cursor-pointer text-xs
                        {{ $sortBy === $sort['key']
                            ? 'bg-primary-subtle text-primary font-semibold shadow-2xs'
                            : 'text-ink-tertiary hover:text-ink-secondary hover:bg-hover' }}"
                >
                    {{ $sort['label'] }}
                    @if($sortBy === $sort['key'])
                        <span class="font-mono">{{ $sortDir === 'desc' ? ' ↓' : ' ↑' }}</span>
                    @endif
                </button>
            @endforeach
        </div>
    </div>

    {{-- Message list container --}}
    <div
        wire:loading.remove
        wire:target="onFolderChanged,setSort,toggleThreadMode"
        class="email-canvas-card overflow-hidden"
    >
        @php
            $isThreaded = $threadMode === 'threaded';
            $isEmpty = $isThreaded ? empty($messages) : $messages->isEmpty();
        @endphp

        @if($isEmpty)
            {{-- Empty state --}}
            <div class="p-16 text-center">
                <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-surface-sunken flex items-center justify-center ring-1 ring-border-subtle">
                    <svg class="w-8 h-8 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.25">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-ink mb-1">
                    {{ $isThreaded ? 'No conversations here' : 'No messages here' }}
                </h3>
                <p class="text-xs text-ink-tertiary max-w-xs mx-auto">
                    {{ $isThreaded
                        ? 'Messages in this folder will appear as threaded conversations.'
                        : 'When new messages arrive, they\'ll show up here.' }}
                </p>
            </div>
        @else
            @if($isThreaded)
                <div class="divide-y divide-border-subtle">
                    @foreach($messages as $thread)
                        <x-mailbox.thread-row :thread="$thread" :depth="0" :isExpanded="false" />
                    @endforeach
                </div>
                <div class="p-3 border-t border-border">
                    {{ $messages instanceof \Illuminate\Pagination\LengthAwarePaginator ? $messages->links() : '' }}
                </div>
            @else
                <div class="divide-y divide-border-subtle">
                    @foreach($messages as $message)
                        <livewire:mailbox.message-row
                            :message="$message"
                            :selected="$selectedUids->contains($message->uid)"
                            :key="$message->uid"
                        />
                    @endforeach
                </div>
                <div class="p-3 border-t border-border">
                    {{ $messages->links() }}
                </div>
            @endif
        @endif
    </div>

    {{-- Skeleton loading --}}
    <div
        wire:loading
        wire:target="onFolderChanged,setSort,toggleThreadMode"
        class="email-canvas-card overflow-hidden"
        aria-hidden="true"
        style="display: none;"
    >
        @foreach([1, 2, 3, 4, 5, 6, 7] as $i)
            <div class="flex items-center gap-3 px-4 py-3 border-b border-border-subtle last:border-b-0">
                <div class="skeleton h-4 w-4 rounded"></div>
                <div class="skeleton h-4 w-4 rounded-full"></div>
                <div class="flex-1 space-y-2 min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="skeleton h-3.5 w-24 rounded"></div>
                        <div class="skeleton h-3 w-32 rounded flex-1"></div>
                        <div class="skeleton h-3 w-12 rounded"></div>
                    </div>
                    <div class="skeleton h-3 w-48 rounded"></div>
                </div>
            </div>
        @endforeach
    </div>
</div>
</div>
