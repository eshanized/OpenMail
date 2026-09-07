<div
    x-data="{ open: @js($showModal) }"
    x-show="open"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Background overlay --}}
        <div
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            x-show="open"
            @click="open = false; $wire.closeModal()"
        ></div>

        {{-- Modal panel --}}
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    {{-- Avatar Preview --}}
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-16 w-16 rounded-full {{ \App\Models\Contact::colorFromEmail($email ?: 'test@test.com') }}">
                        <span class="text-2xl font-medium text-white">
                            {{ strtoupper(substr($name, 0, 1) ?: '?') }}
                        </span>
                    </div>

                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            {{ $contact ? 'Edit Contact' : 'New Contact' }}
                        </h3>

                        <div class="mt-4 space-y-4">
                            {{-- Name --}}
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Name *</label>
                                <input
                                    type="text"
                                    wire:model="name"
                                    id="name"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="John Doe"
                                >
                            </div>

                            {{-- Email --}}
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email *</label>
                                <input
                                    type="email"
                                    wire:model="email"
                                    id="email"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="john@example.com"
                                >
                            </div>

                            {{-- Phone --}}
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input
                                    type="tel"
                                    wire:model="phone"
                                    id="phone"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="+1 (555) 123-4567"
                                >
                            </div>

                            {{-- Notes --}}
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes</label>
                                <textarea
                                    wire:model="notes"
                                    id="notes"
                                    rows="3"
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    placeholder="Add any notes about this contact..."
                                ></textarea>
                            </div>

                            {{-- Groups --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Groups</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableGroups as $group)
                                        <label class="inline-flex items-center">
                                            <input
                                                type="checkbox"
                                                wire:model="selectedGroups"
                                                value="{{ $group['id'] }}"
                                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                            <span class="ml-1 text-sm text-gray-700">{{ $group['name'] }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Add new group --}}
                                <div class="mt-2 flex items-center space-x-2">
                                    <input
                                        type="text"
                                        wire:model="newGroupName"
                                        placeholder="New group name"
                                        class="block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    >
                                    <button
                                        wire:click="addGroup"
                                        type="button"
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                    >
                                        Add
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button
                    type="button"
                    wire:click="save"
                    class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                >
                    Save
                </button>
                <button
                    type="button"
                    wire:click="closeModal"
                    class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:w-auto sm:text-sm"
                >
                    Cancel
                </button>
                @if($contact)
                    <button
                        type="button"
                        wire:click="delete"
                        wire:confirm="Are you sure you want to delete this contact?"
                        class="mt-3 w-full inline-flex justify-center rounded-md border border-red-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-red-700 hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:mt-0 sm:w-auto sm:text-sm"
                    >
                        Delete
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
