<div>
    {{-- Header --}}
    <div class="p-4 border-b border-border flex items-center justify-between">
        <h2 class="text-lg font-semibold text-ink">Contacts</h2>
        <button
            wire:click="openCreateModal"
            class="inline-flex items-center px-3 py-1.5 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-hover focus:outline-none focus:ring-2 focus:ring-primary focus:ring-offset-2"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            New Contact
        </button>
    </div>

    {{-- Search --}}
    <div class="p-4 border-b border-border">
        <div class="relative">
            <input
                type="text"
                wire:model.live.debounce.200ms="search"
                placeholder="Search contacts..."
                class="w-full pl-10 pr-4 py-2 bg-surface-sunken border border-border rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-transparent"
            >
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
        </div>
    </div>

    {{-- Contact List --}}
    <div class="flex-1 overflow-y-auto">
        {{-- Loading State --}}
        @if($isLoading)
            <div class="p-4 space-y-3">
                @for($i = 0; $i < 5; $i++)
                    <div class="animate-pulse flex items-center space-x-3 p-3">
                        <div class="w-10 h-10 bg-surface-sunken rounded-full"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-4 bg-surface-sunken rounded w-3/4"></div>
                            <div class="h-3 bg-surface-sunken rounded w-1/2"></div>
                        </div>
                    </div>
                @endfor
            </div>
        {{-- Empty State --}}
        @elseif($contacts->isEmpty())
            <div class="p-8 text-center">
                <svg class="mx-auto h-12 w-12 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-ink">No contacts yet</h3>
                <p class="mt-1 text-sm text-ink-tertiary">Add contacts manually or import a vCard file.</p>
                <div class="mt-4 space-x-3">
                    <button
                        wire:click="openCreateModal"
                        class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg hover:bg-primary-hover"
                    >
                        Add Contact
                    </button>
                    <button
                        wire:click="openImportModal"
                        class="inline-flex items-center px-4 py-2 bg-white border border-border text-ink-secondary text-sm font-medium rounded-lg hover:bg-surface-sunken"
                    >
                        Import vCard
                    </button>
                </div>
            </div>
        {{-- Contact List --}}
        @else
            <div class="divide-y divide-border">
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
