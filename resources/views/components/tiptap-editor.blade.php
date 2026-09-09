{{--
  Reusable Tiptap Editor Component
  Usage: <x-tiptap-editor :content-json="$contentJson" wire:model="contentJson" placeholder="Type here..." />

  Props:
    - contentJson (array): Tiptap JSON document content
    - placeholder (string): Placeholder text when empty
    - minHeight (string): Minimum height CSS value
    - wireModel (string): Livewire property to sync with
--}}
@props([
    'contentJson' => ['type' => 'doc', 'content' => [['type' => 'paragraph']]],
    'placeholder' => 'Start typing...',
    'minHeight' => '150px',
    'wireModel' => null,
])

<div
    x-data="{
        editor: null,
        contentJson: @js($contentJson),
        init() {
            this.$nextTick(() => {
                this.initEditor();
            });
            // Watch for external content changes
            this.$watch('contentJson', (newVal) => {
                if (this.editor && JSON.stringify(this.editor.getJSON()) !== JSON.stringify(newVal)) {
                    this.editor.commands.setContent(newVal);
                }
            });
        },
        async initEditor() {
            const initTiptap = window.initTiptapEditor || (await import('../../js/components/TiptapEditor.js')).initTiptapEditor;
            const result = await initTiptap(this.$refs.editor, {
                onUpdate: (html) => {
                    // Sync JSON content to Livewire
                    const json = this.editor.getJSON();
                    @if($wireModel)
                        @this.set('{{ $wireModel }}', json);
                    @endif
                    // Dispatch change event
                    this.$dispatch('tiptap-change', { html, json });
                },
                initialContent: @js(is_string($contentJson) ? $contentJson : json_encode($contentJson)),
            });
            this.editor = result.editor;
        },
        destroy() {
            if (this.editor) {
                this.editor.destroy();
            }
        }
    }"
    class="tiptap-editor border border-gray-300 dark:border-gray-600 rounded-md overflow-hidden"
>
    {{-- Toolbar --}}
    <div class="tiptap-toolbar border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-2 py-1 flex flex-wrap gap-1">
        <button
            type="button"
            @click="editor?.chain().focus().toggleBold().run()"
            :class="editor?.isActive('bold') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm font-medium transition-colors"
            title="Bold"
        >
            <strong>B</strong>
        </button>
        <button
            type="button"
            @click="editor?.chain().focus().toggleItalic().run()"
            :class="editor?.isActive('italic') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm font-medium transition-colors"
            title="Italic"
        >
            <em>I</em>
        </button>
        <button
            type="button"
            @click="editor?.chain().focus().toggleUnderline().run()"
            :class="editor?.isActive('underline') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm font-medium transition-colors"
            title="Underline"
        >
            <u>U</u>
        </button>
        <div class="w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
        <button
            type="button"
            @click="editor?.chain().focus().toggleBulletList().run()"
            :class="editor?.isActive('bulletList') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm transition-colors"
            title="Bullet List"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
        <button
            type="button"
            @click="editor?.chain().focus().toggleOrderedList().run()"
            :class="editor?.isActive('orderedList') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm transition-colors"
            title="Numbered List"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"></path></svg>
        </button>
        <div class="w-px bg-gray-300 dark:bg-gray-600 mx-1"></div>
        <button
            type="button"
            @click="const url = prompt('Enter URL:'); if (url) editor?.chain().focus().setLink({ href: url }).run()"
            :class="editor?.isActive('link') ? 'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700'"
            class="p-1.5 rounded text-sm transition-colors"
            title="Link"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
        </button>
        <button
            type="button"
            @click="editor?.chain().focus().unsetLink().run()"
            x-show="editor?.isActive('link')"
            class="p-1.5 rounded text-sm text-gray-600 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700 transition-colors"
            title="Remove Link"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>
    </div>

    {{-- Editor --}}
    <div
        x-ref="editor"
        class="tiptap-editor-content"
        style="min-height: {{ $minHeight }}; padding: 0.75rem;"
    ></div>
</div>
