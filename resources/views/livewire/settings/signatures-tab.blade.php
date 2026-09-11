<div class="space-y-6">
    <section>
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-xs font-semibold text-ink uppercase tracking-wider">Signatures</h3>
                <p class="text-xs text-ink-tertiary mt-0.5">Email signatures automatically appended to outgoing messages.</p>
            </div>
            <button
                wire:click="openCreateModal"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>New Signature</span>
            </button>
        </div>

        @if($signatures->isEmpty())
            <div class="text-center py-10 px-4 bg-surface-sunken rounded border border-dashed border-border">
                <p class="text-xs font-medium text-ink">No signatures yet</p>
                <p class="mt-0.5 text-xs text-ink-tertiary max-w-sm mx-auto">Create signatures to automatically append your details to outgoing emails.</p>
                <button
                    wire:click="openCreateModal"
                    class="mt-3 inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-primary hover:text-primary-hover border border-primary/30 rounded bg-primary-subtle transition-colors cursor-pointer"
                >
                    Create signature
                </button>
            </div>
        @else
            <div class="space-y-2">
                @foreach($signatures as $signature)
                    <div class="flex items-center justify-between p-3 rounded border border-border bg-surface-raised hover:bg-hover transition-colors">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <p class="text-xs font-semibold text-ink truncate">{{ $signature->name }}</p>
                                    @if($signature->is_default)
                                        <span class="text-[10px] font-medium text-primary bg-primary-subtle border border-primary/20 px-1.5 py-0.2 rounded">
                                            Default
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-ink-tertiary truncate mt-0.5">
                                    {{ strip_tags($signature->content_html ?? 'Empty signature') }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            @unless($signature->is_default)
                                <button
                                    wire:click="setDefaultSignature({{ $signature->id }})"
                                    class="px-2 py-1 text-xs text-ink-secondary hover:text-ink border border-border rounded transition-colors cursor-pointer"
                                    title="Set as default"
                                >
                                    Make Default
                                </button>
                            @endunless
                            <button
                                wire:click="openEditModal({{ $signature->id }})"
                                class="p-1 rounded text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                                title="Edit signature"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            <button
                                wire:click="deleteSignature({{ $signature->id }})"
                                wire:confirm="Are you sure you want to delete this signature?"
                                class="p-1 rounded text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors cursor-pointer"
                                title="Delete signature"
                            >
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
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
                    class="fixed inset-0 bg-black/40 transition-opacity"
                    wire:click="closeModal"
                ></div>

                {{-- Modal panel --}}
                <div class="inline-block align-bottom bg-surface-raised border border-border rounded max-w-2xl w-full shadow-xl transform transition-all sm:my-8 sm:align-middle overflow-hidden text-left z-10">
                    <div class="px-5 py-3.5 border-b border-border flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-ink" id="modal-title">
                            {{ $modalTitle }}
                        </h3>
                        <button
                            wire:click="closeModal"
                            class="p-1 rounded text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <div class="px-5 py-4 space-y-3">
                        {{-- Name --}}
                        <div>
                            <label for="sig-name" class="block text-xs font-medium text-ink-secondary mb-1">
                                Signature Name
                            </label>
                            <input
                                type="text"
                                id="sig-name"
                                wire:model="name"
                                class="settings-input text-xs"
                                placeholder="e.g. Work, Personal"
                                maxlength="100"
                            >
                            @error('name')
                                <p class="mt-1 text-xs text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tiptap Editor --}}
                        <div>
                            <label class="block text-xs font-medium text-ink-secondary mb-1">
                                Content
                            </label>
                            <div class="rounded border border-border overflow-hidden bg-surface-raised">
                                <x-tiptap-editor
                                    :content-json="$contentJson"
                                    wire:model="contentJson"
                                    wire:change="updateContentJson($event.target.value)"
                                    placeholder="Type your signature..."
                                    :min-height="'140px'"
                                />
                            </div>
                        </div>

                        {{-- Default toggle --}}
                        <div class="flex items-center gap-2 pt-1">
                            <input
                                type="checkbox"
                                id="sig-default"
                                wire:model="isDefault"
                                class="mail-checkbox cursor-pointer"
                            >
                            <label for="sig-default" class="text-xs text-ink cursor-pointer">
                                Set as default signature
                            </label>
                        </div>
                    </div>

                    <div class="px-5 py-3 bg-surface-sunken border-t border-border flex items-center justify-end gap-2">
                        <button
                            wire:click="closeModal"
                            class="px-3 py-1.5 text-xs text-ink-secondary hover:text-ink border border-border rounded bg-surface-raised transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            wire:click="saveSignature"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer">
                            <span wire:loading.remove wire:target="saveSignature">
                                {{ $editingSignatureId ? 'Save Changes' : 'Create Signature' }}
                            </span>
                            <span wire:loading wire:target="saveSignature">Saving…</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>