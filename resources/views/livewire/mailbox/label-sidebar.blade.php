@props(['labels', 'activeLabelId'])

<div
    x-data="{
        contextMenu: { show: false, x: 0, y: 0, label: null },
        openContextMenu(e, label) {
            e.preventDefault();
            this.contextMenu = { show: true, x: e.clientX, y: e.clientY, label: label };
        },
        closeContextMenu() {
            this.contextMenu = { show: false, x: 0, y: 0, label: null };
        }
    }"
    @click="closeContextMenu()"
    @keydown.escape.window="closeContextMenu()"
    class="h-full flex flex-col"
>
    {{-- Header --}}
    <div class="flex items-center justify-between px-4 py-3 border-b border-border">
        <h3 class="text-sm font-semibold text-ink">Labels</h3>
        <button
            wire:click="openCreateModal"
            class="text-xs text-primary hover:text-primary-hover font-medium flex items-center gap-1 cursor-pointer"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            <span>New</span>
        </button>
    </div>

    {{-- Loading state --}}
    @if($isLoading)
        <div class="p-3 space-y-2">
            @foreach(range(1, 4) as $i)
                <div class="flex items-center gap-2.5">
                    <div class="w-2.5 h-2.5 rounded-full bg-surface-sunken skeleton"></div>
                    <div class="h-3 bg-surface-sunken rounded flex-1 skeleton"></div>
                </div>
            @endforeach
        </div>
    @elseif($labels->isEmpty())
        {{-- Empty state --}}
        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
            <p class="text-xs font-semibold text-ink mb-0.5">No labels created</p>
            <p class="text-xs text-ink-tertiary mb-3">Labels help categorize messages across folders.</p>
            <button
                wire:click="openCreateModal"
                class="text-xs text-primary hover:underline font-medium cursor-pointer"
            >
                Create label
            </button>
        </div>
    @else
        {{-- Label list --}}
        <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
            @foreach($labels as $label)
                <div
                    class="group flex items-center px-2.5 py-1.5 rounded cursor-pointer transition-colors relative
                        {{ $activeLabelId === $label->id
                            ? 'bg-primary-subtle text-primary font-medium'
                            : 'text-ink-secondary hover:bg-hover hover:text-ink' }}"
                    wire:click="selectLabel('{{ $label->id }}')"
                    @contextmenu="openContextMenu($event, @js($label))"
                    @dblclick="$dispatch('openEditLabelModal', { label: @js($label) })"
                >
                    {{-- Color dot --}}
                    <span
                        class="w-2.5 h-2.5 rounded-full flex-shrink-0 mr-2.5"
                        style="background-color: {{ $label->color }}"
                    ></span>

                    {{-- Label name --}}
                    <span class="flex-1 truncate text-xs">
                        {{ $label->name }}
                    </span>

                    {{-- Counts --}}
                    <div class="flex items-center gap-1.5 ml-2">
                        @if($label->unread_count > 0)
                            <span class="text-xs font-medium text-primary tabular-nums">
                                {{ $label->unread_count }}
                            </span>
                        @endif
                        <span class="text-[11px] text-ink-tertiary tabular-nums">{{ $label->total_count }}</span>
                    </div>

                    {{-- Hover actions --}}
                    <div class="hidden group-hover:flex items-center gap-0.5 ml-1.5">
                        <button
                            @click.stop="$dispatch('openEditLabelModal', { label: @js($label) })"
                            class="p-0.5 text-ink-tertiary hover:text-ink rounded"
                            title="Edit"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button
                            @click.stop="if(confirm('Delete label &quot;{{ addslashes($label->name) }}&quot;?')) $wire.deleteLabel({{ $label->id }})"
                            class="p-0.5 text-ink-tertiary hover:text-error rounded"
                            title="Delete"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Context menu --}}
    <div
        x-show="contextMenu.show"
        x-cloak
        class="fixed z-50 bg-surface-raised rounded shadow-md border border-border py-1 min-w-[150px]"
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
        @click.outside="closeContextMenu()"
        @keydown.escape.window="closeContextMenu()"
    >
        <button
            @click="$dispatch('openEditLabelModal', { label: contextMenu.label }); closeContextMenu()"
            class="w-full px-3 py-1.5 text-left text-xs text-ink hover:bg-hover flex items-center gap-2 cursor-pointer"
        >
            <svg class="w-3.5 h-3.5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Rename
        </button>
        <button
            @click="$dispatch('openEditLabelModal', { label: contextMenu.label }); closeContextMenu()"
            class="w-full px-3 py-1.5 text-left text-xs text-ink hover:bg-hover flex items-center gap-2 cursor-pointer"
        >
            <svg class="w-3.5 h-3.5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
            Change Color
        </button>
        <div class="border-t border-border my-1"></div>
        <button
            @click="if(confirm('Delete label &quot;' + contextMenu.label.name + '&quot;?')) { $wire.deleteLabel(contextMenu.label.id); } closeContextMenu()"
            class="w-full px-3 py-1.5 text-left text-xs text-error hover:bg-error-subtle flex items-center gap-2 cursor-pointer"
        >
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
        </button>
    </div>

    {{-- Label Modal --}}
    <livewire:mailbox.label-modal />
</div>
