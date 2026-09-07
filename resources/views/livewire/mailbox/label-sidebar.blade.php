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
    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-200">
        <h3 class="text-sm font-semibold text-gray-900">Labels</h3>
        <button
            wire:click="openCreateModal"
            class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
            </svg>
            Create
        </button>
    </div>

    {{-- Loading state --}}
    @if($isLoading)
        <div class="p-4 space-y-3">
            @foreach(range(1, 5) as $i)
                <div class="flex items-center gap-3 animate-pulse">
                    <div class="w-3 h-3 rounded-full bg-gray-200"></div>
                    <div class="h-4 bg-gray-200 rounded flex-1"></div>
                    <div class="h-4 bg-gray-200 rounded w-8"></div>
                </div>
            @endforeach
        </div>
    @elseif($labels->isEmpty())
        {{-- Empty state --}}
        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
            <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
            </svg>
            <p class="text-sm font-medium text-gray-900 mb-1">No labels created</p>
            <p class="text-xs text-gray-500 mb-3">Create labels to organize messages across folders.</p>
            <button
                wire:click="openCreateModal"
                class="text-sm text-blue-600 hover:text-blue-800 font-medium"
            >
                Create Label
            </button>
        </div>
    @else
        {{-- Label list --}}
        <div class="flex-1 overflow-y-auto p-2 space-y-0.5">
            @foreach($labels as $label)
                <div
                    class="group flex items-center px-3 py-2 rounded-lg cursor-pointer transition-colors relative
                        {{ $activeLabelId === $label->id
                            ? 'bg-blue-50 text-blue-700'
                            : 'text-gray-700 hover:bg-gray-50' }}"
                    wire:click="selectLabel('{{ $label->id }}')"
                    @contextmenu="openContextMenu($event, @js($label))"
                    @dblclick="$dispatch('openEditLabelModal', { label: @js($label) })"
                >
                    {{-- Color dot --}}
                    <span
                        class="w-3 h-3 rounded-full flex-shrink-0 mr-3"
                        style="background-color: {{ $label->color }}"
                    ></span>

                    {{-- Label name --}}
                    <span class="flex-1 truncate text-sm font-medium">
                        {{ $label->name }}
                    </span>

                    {{-- Counts --}}
                    <div class="flex items-center gap-2 ml-2">
                        @if($label->unread_count > 0)
                            <span class="text-xs font-bold text-blue-600 bg-blue-100 px-1.5 py-0.5 rounded-full">
                                {{ $label->unread_count }}
                            </span>
                        @endif
                        <span class="text-xs text-gray-400">{{ $label->total_count }}</span>
                    </div>

                    {{-- Hover actions --}}
                    <div class="hidden group-hover:flex items-center gap-1 ml-2">
                        <button
                            @click.stop="$dispatch('openEditLabelModal', { label: @js($label) })"
                            class="p-1 text-gray-400 hover:text-gray-600 rounded"
                            title="Edit"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                            </svg>
                        </button>
                        <button
                            @click.stop="if(confirm('Delete label &quot;{{ addslashes($label->name) }}&quot;?')) $wire.deleteLabel({{ $label->id }})"
                            class="p-1 text-gray-400 hover:text-red-600 rounded"
                            title="Delete"
                        >
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        class="fixed z-50 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[160px]"
        :style="`left: ${contextMenu.x}px; top: ${contextMenu.y}px;`"
        @click.outside="closeContextMenu()"
        @keydown.escape.window="closeContextMenu()"
    >
        <button
            @click="$dispatch('openEditLabelModal', { label: contextMenu.label }); closeContextMenu()"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
        >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
            </svg>
            Rename
        </button>
        <button
            @click="$dispatch('openEditLabelModal', { label: contextMenu.label }); closeContextMenu()"
            class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2"
        >
            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
            </svg>
            Change Color
        </button>
        <div class="border-t border-gray-100 my-1"></div>
        <button
            @click="if(confirm('Delete label &quot;' + contextMenu.label.name + '&quot;?')) { $wire.deleteLabel(contextMenu.label.id); } closeContextMenu()"
            class="w-full px-4 py-2 text-left text-sm text-red-600 hover:bg-red-50 flex items-center gap-2"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            Delete
        </button>
    </div>

    {{-- Label Modal --}}
    <livewire:mailbox.label-modal />
</div>
