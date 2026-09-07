<div
    x-data="{ showActions: false }"
    class="flex items-center px-4 py-3 hover:bg-gray-50 cursor-pointer group"
    wire:click="openContactModal"
    @mouseenter="showActions = true"
    @mouseleave="showActions = false"
>
    {{-- Avatar --}}
    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium text-sm {{ $contact->avatar_color }}">
        {{ strtoupper(substr($contact->name, 0, 1)) }}
    </div>

    {{-- Contact Info --}}
    <div class="ml-3 flex-1 min-w-0">
        <div class="text-sm font-medium text-gray-900 truncate">{{ $contact->name }}</div>
        <div class="text-sm text-gray-500 truncate">{{ $contact->email }}</div>
        @if($contact->phone)
            <div class="text-xs text-gray-400 truncate flex items-center">
                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                </svg>
                {{ $contact->phone }}
            </div>
        @endif
    </div>

    {{-- Groups --}}
    <div class="ml-2 flex-shrink-0 flex items-center space-x-1">
        @php
            $groups = $contact->groups->take(2);
            $overflow = $contact->groups->count() - 2;
        @endphp
        @foreach($groups as $group)
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                {{ $group->name }}
            </span>
        @endforeach
        @if($overflow > 0)
            <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                +{{ $overflow }}
            </span>
        @endif
    </div>

    {{-- Actions --}}
    <div
        x-show="showActions"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        class="ml-2 flex-shrink-0 flex items-center space-x-1"
    >
        <button
            wire:click.stop="openContactModal"
            class="p-1 text-gray-400 hover:text-gray-600"
            title="Edit"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
            </svg>
        </button>
        <button
            wire:click.stop="deleteContact"
            wire:confirm="Are you sure you want to delete this contact?"
            class="p-1 text-gray-400 hover:text-red-600"
            title="Delete"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
        </button>
    </div>
</div>
