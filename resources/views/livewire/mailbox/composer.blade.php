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
            const initTiptap = window.initTiptapEditor || (await import('../../js/components/TiptapEditor.js')).initTiptapEditor;
            const createTb = window.createTiptapToolbar || (await import('../../js/components/TiptapEditor.js')).createToolbar;
            const editorEl = this.$refs.editor;
            const toolbarEl = this.$refs.toolbar;

            if (!editorEl || !toolbarEl) return;

            this.tiptapEditor = await initTiptap(editorEl, {
                onUpdate: (html) => {
                    this.$wire.syncBodyFromEditor(html);
                    this.triggerAutosave();
                },
                initialContent: @js($bodyHtml ?: $body),
            });

            this.tiptapToolbar = createTb(this.tiptapEditor.editor, toolbarEl);
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
    x-cloak
    class="fixed inset-0 z-40"
    @keydown.window="handleKeydown($event)"
    wire:ignore.self
>
    {{-- Backdrop --}}
    <div
        class="fixed inset-0 bg-black/60 backdrop-blur-xs transition-opacity"
        @click="closeWithConfirm()"
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div
        class="fixed inset-0 flex items-center justify-center p-3 sm:p-6"
        @click.outside="closeWithConfirm()"
    >
        <div
            class="bg-surface-raised rounded-2xl border border-border shadow-2xl max-w-4xl w-full max-h-[92vh] flex flex-col overflow-hidden animate-fade-in"
            @click.outside.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-border bg-surface">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-primary/10 text-primary flex items-center justify-center shrink-0 ring-1 ring-primary/20">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h2 class="text-sm sm:text-base font-bold text-ink truncate tracking-tight">
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
                    </div>

                    {{-- Autosave Status Badge --}}
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-medium bg-surface-sunken border border-border-subtle text-ink-tertiary shadow-2xs shrink-0" x-data="{ status: @entangle('autosaveStatus') }">
                        <span x-show="status === 'saving'" class="flex items-center gap-1 text-primary font-medium">
                            <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                            Saving...
                        </span>
                        <span x-show="status === 'saved'" class="text-ink-secondary">Draft saved</span>
                        <span x-show="status === 'error'" class="text-error font-medium">Save failed</span>
                        <span x-show="status === 'idle'" class="text-ink-tertiary">Draft</span>
                    </span>
                </div>

                {{-- Header Actions --}}
                <div class="flex items-center gap-2">
                    <button
                        wire:click="saveDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveDraft"
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-ink-secondary hover:text-ink bg-surface border border-border rounded-xl hover:bg-hover transition-colors cursor-pointer disabled:opacity-50"
                        title="Save Draft (Ctrl+S)"
                    >
                        <svg class="w-3.5 h-3.5 text-ink-tertiary" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0111.186 0z"/>
                        </svg>
                        <span wire:loading.remove wire:target="saveDraft">Save Draft</span>
                        <span wire:loading wire:target="saveDraft">Saving...</span>
                    </button>
                    <button
                        wire:click="discardDraft"
                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-error hover:bg-error-subtle rounded-xl transition-colors cursor-pointer"
                        onclick="return confirm('This draft has unsaved changes. Are you sure you want to discard it?')"
                        title="Discard draft"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                        <span class="hidden sm:inline">Discard</span>
                    </button>
                    <button
                        type="button"
                        @click="closeWithConfirm()"
                        class="p-1.5 rounded-xl text-ink-tertiary hover:text-ink hover:bg-hover transition-colors cursor-pointer"
                        title="Close (Esc)"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Form Body --}}
            <form class="flex-1 overflow-y-auto p-5 sm:p-6 space-y-4" wire:submit.prevent="send">
                {{-- Recipient Fields --}}
                <div class="space-y-3">
                    {{-- To Field + CC/BCC Toggles --}}
                    <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                        <div class="flex-1 min-w-0">
                            <x-composer-recipient-chips field="to" :value="$to" label="To" :show-suggestions="true" />
                        </div>
                        <div class="flex items-center gap-2 pb-1 shrink-0">
                            <button
                                type="button"
                                @click="showCc = !showCc"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-colors cursor-pointer"
                                :class="showCc ? 'bg-primary-subtle text-primary border-primary/30' : 'bg-surface border-border text-ink-secondary hover:text-ink hover:bg-hover'"
                            >
                                <span x-text="showCc ? '− Cc' : '+ Cc'">+ Cc</span>
                            </button>
                            <button
                                type="button"
                                @click="showBcc = !showBcc"
                                class="px-2.5 py-1 text-xs font-semibold rounded-lg border transition-colors cursor-pointer"
                                :class="showBcc ? 'bg-primary-subtle text-primary border-primary/30' : 'bg-surface border-border text-ink-secondary hover:text-ink hover:bg-hover'"
                            >
                                <span x-text="showBcc ? '− Bcc' : '+ Bcc'">+ Bcc</span>
                            </button>
                        </div>
                    </div>

                    {{-- CC Field --}}
                    <div x-show="showCc" x-transition>
                        <x-composer-recipient-chips field="cc" :value="$cc" label="Cc" :show-suggestions="true" />
                    </div>

                    {{-- BCC Field --}}
                    <div x-show="showBcc" x-transition>
                        <x-composer-recipient-chips field="bcc" :value="$bcc" label="Bcc" :show-suggestions="true" />
                    </div>
                </div>

                {{-- Subject --}}
                <div>
                    <label for="composer-subject" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary mb-1.5">Subject</label>
                    <input
                        type="text"
                        id="composer-subject"
                        wire:model="subject"
                        class="w-full px-3.5 py-2.5 bg-surface border border-border rounded-xl text-sm font-medium text-ink placeholder-ink-tertiary focus:outline-none focus:ring-2 focus:ring-primary/25 focus:border-primary transition-all"
                        placeholder="Subject"
                    >
                    @error('subject')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Body & Editor --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="composer-body" class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary">Message</label>
                        @if($mode === 'compose')
                            <x-signature-dropdown
                                :signatures="$signatures ?? collect()"
                                :default-signature="$defaultSignature"
                            />
                        @endif
                    </div>
                    <div class="border border-border rounded-xl overflow-hidden bg-surface flex flex-col focus-within:ring-2 focus-within:ring-primary/25 focus-within:border-primary transition-all shadow-2xs" wire:ignore>
                        <div x-ref="toolbar" class="tiptap-toolbar-container border-b border-border"></div>
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

                {{-- Attachments Zone --}}
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold uppercase tracking-wider text-ink-secondary">Attachments</label>
                        <span class="text-[11px] text-ink-tertiary">Max 25MB per file, 50MB total</span>
                    </div>

                    <div
                        class="border-2 border-dashed border-border hover:border-primary/50 rounded-xl p-3.5 text-center transition-all cursor-pointer relative bg-surface-sunken/40 hover:bg-surface-sunken/70"
                        wire:loading.class="opacity-50"
                        x-data="{ dragOver: false }"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; handleDrop($event)"
                        :class="{ 'border-primary bg-primary-subtle/30 ring-2 ring-primary/20': dragOver }"
                    >
                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            @change="$wire.updatedAttachments()"
                        >
                        <div class="flex items-center justify-center gap-2.5 py-1">
                            <div class="w-8 h-8 rounded-lg bg-surface flex items-center justify-center text-primary shadow-2xs border border-border shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                                </svg>
                            </div>
                            <div class="text-left">
                                <p class="text-xs font-semibold text-ink">Drag files here or click to browse</p>
                                <p class="text-[10px] text-ink-tertiary">Supports images, PDF, documents, and archives</p>
                            </div>
                        </div>
                        <script>
                            function handleDrop(event) { event.preventDefault(); }
                        </script>
                    </div>

                    @if(count($attachments) > 0)
                        <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($attachments as $index => $attachment)
                                <div class="flex items-center gap-2.5 p-2.5 bg-surface rounded-xl border border-border shadow-2xs">
                                    @php
                                        $mime = $attachment->getMimeType();
                                        $isImage = str_starts_with($mime, 'image/');
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="{{ $attachment->getClientOriginalName() }}"
                                             class="w-9 h-9 object-cover rounded-lg border border-border shrink-0">
                                    @else
                                        <div class="w-9 h-9 bg-primary/10 text-primary rounded-lg flex items-center justify-center shrink-0 ring-1 ring-primary/20">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-semibold text-ink truncate">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-ink-tertiary font-mono">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeAttachment({{ $index }})"
                                        class="p-1 rounded-lg text-ink-tertiary hover:text-error hover:bg-error-subtle transition-colors cursor-pointer shrink-0"
                                        aria-label="Remove {{ $attachment->getClientOriginalName() }}"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
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

            {{-- Footer Action Bar --}}
            <div class="flex items-center justify-between px-6 py-3.5 bg-surface-sunken/50 border-t border-border">
                <div class="flex items-center gap-3">
                    {{-- Primary Send Button with proper wire:loading --}}
                    <button
                        type="button"
                        wire:click="send"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary hover:bg-primary-hover text-white text-xs font-semibold shadow-sm hover:shadow-md transition-all active:scale-95 disabled:opacity-50 cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="send" class="inline-flex items-center gap-1.5">
                            <span>Send</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="send" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Sending...</span>
                        </span>
                    </button>

                    <span class="hidden sm:inline-block text-[11px] text-ink-tertiary font-mono">
                        ⌘+Enter
                    </span>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        wire:click="saveDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveDraft"
                        class="px-3.5 py-2 text-xs font-semibold text-ink-secondary hover:text-ink bg-surface border border-border rounded-xl hover:bg-hover transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="saveDraft">Save Draft</span>
                        <span wire:loading wire:target="saveDraft">Saving...</span>
                    </button>
                    <button
                        type="button"
                        wire:click="discardDraft"
                        class="p-2 text-ink-tertiary hover:text-error hover:bg-error-subtle rounded-xl transition-colors cursor-pointer"
                        onclick="return confirm('This draft has unsaved changes. Are you sure you want to discard it?')"
                        title="Discard draft"
                    >
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                        </svg>
                    </button>
                </div>
            </div>
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