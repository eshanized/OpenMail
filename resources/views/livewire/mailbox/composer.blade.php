<div
    x-data="{
        open: false,
        showCc: false,
        showBcc: false,
        autosaveTimer: null,
        imapSyncTimer: null,
        tiptapEditor: null,
        tiptapToolbar: null,
        init() {
            this.$watch('$wire.isOpen', (value) => {
                this.open = value;
                if (value) {
                    document.body.style.overflow = 'hidden';
                    this.$nextTick(() => {
                        this.$refs.toInput?.focus();
                        this.initTiptap();
                    });
                } else {
                    document.body.style.overflow = '';
                    this.destroyTiptap();
                }
            });
            this.$watch('$wire.showCc', (value) => { this.showCc = value; });
            this.$watch('$wire.showBcc', (value) => { this.showBcc = value; });
            this.$watch('$wire.autosaveStatus', (value) => { this.autosaveStatus = value; });

            // Listen for autosave events from Livewire
            Livewire.on('autosave-draft', (data) => {
                this.saveToLocalStorage(data);
            });

            // Listen for undo-send toast
            Livewire.on('undo-send', (data) => {
                this.showUndoToast(data.delay);
            });

            // Listen for clear draft storage
            Livewire.on('clear-draft-storage', (data) => {
                localStorage.removeItem('openmail:draft:' + data.compositionId);
            });

            // Load draft from LocalStorage on mount
            this.loadFromLocalStorage();

            // Handle keyboard shortcuts
            document.addEventListener('keydown', this.handleKeydown.bind(this));
        },
        destroy() {
            this.destroyTiptap();
            if (this.autosaveTimer) clearTimeout(this.autosaveTimer);
            if (this.imapSyncTimer) clearInterval(this.imapSyncTimer);
            document.removeEventListener('keydown', this.handleKeydown);
        },
        handleKeydown(event) {
            if (!this.open) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                this.closeWithConfirm();
            }
            if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
                event.preventDefault();
                this.$wire.send();
            }
            if ((event.metaKey || event.ctrlKey) && event.key === 's') {
                event.preventDefault();
                this.$wire.saveDraft();
            }
        },
        async initTiptap() {
            const { initTiptapEditor, createToolbar } = await import('../../js/components/TiptapEditor.js');
            const editorEl = this.$refs.editor;
            const toolbarEl = this.$refs.toolbar;

            if (!editorEl || !toolbarEl) return;

            this.tiptapEditor = await initTiptapEditor(editorEl, {
                onUpdate: (html) => {
                    this.$wire.syncBodyFromEditor(html);
                    this.triggerAutosave();
                },
                initialContent: @js($bodyHtml ?: $body),
            });

            this.tiptapToolbar = createToolbar(this.tiptapEditor.editor, toolbarEl);
        },
        destroyTiptap() {
            if (this.tiptapEditor) {
                this.tiptapEditor.destroy();
                this.tiptapEditor = null;
            }
            if (this.tiptapToolbar) {
                this.tiptapToolbar.destroy();
                this.tiptapToolbar = null;
            }
        },
        triggerAutosave() {
            if (this.autosaveTimer) clearTimeout(this.autosaveTimer);
            this.autosaveTimer = setTimeout(() => {
                this.$dispatch('autosave-draft', {
                    compositionId: @js($compositionId),
                    to: @entangle('to').defer,
                    cc: @entangle('cc').defer,
                    bcc: @entangle('bcc').defer,
                    subject: @entangle('subject').defer,
                    body: @entangle('bodyHtml').defer,
                    mode: @js($mode),
                    replyToMessage: @js($replyToMessage),
                });
            }, 1500);
        },
        saveToLocalStorage(data) {
            const key = 'openmail:draft:' + data.compositionId;
            localStorage.setItem(key, JSON.stringify({
                ...data,
                savedAt: Date.now(),
            }));
            this.$wire.autosaveStatus = 'saved';
            this.$wire.lastSavedAt = new Date().toISOString();

            // Start IMAP sync interval if not already running
            if (!this.imapSyncTimer) {
                this.imapSyncTimer = setInterval(() => {
                    this.$wire.syncDraftToImap();
                }, 30000); // Every 30 seconds
            }
        },
        loadFromLocalStorage() {
            const key = 'openmail:draft:' + @js($compositionId);
            const stored = localStorage.getItem(key);
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    // Check if draft is recent (within 24 hours)
                    if (Date.now() - data.savedAt < 24 * 60 * 60 * 1000) {
                        this.$wire.loadDraftFromLocalStorage(data);
                    }
                } catch (e) {
                    console.warn('Failed to load draft from LocalStorage:', e);
                }
            }
        },
        showUndoToast(delay) {
            this.$wire.undoSendDelay = delay;
            this.$wire.showUndoToast = true;
        },
        closeWithConfirm() {
            if (this.tiptapEditor && this.tiptapEditor.getHTML().trim()) {
                if (confirm('This draft has unsaved changes. Are you sure you want to discard it?')) {
                    this.$wire.discardDraft();
                }
            } else {
                this.$wire.discardDraft();
            }
        },
    }"
    x-show="open"
    class="fixed inset-0 z-40"
    @keydown.window="handleKeydown($event)"
    wire:ignore.self
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-black/50 transition-opacity"
        @click="closeWithConfirm()"
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div
        class="fixed inset-0 flex items-center justify-center p-4"
        @click.outside="closeWithConfirm()"
    >
        <div
            class="bg-surface-overlay rounded-xl border border-border shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden"
            @click.outside.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-border">
                <h2 class="text-base font-semibold text-ink">
                    @if($mode === 'compose')
                        New Message
                    @elseif($mode === 'reply')
                        Reply
                    @elseif($mode === 'replyAll')
                        Reply All
                    @elseif($mode === 'forward')
                        Forward
                    @else
                        New Message
                    @endif
                </h2>
                <div class="flex items-center gap-2">
                    <button
                        wire:click="saveDraft"
                        data-loading.attr="disabled"
                        class="px-3 py-1.5 text-xs font-medium text-ink-secondary bg-surface border border-border rounded-lg hover:bg-hover transition-colors disabled:opacity-50"
                    >
                        Save Draft
                    </button>
                    <button
                        wire:click="discardDraft"
                        class="px-3 py-1.5 text-xs font-medium text-error bg-error-subtle border border-error/20 rounded-lg hover:bg-error/10 transition-colors"
                        onclick="return confirm('This draft has unsaved changes. Are you sure you want to discard it?')"
                    >
                        Discard
                    </button>
                    <button
                        wire:click="send"
                        data-loading.attr="disabled"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary hover:bg-primary-hover
                               px-4 py-1.5 text-xs font-semibold text-white
                               shadow-sm hover:shadow-md
                               transition-all duration-150
                               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
                               focus-visible:ring-primary
                               active:scale-[0.98]
                               disabled:opacity-50"
                    >
                        <span data-loading.remove>Send</span>
                        <span data-loading class="flex items-center gap-1">
                            <svg class="spin-animation h-3.5 w-3.5" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                            </svg>
                            Sending...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            <form class="flex-1 overflow-y-auto p-6" wire:submit.prevent="send">
                {{-- Recipient Fields --}}
                <div class="space-y-3 mb-5">
                    {{-- To Field --}}
                    <div>
                        <x-composer-recipient-chips field="to" :value="$to" label="To" :show-suggestions="true" />
                    </div>

                    {{-- CC Field --}}
                    <div x-show="showCc" x-transition>
                        <x-composer-recipient-chips field="cc" :value="$cc" label="Cc" :show-suggestions="true" />
                    </div>

                    {{-- BCC Field --}}
                    <div x-show="showBcc" x-transition>
                        <x-composer-recipient-chips field="bcc" :value="$bcc" label="Bcc" :show-suggestions="true" />
                    </div>

                    {{-- CC/BCC Toggle Buttons --}}
                    <div class="flex gap-3">
                        <button type="button" @click="showCc = !showCc"
                                class="text-xs text-primary hover:text-primary-hover font-medium flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span x-text="showCc ? 'Hide Cc' : 'Add Cc'">Add Cc</span>
                        </button>
                        <button type="button" @click="showBcc = !showBcc"
                                class="text-xs text-primary hover:text-primary-hover font-medium flex items-center gap-1 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                            </svg>
                            <span x-text="showBcc ? 'Hide Bcc' : 'Add Bcc'">Add Bcc</span>
                        </button>
                    </div>
                </div>

                {{-- Subject --}}
                <div class="mb-5">
                    <label for="composer-subject" class="block text-xs font-medium text-ink-secondary mb-1.5">Subject</label>
                    <input
                        type="text"
                        id="composer-subject"
                        wire:model="subject"
                        class="w-full px-3 py-2 bg-surface border border-border rounded-lg text-sm text-ink placeholder-ink-tertiary
                               focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                        placeholder="(no subject)"
                    >
                    @error('subject')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Body --}}
                <div class="mb-5">
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="composer-body" class="block text-xs font-medium text-ink-secondary">Message</label>
                        @if($mode === 'compose')
                            <x-signature-dropdown
                                :signatures="$signatures ?? collect()"
                                :default-signature="$defaultSignature"
                            />
                        @endif
                    </div>
                    <div class="border border-border rounded-lg overflow-hidden">
                        <div x-ref="toolbar" class="tiptap-toolbar-container"></div>
                        <div x-ref="editor" class="tiptap-editor"></div>
                        <textarea
                            id="composer-body"
                            wire:model="bodyHtml"
                            class="hidden"
                            aria-hidden="true"
                        ></textarea>
                    </div>
                    @error('body')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Autosave Status --}}
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-xs text-ink-tertiary" x-data="{ status: @entangle('autosaveStatus') }">
                        <span x-show="status === 'saving'" class="flex items-center gap-1">
                            <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Saving...
                        </span>
                        <span x-show="status === 'saved'">Draft saved @if($lastSavedAt) {{ \Carbon\Carbon::parse($lastSavedAt)->diffForHumans() }} @endif</span>
                        <span x-show="status === 'error'" class="text-error">Save failed</span>
                    </span>
                </div>

                {{-- Attachments --}}
                <div class="mb-5">
                    <label class="block text-xs font-medium text-ink-secondary mb-2">Attachments</label>
                    <div
                        class="border border-dashed border-border hover:border-primary/40 rounded-lg p-6 text-center transition-colors cursor-pointer relative"
                        wire:loading.class="opacity-50"
                        x-data="{ dragOver: false }"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; handleDrop($event)"
                        :class="{ 'border-primary bg-primary-subtle/30': dragOver }"
                    >
                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            @change="$wire.updatedAttachments()"
                        >
                        <div class="flex flex-col items-center gap-1.5">
                            <svg class="w-8 h-8 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                            </svg>
                            <p class="text-xs text-ink-secondary">Drag files here or click to browse</p>
                            <p class="text-[10px] text-ink-tertiary">Max 25MB per file, 50MB total</p>
                        </div>
                        <script>
                            function handleDrop(event) { event.preventDefault(); }
                        </script>
                    </div>

                    @if(count($attachments) > 0)
                        <div class="mt-3 space-y-1.5">
                            @foreach($attachments as $index => $attachment)
                                <div class="flex items-center gap-2.5 p-2.5 bg-surface-sunken rounded-lg border border-border-subtle">
                                    @php
                                        $mime = $attachment->getMimeType();
                                        $isImage = str_starts_with($mime, 'image/');
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="{{ $attachment->getClientOriginalName() }}"
                                             class="w-10 h-10 object-cover rounded-md">
                                    @else
                                        <div class="w-10 h-10 bg-primary-subtle rounded-md flex items-center justify-center">
                                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-ink truncate">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="text-[11px] text-ink-tertiary">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeAttachment({{ $index }})"
                                        class="p-1 text-ink-tertiary hover:text-error rounded transition-colors"
                                        aria-label="Remove {{ $attachment->getClientOriginalName() }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('attachments')
                        <p class="mt-2 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reply/Forward Quoted Message --}}
                @if($mode !== 'compose' && $replyToMessage)
                    <x-composer-quote
                        :attribution="'On ' . ($replyToMessage['date_formatted'] ?? $replyToMessage['date'] ?? '') . ', ' . ($replyToMessage['from_name'] ?? '') . ' <' . ($replyToMessage['from_email'] ?? '') . '> wrote:'"
                        :quoted-html="$replyToMessage['html_body'] ?? nl2br(e($replyToMessage['text_body'] ?? ''))"
                        :uid="'composer-quote-' . $compositionId"
                    />
                @endif
            </form>
        </div>
    </div>

    {{-- Undo Send Toast --}}
    @if($showUndoToast)
        <x-undo-send-toast :delay="$undoSendDelay" :pending-send-id="$pendingSendId" />
    @endif

    @push('scripts')
        <script>
            document.addEventListener('livewire:load', () => {
                Livewire.on('toast', (message, type = 'info') => {
                    window.dispatchEvent(new CustomEvent('openmail-toast', {
                        detail: { message, type }
                    }));
                });

                Livewire.on('composer-sent', () => {
                    localStorage.removeItem('openmail:draft:' + @js($compositionId));
                });
            });
        </script>
    @endpush
</div>