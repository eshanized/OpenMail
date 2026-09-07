{{-- Label Create/Edit Modal --}}
<div
    x-data="{ show: @entangle('show') }"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 overflow-y-auto"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true"
>
    {{-- Backdrop --}}
    <div
        x-show="show"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 bg-gray-500 bg-opacity-75"
        @click="$wire.closeModal()"
    ></div>

    {{-- Modal panel --}}
    <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative transform overflow-hidden rounded-lg bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md"
        >
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4" id="modal-title">
                    {{ $label ? 'Edit Label' : 'Create Label' }}
                </h3>

                {{-- Name field --}}
                <div class="mb-4">
                    <label for="label-name" class="block text-sm font-medium text-gray-700 mb-1">Name</label>
                    <input
                        type="text"
                        id="label-name"
                        wire:model="name"
                        maxlength="50"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
                        placeholder="Label name"
                        autofocus
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ strlen($name) }}/50</p>
                </div>

                {{-- Color picker --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Color</label>
                    <div class="grid grid-cols-5 gap-2" role="radiogroup" aria-label="Label color">
                        @foreach($palette as $colorName => $hex)
                            <button
                                type="button"
                                wire:click="selectColor('{{ $hex }}')"
                                class="relative w-10 h-10 rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-transform {{ $color === $hex ? 'scale-110' : 'hover:scale-105' }}"
                                style="background-color: {{ $hex }}"
                                aria-label="{{ $colorName }}"
                                role="radio"
                                aria-checked="{{ $color === $hex ? 'true' : 'false' }}"
                            >
                                @if($color === $hex)
                                    <svg class="w-5 h-5 text-white absolute inset-0 m-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Actions --}}
            <div class="bg-gray-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 gap-2">
                <button
                    type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:w-auto disabled:opacity-50"
                >
                    {{ $label ? 'Save' : 'Create' }}
                </button>
                <button
                    type="button"
                    @click="$wire.closeModal()"
                    class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50 sm:mt-0 sm:w-auto"
                >
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>
