<div class="space-y-6">
    <section>
        <div class="flex items-center justify-between mb-5">
            <div>
                <h3 class="text-sm font-semibold text-ink">Signatures</h3>
                <p class="text-xs text-ink-tertiary mt-0.5">Manage email signatures appended to outgoing messages.</p>
            </div>
            <button
                wire:click="openCreateModal"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-sm font-semibold shadow-sm hover:shadow-md transition-all active:scale-95 cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                New Signature
            </button>
        </div>

        @if($signatures->isEmpty())
            <div class="text-center py-12 px-4 bg-surface-sunken/40 rounded-2xl border border-dashed border-border-strong/60">
                <div class="w-12 h-12 rounded-2xl bg-surface-raised text-ink-tertiary flex items-center justify-center mx-auto mb-3 shadow-2xs border border-border">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-ink">No signatures yet</p>
                <p class="mt-1 text-xs text-ink-tertiary max-w-sm mx-auto">Create customized signatures to automatically append your professional details to outgoing emails.</p>
                <button
                    wire:click="openCreateModal"
                    class="mt-4 inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-primary hover:text-primary-hover bg-primary-subtle rounded-xl border border-primary/20 transition-colors cursor-pointer"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create First Signature
                </button>
            </div>
        @else
            <div class="space-y-3">
                @foreach($signatures as $signature)
                    <div class="flex items-center justify-between p-4 rounded-xl border border-border bg-surface hover:bg-hover hover:border-primary/40 transition-all shadow-2xs">
                        <div class="flex items-center gap-3.5 min-w-0">
                            <div class="w-10 h-10 bg-primary/10 text-primary rounded-xl flex items-center justify-center shrink-0 ring-1 ring-primary/20">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-sm font-semibold text-ink truncate">{{ $signature->name }}</p>
                                    @if($signature->is_default)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-primary-subtle text-primary border border-primary/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-primary"></span>
                                            Default
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-ink-tertiary truncate mt-0.5">
                                    {{ strip_tags($signature->content_html ?? 'Empty signature') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @unless($signature->is_default)
                                <button
                                    wire:click="setDefaultSignature({{ $signature->id }})"
                                    class="px-2.5 py-1 text-xs font-semibold rounded-lg text-primary hover:bg-primary-subtle border border-transparent hover:border-primary/20 transition-colors cursor-pointer"
                                    title="Set as default"
                                >
                                    Set Default
                                </button>
                            @endunless
                            <button
                                wire:click="openEditModal({{ $signature->id }})"
                                class="p-1.5 rounded-lg text-ink-tertiary hover:text-primary hover:bg-hover transition-colors cursor-pointer"
                                title="Edit signature"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button
                                wire:click="deleteSignature({{ $signature->id }})"
                                wire:confirm="Are you sure you want to delete this signature?"
                                class="p-1.5 rounded-lg text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors cursor-pointer"
                                title="Delete signature"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
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
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:p-0">
                {{-- Backdrop --}}
                <div
                    class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
                    wire:click="closeModal"
                ></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-surface border border-border rounded-2xl max-w-2xl w-full shadow-2xl transform transition-all sm:my-8 sm:align-middle overflow-hidden text-left z-10">
                    <div class="px-6 py-4 border-b border-border-subtle flex items-center justify-between">
                        <h3 class="text-base font-bold text-ink" id="modal-title">
                            {{ $modalTitle }}
                        </h3>
                        <button
                            wire:click="closeModal"
                            class="p-1 rounded-lg text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 space-y-4">
                        {{-- Name --}}
                        <div>
                            <label for="sig-name" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">
                                Signature Name
                            </label>
                            <input
                                type="text"
                                id="sig-name"
                                wire:model="name"
                                class="settings-input text-sm"
                                placeholder="e.g., Work, Personal, Executive"
                                maxlength="100"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tiptap Editor --}}
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">
                                Signature Content
                            </label>
                            <div class="rounded-xl border border-border overflow-hidden bg-surface">
                                <x-tiptap-editor
                                    :content-json="$contentJson"
                                    wire:model="contentJson"
                                    wire:change="updateContentJson($event.target.value)"
                                    placeholder="Type your signature..."
                                    :min-height="'150px'"
                                />
                            </div>
                        </div>

                        {{-- Default toggle --}}
                        <div class="flex items-center gap-2.5 pt-1">
                            <input
                                type="checkbox"
                                id="sig-default"
                                wire:model="isDefault"
                                class="h-4 w-4 text-primary border-border rounded focus:ring-primary cursor-pointer"
                            >
                            <label for="sig-default" class="text-xs font-medium text-ink cursor-pointer">
                                Set as default signature for outgoing emails
                            </label>
                        </div>
                    </div>

                    <div class="px-6 py-4 bg-surface-sunken/40 border-t border-border-subtle flex items-center justify-end gap-3">
                        <button
                            wire:click="closeModal"
                            class="px-4 py-2 text-xs font-semibold text-ink bg-surface hover:bg-hover border border-border rounded-xl transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="saveSignature"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-2 px-5 py-2 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-sm transition-all active:scale-95 disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="saveSignature">
                                {{ $editingSignatureId ? 'Update Signature' : 'Create Signature' }}
                            </span>
                            <span wire:loading wire:target="saveSignature" class="flex items-center gap-1.5">
                                <svg class="animate-spin h-3.5 w-3.5 text-white" viewBox="0 0 24 24" fill="none">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                                Saving...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>