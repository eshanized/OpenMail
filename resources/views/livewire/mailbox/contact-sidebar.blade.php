<div>
    {{-- Header --}}
    <div class="px-4 py-3 border-b border-border flex items-center justify-between">
        <h2 class="text-sm font-semibold text-ink">Contacts</h2>
        <button
            wire:click="openCreateModal"
            class="inline-flex items-center px-2.5 py-1 bg-primary text-white text-xs font-medium rounded hover:bg-primary-hover transition-colors cursor-pointer"
        >
            <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            <span>New</span>
        </button>
    </div>

    {{-- Search --}}
    <div class="p-3 border-b border-border">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.200ms="search"
                placeholder="Search contacts…"
                class="w-full pl-8 pr-3 py-1.5 bg-surface-raised border border-border rounded text-xs text-ink placeholder-ink-tertiary focus:border-primary focus:outline-none"
            >
            <svg class="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    {{-- Contact List --}}
    <div class="flex-1 overflow-y-auto">
        {{-- Loading State --}}
        @if($isLoading)
            <div class="p-3 space-y-2">
                @for($i = 0; $i < 5; $i++)
                    <div class="flex items-center space-x-2.5 p-2">
                        <div class="w-7 h-7 bg-surface-sunken rounded skeleton"></div>
                        <div class="flex-1 space-y-1">
                            <div class="h-3 bg-surface-sunken rounded w-3/4 skeleton"></div>
                            <div class="h-2.5 bg-surface-sunken rounded w-1/2 skeleton"></div>
                        </div>
                    </div>
                @endfor
            </div>
        {{-- Empty State --}}
        @elseif($contacts->isEmpty())
            <div class="p-6 text-center">
                <p class="text-xs font-semibold text-ink">No contacts</p>
                <p class="mt-0.5 text-xs text-ink-tertiary">Add contacts manually or import vCards.</p>
                <div class="mt-3 flex items-center justify-center gap-2">
                    <button
                        wire:click="openCreateModal"
                        class="px-2.5 py-1 bg-primary text-white text-xs font-medium rounded hover:bg-primary-hover transition-colors cursor-pointer"
                    >
                        Add
                    </button>
                    <button
                        wire:click="openImportModal"
                        class="px-2.5 py-1 bg-surface-raised border border-border text-ink-secondary text-xs rounded hover:bg-hover transition-colors cursor-pointer"
                    >
                        Import
                    </button>
                </div>
            </div>
        {{-- Contact List --}}
        @else
            <div class="divide-y divide-border-subtle">
                @foreach($contacts as $contact)
                    <livewire:mailbox.contact-row :contact="$contact" wire:key="contact-{{ $contact->id }}">
                @endforeach
            </div>
        @endif
    </div>

    {{-- Contact Modal --}}
    @if($showModal)
        <livewire:mailbox.contact-modal :contact="$selectedContact" wire:key="contact-modal">
    @endif

    {{-- Import Modal --}}
    @if($showImportModal)
        <livewire:mailbox.contact-import-modal wire:key="import-modal">
    @endif
</div>
