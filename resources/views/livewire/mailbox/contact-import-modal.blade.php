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
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Import/Export Contacts
                        </h3>

                        <div class="mt-4">
                            {{-- File Upload Zone --}}
                            @if(!$importFile)
                                <div
                                    class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors"
                                    x-data="{ dragging: false }"
                                    @dragover.prevent="dragging = true"
                                    @dragleave="dragging = false"
                                    @drop.prevent="dragging = false; $refs.fileInput.click()"
                                    :class="{ 'border-blue-400 bg-blue-50': dragging }"
                                >
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Drag and drop a vCard file here, or
                                        <label class="text-blue-600 hover:text-blue-500 cursor-pointer">
                                            browse
                                            <input
                                                type="file"
                                                wire:model="importFile"
                                                x-ref="fileInput"
                                                accept=".vcf,.vcard"
                                                class="hidden"
                                            >
                                        </label>
                                    </p>
                                    <p class="mt-1 text-xs text-gray-500">.vcf or .vcard files up to 2MB</p>
                                </div>
                            @else
                                {{-- Preview --}}
                                <div class="border border-gray-200 rounded-lg overflow-hidden">
                                    <div class="bg-gray-50 px-4 py-3 border-b border-gray-200">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">
                                                {{ count($preview) }} contacts found
                                            </span>
                                            <label class="inline-flex items-center">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selectAll"
                                                    wire:change="toggleSelectAll"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                >
                                                <span class="ml-2 text-sm text-gray-600">Select All</span>
                                            </label>
                                        </div>
                                    </div>

                                    <div class="max-h-64 overflow-y-auto divide-y divide-gray-100">
                                        @forelse($preview as $contact)
                                            <div class="px-4 py-3 flex items-center">
                                                <input
                                                    type="checkbox"
                                                    wire:model="selectedContacts"
                                                    value="{{ $contact['index'] }}"
                                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                >
                                                <div class="ml-3 flex-1">
                                                    <div class="text-sm font-medium text-gray-900">{{ $contact['name'] ?: 'No Name' }}</div>
                                                    <div class="text-sm text-gray-500">{{ $contact['email'] }}</div>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="px-4 py-8 text-center text-gray-500">
                                                No valid contacts found in file
                                            </div>
                                        @endforelse
                                    </div>
                                </div>

                                {{-- Conflict Resolution --}}
                                <div class="mt-4">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Conflict Resolution</label>
                                    <div class="space-y-2">
                                        <label class="inline-flex items-center">
                                            <input
                                                type="radio"
                                                wire:model="conflictStrategy"
                                                value="skip"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">Skip existing contacts</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input
                                                type="radio"
                                                wire:model="conflictStrategy"
                                                value="update"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">Update existing contacts</span>
                                        </label>
                                        <label class="inline-flex items-center">
                                            <input
                                                type="radio"
                                                wire:model="conflictStrategy"
                                                value="duplicate"
                                                class="border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                            >
                                            <span class="ml-2 text-sm text-gray-700">Create duplicates</span>
                                        </label>
                                    </div>
                                </div>

                                {{-- Import Results --}}
                                @if($importResults)
                                    <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium text-green-800">Import Complete</h3>
                                                <div class="mt-2 text-sm text-green-700">
                                                    <p>Imported: {{ $importResults['imported'] }}</p>
                                                    <p>Updated: {{ $importResults['updated'] }}</p>
                                                    <p>Skipped: {{ $importResults['skipped'] }}</p>
                                                    @if(!empty($importResults['errors']))
                                                        <p class="text-red-600">Errors: {{ count($importResults['errors']) }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                @if($importFile && empty($importResults))
                    <button
                        type="button"
                        wire:click="confirmImport"
                        wire:loading.attr="disabled"
                        class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm"
                    >
                        <span wire:loading.remove wire:target="confirmImport">Import Contacts</span>
                        <span wire:loading wire:target="confirmImport">Importing...</span>
                    </button>
                @endif

                <button
                    type="button"
                    wire:click="exportVCard"
                    class="mt-3 sm:mt-0 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
                >
                    Export vCard
                </button>

                <button
                    type="button"
                    wire:click="closeModal"
                    class="mt-3 sm:mt-0 sm:mr-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:w-auto sm:text-sm"
                >
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
