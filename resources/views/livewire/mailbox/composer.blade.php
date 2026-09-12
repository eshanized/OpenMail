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
        send() {
            window.dispatchEvent(new CustomEvent('composer-commit-recipients'));
            if (this.tiptapEditor) {
                this.$wire.syncBodyFromEditor(this.tiptapEditor.getHTML());
            }
            this.$wire.send();
        },
        handleKeydown(event) {
            if (!this.open) return;
            if (event.key === 'Escape') {
                event.preventDefault();
                this.closeWithConfirm();
            }
            if ((event.metaKey || event.ctrlKey) && event.key === 'Enter') {
                event.preventDefault();
                this.send();
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
        class="fixed inset-0 bg-black/40 transition-opacity"
        @click="closeWithConfirm()"
        aria-hidden="true"
    ></div>

    {{-- Modal Container --}}
    <div
        class="fixed inset-0 flex items-center justify-center p-3 sm:p-5"
        @click.outside="closeWithConfirm()"
    >
        <div
            class="bg-surface-raised rounded border border-border shadow-xl max-w-4xl w-full max-h-[92vh] flex flex-col overflow-hidden animate-fade-in"
            @click.outside.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-5 py-3 border-b border-border bg-surface-raised">
                <div class="flex items-center gap-3 min-w-0">
                    <h2 class="text-sm font-semibold text-ink truncate">
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

                    {{-- Autosave Status --}}
                    <span class="text-[11px] text-ink-tertiary" x-data="{ status: @entangle('autosaveStatus') }">
                        <span x-show="status === 'saving'" class="text-primary">Saving…</span>
                        <span x-show="status === 'saved'">Draft saved</span>
                        <span x-show="status === 'error'" class="text-error">Save failed</span>
                        <span x-show="status === 'idle'">Draft</span>
                    </span>
                </div>

                {{-- Header Actions --}}
                <div class="flex items-center gap-2">
                    <button
                        wire:click="saveDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveDraft"
                        class="hidden sm:inline-flex items-center gap-1 px-2.5 py-1 text-xs text-ink-secondary hover:text-ink border border-border rounded hover:bg-hover transition-colors cursor-pointer disabled:opacity-50"
                        title="Save Draft (Ctrl+S)"
                    >
                        <span wire:loading.remove wire:target="saveDraft">Save</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
                    </button>
                    <button
                        wire:click="discardDraft"
                        class="px-2 py-1 text-xs text-ink-tertiary hover:text-error transition-colors cursor-pointer"
                        onclick="return confirm('Discard this draft?')"
                        title="Discard draft"
                    >
                        Discard
                    </button>
                    <button
                        type="button"
                        @click="closeWithConfirm()"
                        class="p-1 text-ink-tertiary hover:text-ink rounded hover:bg-hover transition-colors cursor-pointer"
                        title="Close (Esc)"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Form Body --}}
            <form class="flex-1 overflow-y-auto p-5 space-y-3" @submit.prevent="send()">
                {{-- Recipient Fields --}}
                <div class="space-y-2">
                    {{-- To Field + CC/BCC Toggles --}}
                    <div class="flex flex-col sm:flex-row sm:items-end gap-2">
                        <div class="flex-1 min-w-0">
                            <x-composer-recipient-chips field="to" :value="$to" label="To" :show-suggestions="true" />
                        </div>
                        <div class="flex items-center gap-1.5 pb-0.5 shrink-0">
                            <button
                                type="button"
                                @click="showCc = !showCc"
                                class="px-2 py-0.5 text-xs rounded border transition-colors cursor-pointer"
                                :class="showCc ? 'bg-primary-subtle text-primary border-primary/25 font-medium' : 'border-border text-ink-tertiary hover:text-ink hover:bg-hover'"
                            >
                                <span x-text="showCc ? '− Cc' : '+ Cc'">+ Cc</span>
                            </button>
                            <button
                                type="button"
                                @click="showBcc = !showBcc"
                                class="px-2 py-0.5 text-xs rounded border transition-colors cursor-pointer"
                                :class="showBcc ? 'bg-primary-subtle text-primary border-primary/25 font-medium' : 'border-border text-ink-tertiary hover:text-ink hover:bg-hover'"
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
                    <label for="composer-subject" class="block text-xs font-medium text-ink-secondary mb-1">Subject</label>
                    <input
                        type="text"
                        id="composer-subject"
                        wire:model="subject"
                        class="w-full px-3 py-1.5 bg-surface-raised border border-border-strong rounded text-sm text-ink placeholder-ink-tertiary focus:outline-none focus:ring-1 focus:ring-primary focus:border-primary transition-colors"
                        placeholder="Subject"
                    >
                    @error('subject')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Body & Editor --}}
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="composer-body" class="block text-xs font-medium text-ink-secondary">Message</label>
                        @if($mode === 'compose')
                            <x-signature-dropdown
                                :signatures="$signatures ?? collect()"
                                :default-signature="$defaultSignature"
                            />
                        @endif
                    </div>
                    <div class="border border-border rounded overflow-hidden bg-surface-raised flex flex-col focus-within:border-primary transition-colors" wire:ignore>
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
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-medium text-ink-secondary">Attachments</label>
                        <span class="text-[11px] text-ink-tertiary">Max 25MB per file, 50MB total</span>
                    </div>

                    <div
                        class="border border-dashed border-border hover:border-border-strong rounded p-3 text-center transition-colors cursor-pointer relative bg-surface-sunken"
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
                        <div class="flex items-center justify-center gap-2 py-0.5 text-xs text-ink-tertiary">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/>
                            </svg>
                            <span class="text-ink-secondary font-medium">Attach files</span>
                            <span>or drag and drop here</span>
                        </div>
                        <script>
                            function handleDrop(event) { event.preventDefault(); }
                        </script>
                    </div>

                    @if(count($attachments) > 0)
                        <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach($attachments as $index => $attachment)
                                <div class="flex items-center gap-2 p-2 bg-surface-sunken rounded border border-border">
                                    @php
                                        $mime = $attachment->getMimeType();
                                        $isImage = str_starts_with($mime, 'image/');
                                    @endphp
                                    @if($isImage)
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="{{ $attachment->getClientOriginalName() }}"
                                             class="w-8 h-8 object-cover rounded border border-border shrink-0">
                                    @else
                                        <div class="w-8 h-8 bg-surface-raised text-ink-secondary border border-border rounded flex items-center justify-center shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                            </svg>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <p class="text-xs font-medium text-ink truncate">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="text-[10px] text-ink-tertiary font-mono">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                    </div>
                                    <button
                                        type="button"
                                        wire:click="removeAttachment({{ $index }})"
                                        class="p-1 rounded text-ink-tertiary hover:text-error transition-colors cursor-pointer shrink-0"
                                        aria-label="Remove {{ $attachment->getClientOriginalName() }}"
                                    >
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('attachments')
                        <p class="mt-1 text-xs text-error">{{ $message }}</p>
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
            <div class="flex items-center justify-between px-5 py-3 bg-surface-sunken border-t border-border">
                <div class="flex items-center gap-2.5">
                    {{-- Primary Send Button --}}
                    <button
                        type="button"
                        @click="send()"
                        wire:loading.attr="disabled"
                        wire:target="send"
                        class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded bg-primary hover:bg-primary-hover text-white text-xs font-medium transition-colors disabled:opacity-50 cursor-pointer"
                    >
                        <span wire:loading.remove wire:target="send" class="inline-flex items-center gap-1.5">
                            <span>Send</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
                            </svg>
                        </span>
                        <span wire:loading wire:target="send" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                            <span>Sending…</span>
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
                        class="px-2.5 py-1.5 text-xs text-ink-secondary hover:text-ink border border-border rounded hover:bg-hover transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <span wire:loading.remove wire:target="saveDraft">Save draft</span>
                        <span wire:loading wire:target="saveDraft">Saving…</span>
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
            document.addEventListener('livewire:init', () => {
                Livewire.on('toast', (message, type = 'info') => {
                    let msg = typeof message === 'string' ? message : (message.message || message[0]);
                    let t = typeof message === 'object' && message.type ? message.type : (type || message[1] || 'info');
                    window.dispatchEvent(new CustomEvent('openmail-toast', {
                        detail: { message: msg, type: t }
                    }));
                });

                Livewire.on('composer-sent', () => {
                    localStorage.removeItem('openmail:draft:' + @js($compositionId));
                });
            });
        </script>
    @endpush
</div>