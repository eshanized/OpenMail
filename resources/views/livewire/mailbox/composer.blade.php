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
            class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden"
            @click.outside.stop
        >
            {{-- Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h2 class="text-xl font-semibold text-gray-900">
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
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors disabled:opacity-50"
                    >
                        Save Draft
                    </button>
                    <button
                        wire:click="discardDraft"
                        class="px-4 py-2 text-sm font-medium text-red-600 bg-white border border-gray-300 rounded-md hover:bg-red-50 transition-colors"
                        onclick="return confirm('This draft has unsaved changes. Are you sure you want to discard it?')"
                    >
                        Discard
                    </button>
                    <button
                        wire:click="send"
                        wire:loading.attr="disabled"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 transition-colors disabled:opacity-50 flex items-center gap-2"
                    >
                        <span wire:loading.remove>Send Message</span>
                        <span wire:loading class="flex items-center gap-1">
                            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Sending...
                        </span>
                    </button>
                </div>
            </div>

            {{-- Form --}}
            <form class="flex-1 overflow-y-auto p-6" wire:submit.prevent="send">
                {{-- Recipient Fields --}}
                <div class="space-y-4 mb-6">
                    {{-- To Field --}}
                    <div>
                        <label for="composer-to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                        <input
                            type="email"
                            id="composer-to"
                            wire:model="to"
                            wire:model.debounce.300ms="to"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="name@example.com"
                            x-ref="toInput"
                            autocomplete="email"
                        >
                        @error('to')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CC Field (collapsible) --}}
                    <div x-show="showCc" x-transition>
                        <label for="composer-cc" class="block text-sm font-medium text-gray-700 mb-1">Cc</label>
                        <input
                            type="email"
                            id="composer-cc"
                            wire:model="cc"
                            wire:model.debounce.300ms="cc"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="name@example.com"
                            autocomplete="email"
                        >
                    </div>

                    {{-- BCC Field (collapsible) --}}
                    <div x-show="showBcc" x-transition>
                        <label for="composer-bcc" class="block text-sm font-medium text-gray-700 mb-1">Bcc</label>
                        <input
                            type="email"
                            id="composer-bcc"
                            wire:model="bcc"
                            wire:model.debounce.300ms="bcc"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                            placeholder="name@example.com"
                            autocomplete="email"
                        >
                    </div>

                    {{-- CC/BCC Toggle Buttons --}}
                    <div class="flex gap-2">
                        <button
                            type="button"
                            @click="showCc = !showCc"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                        >
                            <span x-show="!showCc">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Cc
                            </span>
                            <span x-show="showCc">
                                <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Cc
                            </span>
                        </button>
                        <button
                            type="button"
                            @click="showBcc = !showBcc"
                            class="text-sm text-blue-600 hover:text-blue-800 font-medium flex items-center gap-1"
                        >
                            <span x-show="!showBcc">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Bcc
                            </span>
                            <span x-show="showBcc">
                                <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Bcc
                            </span>
                        </button>
                    </div>
                </div>

                {{-- Subject Field --}}
                <div class="mb-6">
                    <label for="composer-subject" class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <input
                        type="text"
                        id="composer-subject"
                        wire:model="subject"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                        placeholder="(no subject)"
                    >
                    @error('subject')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Body with Tiptap Editor --}}
                <div class="mb-6">
                    <label for="composer-body" class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <div class="border border-gray-300 rounded-md overflow-hidden">
                        {{-- Toolbar --}}
                        <div x-ref="toolbar" class="tiptap-toolbar-container"></div>
                        {{-- Editor --}}
                        <div x-ref="editor" class="tiptap-editor"></div>
                        {{-- Hidden textarea for Livewire sync --}}
                        <textarea
                            id="composer-body"
                            wire:model="bodyHtml"
                            class="hidden"
                            aria-hidden="true"
                        ></textarea>
                    </div>
                    @error('body')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Autosave Status Indicator --}}
                <div class="mb-4 flex items-center justify-between">
                    <span class="text-xs text-gray-500" x-data="{ status: @entangle('autosaveStatus') }">
                        <span x-show="status === 'saving'" class="flex items-center gap-1">
                            <svg class="animate-spin h-3 w-3" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Saving...
                        </span>
                        <span x-show="status === 'saved'">Saved @if($lastSavedAt) {{ \Carbon\Carbon::parse($lastSavedAt)->diffForHumans() }} @else just now @endif</span>
                        <span x-show="status === 'error'" class="text-red-600">Save failed</span>
                    </span>
                </div>

                {{-- Attachments --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Attachments</label>

                    {{-- Drop Zone --}}
                    <div
                        class="border-2 border-dashed border-gray-300 hover:border-blue-400 rounded-lg p-8 text-center transition-colors cursor-pointer"
                        wire:loading.class="opacity-50"
                        x-data="{ dragOver: false }"
                        @dragover.prevent="dragOver = true"
                        @dragleave.prevent="dragOver = false"
                        @drop.prevent="dragOver = false; handleDrop($event)"
                        :class="{ 'border-blue-500 bg-blue-50': dragOver }"
                    >
                        <input
                            type="file"
                            wire:model="attachments"
                            multiple
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                            @change="$wire.updatedAttachments()"
                        >
                        <div class="flex flex-col items-center gap-2">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <p class="text-gray-600">Drag files here or click to browse</p>
                            <p class="text-xs text-gray-400">Max 25MB per file, 50MB total</p>
                        </div>
                    </div>

                    {{-- Attachment List --}}
                    @if(count($attachments) > 0)
                        <div class="mt-4 space-y-2">
                            @foreach($attachments as $index => $attachment)
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                                    {{-- File Icon/Preview --}}
                                    @php
                                        $mime = $attachment->getMimeType();
                                        $isImage = str_starts_with($mime, 'image/');
                                    @endphp
                                    @if($isImage)
                                        <img
                                            src="{{ $attachment->temporaryUrl() }}"
                                            alt="{{ $attachment->getClientOriginalName() }}"
                                            class="w-12 h-12 object-cover rounded"
                                        >
                                    @else
                                        <div class="w-12 h-12 bg-blue-100 rounded flex items-center justify-center">
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828L18 9.828l6.586 6.586a2 2 0 002.828-2.828L10.828 2.172a2 2 0 00-2.828 0L2.172 9.828a2 2 0 000 2.828l6.586 6.586a2 2 0 102.828-2.828z"></path>
                                            </svg>
                                        </div>
                                    @endif

                                    {{-- File Info --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 truncate">{{ $attachment->getClientOriginalName() }}</p>
                                        <p class="text-xs text-gray-500">{{ number_format($attachment->getSize() / 1024, 1) }} KB</p>
                                    </div>

                                    {{-- Remove Button --}}
                                    <button
                                        type="button"
                                        wire:click="removeAttachment({{ $index }})"
                                        class="text-gray-400 hover:text-red-600 p-1 rounded"
                                        aria-label="Remove {{ $attachment->getClientOriginalName() }}"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @error('attachments')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Reply/Forward Quoted Message Preview --}}
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