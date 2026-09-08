<div class="space-y-6">
    <section>
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Signatures</h3>
            <button
                wire:click="openCreateModal"
                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors flex items-center gap-2"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                New Signature
            </button>
        </div>

        @if($signatures->isEmpty())
            <div class="text-center py-12 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                </svg>
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No signatures yet.</p>
                <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">Create your first signature to use in outgoing emails.</p>
                <button
                    wire:click="openCreateModal"
                    class="mt-4 px-4 py-2 text-sm font-medium text-blue-600 hover:text-blue-800"
                >
                    Create Signature
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($signatures as $signature)
                    <div class="flex items-center justify-between p-4 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg hover:border-gray-300 dark:hover:border-gray-600 transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900 rounded-full flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $signature->name }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    @if($signature->is_default)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                            Default
                                        </span>
                                    @else
                                        {{ strip_tags($signature->content_html ?? 'Empty signature') }}
                                    @endif
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-shrink-0">
                            @unless($signature->is_default)
                                <button
                                    wire:click="setDefaultSignature({{ $signature->id }})"
                                    class="px-3 py-1 text-xs font-medium text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors"
                                    title="Set as default"
                                >
                                    Set Default
                                </button>
                            @endunless
                            <button
                                wire:click="openEditModal({{ $signature->id }})"
                                class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
                                title="Edit signature"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button
                                wire:click="deleteSignature({{ $signature->id }})"
                                wire:confirm="Are you sure you want to delete this signature?"
                                class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors"
                                title="Delete signature"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    {{-- Signature Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                {{-- Backdrop --}}
                <div
                    class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
                    wire:click="closeModal"
                ></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                            {{ $modalTitle }}
                        </h3>
                    </div>

                    <div class="px-6 py-4 space-y-4">
                        {{-- Name --}}
                        <div>
                            <label for="sig-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Signature Name
                            </label>
                            <input
                                type="text"
                                id="sig-name"
                                wire:model="name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm dark:bg-gray-700 dark:text-white"
                                placeholder="e.g., Work, Personal"
                                maxlength="100"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tiptap Editor --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Signature Content
                            </label>
                            <x-tiptap-editor
                                :content-json="$contentJson"
                                wire:model="contentJson"
                                wire:change="updateContentJson($event.target.value)"
                                placeholder="Type your signature..."
                                :min-height="'150px'"
                            />
                        </div>

                        {{-- Default toggle --}}
                        <div class="flex items-center gap-2">
                            <input
                                type="checkbox"
                                id="sig-default"
                                wire:model="isDefault"
                                class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            >
                            <label for="sig-default" class="text-sm text-gray-700 dark:text-gray-300">
                                Set as default signature
                            </label>
                        </div>
                    </div>

                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                        <button
                            wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="saveSignature"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50"
                        >
                            <span wire:loading.remove wire:target="saveSignature">
                                {{ $editingSignatureId ? 'Update Signature' : 'Create Signature' }}
                            </span>
                            <span wire:loading wire:target="saveSignature">
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
