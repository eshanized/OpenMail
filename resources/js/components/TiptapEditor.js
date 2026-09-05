// Tiptap Editor for OpenMail Composer
// Full implementation with toolbar, Livewire sync, and all extensions per D-02, D-04

import { Editor, Node, mergeAttributes } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import Link from '@tiptap/extension-link';
import Image from '@tiptap/extension-image';
import Table from '@tiptap/extension-table';
import TableRow from '@tiptap/extension-table-row';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TaskList from '@tiptap/extension-task-list';
import TaskItem from '@tiptap/extension-task-item';
import TextAlign from '@tiptap/extension-text-align';
import TextStyle from '@tiptap/extension-text-style';
import Color from '@tiptap/extension-color';
import Placeholder from '@tiptap/extension-placeholder';
import History from '@tiptap/extension-history';
import CharacterCount from '@tiptap/extension-character-count';
import Underline from '@tiptap/extension-underline';
import Highlight from '@tiptap/extension-highlight';
import Emoji from '@tiptap/extension-emoji';

/**
 * Signature placeholder node for email signatures
 */
const SignaturePlaceholder = Node.create({
    name: 'signaturePlaceholder',
    group: 'block',
    atom: true,
    draggable: true,
    addAttributes() {
        return {
            id: {
                default: null,
            },
        };
    },
    parseHTML() {
        return [
            {
                tag: 'div[data-signature-placeholder]',
            },
        ];
    },
    renderHTML({ HTMLAttributes }) {
        return [
            'div',
            mergeAttributes(HTMLAttributes, {
                'data-signature-placeholder': '',
                class: 'signature-placeholder',
                contenteditable: 'false',
            }),
            'Signature',
        ];
    },
});

/**
 * Initialize a Tiptap editor instance with full toolbar
 * @param {HTMLElement} element - The DOM element to attach the editor to
 * @param {Object} options - Configuration options
 * @param {Function} options.onUpdate - Callback when editor content changes (html string)
 * @param {string} options.initialContent - Initial HTML content
 * @returns {Object} Editor interface with getHTML, destroy, setContent methods
 */
export async function initTiptapEditor(element, options = {}) {
    const { onUpdate, initialContent = '' } = options;

    // Dynamically import all Tiptap modules (avoids SSR issues)
    const [
        { Editor },
        { StarterKit },
        { Link },
        { Image },
        { Table },
        { TableRow },
        { TableCell },
        { TableHeader },
        { TaskList },
        { TaskItem },
        { TextAlign },
        { TextStyle },
        { Color },
        { Placeholder },
        { History },
        { CharacterCount },
        { Underline },
        { Highlight },
        { Emoji },
    ] = await Promise.all([
        import('@tiptap/core'),
        import('@tiptap/starter-kit'),
        import('@tiptap/extension-link'),
        import('@tiptap/extension-image'),
        import('@tiptap/extension-table'),
        import('@tiptap/extension-table-row'),
        import('@tiptap/extension-table-cell'),
        import('@tiptap/extension-table-header'),
        import('@tiptap/extension-task-list'),
        import('@tiptap/extension-task-item'),
        import('@tiptap/extension-text-align'),
        import('@tiptap/extension-text-style'),
        import('@tiptap/extension-color'),
        import('@tiptap/extension-placeholder'),
        import('@tiptap/extension-history'),
        import('@tiptap/extension-character-count'),
        import('@tiptap/extension-underline'),
        import('@tiptap/extension-highlight'),
        import('@tiptap/extension-emoji'),
    ]);

    // Create editor instance with all extensions
    const editor = new Editor({
        element,
        extensions: [
            StarterKit.configure({
                heading: { levels: [1, 2, 3] },
                codeBlock: true,
                horizontalRule: true,
                blockquote: true,
                bulletList: true,
                orderedList: true,
            }),
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    class: 'text-blue-600 underline cursor-pointer',
                    target: '_blank',
                    rel: 'noopener noreferrer',
                },
            }),
            Image.configure({
                inline: false,
                allowBase64: false,
            }),
            Table.configure({
                resizable: true,
            }),
            TableRow,
            TableCell,
            TableHeader,
            TaskList,
            TaskItem.configure({ nested: true }),
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            TextStyle,
            Color,
            Placeholder.configure({ placeholder: 'Start writing…' }),
            History.configure({ depth: 100 }),
            CharacterCount.configure({ limit: 100000 }),
            Underline,
            Highlight.configure({ multicolor: true }),
            Emoji.configure({ emoticons: false, suggestion: { tileMargin: 4 } }),
            SignaturePlaceholder,
        ],
        content: initialContent,
        onUpdate: ({ editor }) => {
            if (onUpdate && typeof onUpdate === 'function') {
                onUpdate(editor.getHTML());
            }
        },
        editorProps: {
            attributes: {
                class: 'prose prose-sm max-w-none focus:outline-none min-h-[300px] p-4',
                spellcheck: 'true',
            },
        },
    });

    return {
        editor,
        getHTML: () => editor.getHTML(),
        destroy: () => editor.destroy(),
        setContent: (html) => editor.commands.setContent(html),
        setOptions: (newOptions) => {
            if (newOptions.onUpdate) {
                options.onUpdate = newOptions.onUpdate;
            }
        },
    };
}

/**
 * Create toolbar for Tiptap editor
 * @param {Editor} editor - Tiptap editor instance
 * @param {HTMLElement} container - Container element for toolbar
 * @returns {Object} Toolbar interface with destroy method
 */
export function createToolbar(editor, container) {
    const toolbar = document.createElement('div');
    toolbar.className = 'tiptap-toolbar border-b border-gray-200 bg-gray-50 p-2 flex flex-col gap-1';
    toolbar.setAttribute('role', 'toolbar');
    toolbar.setAttribute('aria-label', 'Rich text formatting');

    // Row 1: Text formatting, heading, alignment
    const row1 = document.createElement('div');
    row1.className = 'flex flex-wrap gap-1';

    // Text formatting group
    const formattingButtons = [
        { name: 'bold', label: 'Bold', shortcut: 'Ctrl+B', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>' },
        { name: 'italic', label: 'Italic', shortcut: 'Ctrl+I', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14"></path></svg>' },
        { name: 'underline', label: 'Underline', shortcut: 'Ctrl+U', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6v16.6M4 22h16"></path></svg>' },
        { name: 'strike', label: 'Strikethrough', shortcut: 'Ctrl+Shift+X', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.25 6.75L4.75 19.25M9.75 17.25L19.25 7.75"></path></svg>' },
    ];

    formattingButtons.forEach(btn => {
        const button = createToolbarButton(btn, editor);
        row1.appendChild(button);
    });

    // Separator
    row1.appendChild(createSeparator());

    // Font size dropdown
    row1.appendChild(createFontSizeDropdown(editor));

    // Text color picker
    row1.appendChild(createColorPicker(editor, 'text', 'Text Color', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0M7 21h18"></path></svg>'));

    // Highlight color picker
    row1.appendChild(createColorPicker(editor, 'highlight', 'Highlight', '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>'));

    row1.appendChild(createSeparator());

    // Heading dropdown
    row1.appendChild(createHeadingDropdown(editor));

    row1.appendChild(createSeparator());

    // Alignment buttons
    const alignButtons = [
        { name: 'left', label: 'Align Left', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3H5a2 2 0 00-2 2v14a2 2 0 002 2h8m-6 0a2 2 0 11-4 0 2 2 0 014 0zm0-12a2 2 0 11-4 0 2 2 0 014 0zm-4 8a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>' },
        { name: 'center', label: 'Align Center', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 3H4a1 1 0 00-1 1v18a1 1 0 001 1h3m10-18h3a1 1 0 011 1v18a1 1 0 01-1 1h-3M3 12h18"></path></svg>' },
        { name: 'right', label: 'Align Right', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 3h6a2 2 0 012 2v14a2 2 0 01-2 2h-6m0-16a2 2 0 114 0 2 2 0 01-4 0zm0 8a2 2 0 114 0 2 2 0 01-4 0zm0 8a2 2 0 114 0 2 2 0 01-4 0z"></path></svg>' },
        { name: 'justify', label: 'Justify', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>' },
    ];

    alignButtons.forEach(btn => {
        const button = createAlignButton(btn, editor);
        row1.appendChild(button);
    });

    toolbar.appendChild(row1);

    // Row 2: Lists, blocks, media, undo/redo
    const row2 = document.createElement('div');
    row2.className = 'flex flex-wrap gap-1';

    // List buttons
    const listButtons = [
        { name: 'bulletList', label: 'Bullet List', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>' },
        { name: 'orderedList', label: 'Numbered List', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 11V9"></path></svg>' },
        { name: 'taskList', label: 'Task List', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 8V7m0 0L7 11m0-4l4 4"></path></svg>' },
    ];

    listButtons.forEach(btn => {
        const button = createListButton(btn, editor);
        row2.appendChild(button);
    });

    row2.appendChild(createSeparator());

    // Block elements
    const blockButtons = [
        { name: 'blockquote', label: 'Quote', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>' },
        { name: 'codeBlock', label: 'Code Block', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"></path></svg>' },
        { name: 'horizontalRule', label: 'Horizontal Rule', icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2"></path></svg>' },
    ];

    blockButtons.forEach(btn => {
        const button = createBlockButton(btn, editor);
        row2.appendChild(button);
    });

    row2.appendChild(createSeparator());

    // Media/insert buttons
    const mediaButtons = [
        {
            name: 'table',
            label: 'Insert Table (3x3)',
            icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>',
            action: () => editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        },
        {
            name: 'link',
            label: 'Insert Link',
            icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.102 1.101"></path></svg>',
            action: () => {
                const url = prompt('Enter URL:');
                if (url) {
                    editor.chain().focus().setLink({ href: url }).run();
                }
            },
        },
        {
            name: 'image',
            label: 'Insert Image (URL)',
            icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>',
            action: () => {
                const url = prompt('Enter image URL:');
                if (url) {
                    editor.chain().focus().setImage({ src: url }).run();
                }
            },
        },
        {
            name: 'emoji',
            label: 'Emoji',
            icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            action: () => {
                // Emoji is handled by the Emoji extension's suggestion
                editor.commands.focus();
            },
        },
    ];

    mediaButtons.forEach(btn => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors';
        button.innerHTML = btn.icon;
        button.title = btn.label;
        button.setAttribute('aria-label', btn.label);
        button.addEventListener('click', (e) => {
            e.preventDefault();
            btn.action();
        });
        row2.appendChild(button);
    });

    row2.appendChild(createSeparator());

    // Undo/Redo
    const undoButton = document.createElement('button');
    undoButton.type = 'button';
    undoButton.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
    undoButton.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a2 2 0 012 2v4a2 2 0 01-2 2m-2-2l-6 6m0 0l6 6m-6-6l6-6"></path></svg>';
    undoButton.title = 'Undo (Ctrl+Z)';
    undoButton.setAttribute('aria-label', 'Undo');
    undoButton.addEventListener('click', () => editor.chain().focus().undo().run());
    undoButton.disabled = !editor.can().undo();
    editor.on('update', () => { undoButton.disabled = !editor.can().undo(); });

    const redoButton = document.createElement('button');
    redoButton.type = 'button';
    redoButton.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors disabled:opacity-50 disabled:cursor-not-allowed';
    redoButton.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a2 2 0 01-2-2V6a2 2 0 012-2m2 2l6-6m0 0l-6-6m6 6l-6 6"></path></svg>';
    redoButton.title = 'Redo (Ctrl+Shift+Z)';
    redoButton.setAttribute('aria-label', 'Redo');
    redoButton.addEventListener('click', () => editor.chain().focus().redo().run());
    redoButton.disabled = !editor.can().redo();
    editor.on('update', () => { redoButton.disabled = !editor.can().redo(); });

    row2.appendChild(undoButton);
    row2.appendChild(redoButton);

    toolbar.appendChild(row2);

    container.appendChild(toolbar);

    return {
        destroy: () => toolbar.remove(),
    };
}

/**
 * Create a standard toolbar button for formatting marks
 */
function createToolbarButton(config, editor) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors';
    button.innerHTML = config.icon;
    button.title = `${config.label} (${config.shortcut})`;
    button.setAttribute('aria-label', config.label);
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('role', 'button');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        editor.chain().focus()[config.name]().run();
    });

    // Update active state
    const updateActive = () => {
        const isActive = editor.isActive(config.name);
        button.classList.toggle('bg-blue-100', isActive);
        button.classList.toggle('text-blue-600', isActive);
        button.setAttribute('aria-pressed', isActive.toString());
    };

    editor.on('update', updateActive);
    updateActive();

    return button;
}

/**
 * Create an alignment button
 */
function createAlignButton(config, editor) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors';
    button.innerHTML = config.icon;
    button.title = config.label;
    button.setAttribute('aria-label', config.label);
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('role', 'button');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        editor.chain().focus().setTextAlign(config.name).run();
    });

    const updateActive = () => {
        const isActive = editor.isActive({ textAlign: config.name });
        button.classList.toggle('bg-blue-100', isActive);
        button.classList.toggle('text-blue-600', isActive);
        button.setAttribute('aria-pressed', isActive.toString());
    };

    editor.on('update', updateActive);
    updateActive();

    return button;
}

/**
 * Create a list button
 */
function createListButton(config, editor) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors';
    button.innerHTML = config.icon;
    button.title = config.label;
    button.setAttribute('aria-label', config.label);
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('role', 'button');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        editor.chain().focus()[config.name]().run();
    });

    const updateActive = () => {
        const isActive = editor.isActive(config.name);
        button.classList.toggle('bg-blue-100', isActive);
        button.classList.toggle('text-blue-600', isActive);
        button.setAttribute('aria-pressed', isActive.toString());
    };

    editor.on('update', updateActive);
    updateActive();

    return button;
}

/**
 * Create a block element button
 */
function createBlockButton(config, editor) {
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors';
    button.innerHTML = config.icon;
    button.title = config.label;
    button.setAttribute('aria-label', config.label);
    button.setAttribute('aria-pressed', 'false');
    button.setAttribute('role', 'button');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        editor.chain().focus()[config.name]().run();
    });

    const updateActive = () => {
        const isActive = editor.isActive(config.name);
        button.classList.toggle('bg-blue-100', isActive);
        button.classList.toggle('text-blue-600', isActive);
        button.setAttribute('aria-pressed', isActive.toString());
    };

    editor.on('update', updateActive);
    updateActive();

    return button;
}

/**
 * Create a separator element
 */
function createSeparator() {
    const sep = document.createElement('div');
    sep.className = 'w-px h-6 bg-gray-300 mx-1';
    sep.setAttribute('role', 'separator');
    sep.setAttribute('aria-orientation', 'vertical');
    return sep;
}

/**
 * Create font size dropdown
 */
function createFontSizeDropdown(editor) {
    const wrapper = document.createElement('div');
    wrapper.className = 'relative';
    wrapper.innerHTML = `
        <button
            type="button"
            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors flex items-center gap-1"
            aria-label="Font Size"
            aria-expanded="false"
            aria-haspopup="listbox"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>
            <span class="text-xs">Aa</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
        <div class="absolute top-full left-0 mt-1 min-w-[120px] bg-white border border-gray-200 rounded-md shadow-lg py-1 hidden" role="listbox" aria-label="Font sizes">
            ${['12px', '14px', '16px', '18px', '24px', '32px', '48px'].map(size => `
                <button type="button" class="w-full px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-100" role="option" data-size="${size}">${size}</button>
            `).join('')}
        </div>
    `;

    const button = wrapper.querySelector('button');
    const dropdown = wrapper.querySelector('div[role="listbox"]');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', (!expanded).toString());
        dropdown.classList.toggle('hidden', expanded);
    });

    dropdown.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const size = btn.dataset.size;
            editor.chain().focus().setFontSize(size).run();
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        });
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }
    });

    return wrapper;
}

/**
 * Create color picker (text or highlight)
 */
function createColorPicker(editor, type, label, icon) {
    const colors = [
        { name: 'Default', value: '' },
        { name: 'Red', value: '#ef4444' },
        { name: 'Orange', value: '#f97316' },
        { name: 'Amber', value: '#f59e0b' },
        { name: 'Green', value: '#22c55e' },
        { name: 'Blue', value: '#3b82f6' },
        { name: 'Indigo', value: '#6366f1' },
        { name: 'Purple', value: '#a855f7' },
        { name: 'Pink', value: '#ec4899' },
        { name: 'Gray', value: '#6b7280' },
    ];

    const wrapper = document.createElement('div');
    wrapper.className = 'relative';
    wrapper.innerHTML = `
        <button
            type="button"
            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors flex items-center gap-1"
            aria-label="${label}"
            aria-expanded="false"
            aria-haspopup="listbox"
        >
            ${icon}
        </button>
        <div class="absolute top-full left-0 mt-1 bg-white border border-gray-200 rounded-md shadow-lg py-1 hidden grid grid-cols-5 gap-1 p-1" role="listbox" aria-label="${label}">
            ${colors.map(c => `
                <button
                    type="button"
                    class="w-6 h-6 rounded border border-gray-200 hover:scale-110 transition-transform ${c.value ? `bg-[${c.value}]` : 'bg-white'}"
                    role="option"
                    data-color="${c.value}"
                    aria-label="${c.name}"
                    title="${c.name}"
                    ${!c.value ? 'style="background: linear-gradient(45deg, #ccc 25%, transparent 25%), linear-gradient(-45deg, #ccc 25%, transparent 25%), linear-gradient(45deg, transparent 75%, #ccc 75%), linear-gradient(-45deg, transparent 75%, #ccc 75%); background-size: 8px 8px;"' : ''}
                ></button>
            `).join('')}
        </div>
    `;

    const button = wrapper.querySelector('button');
    const dropdown = wrapper.querySelector('div[role="listbox"]');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', (!expanded).toString());
        dropdown.classList.toggle('hidden', expanded);
    });

    dropdown.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const color = btn.dataset.color;
            if (type === 'text') {
                editor.chain().focus().setColor(color).run();
            } else {
                editor.chain().focus().setHighlight({ color }).run();
            }
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }
    });

    return wrapper;
}

/**
 * Create heading dropdown
 */
function createHeadingDropdown(editor) {
    const wrapper = document.createElement('div');
    wrapper.className = 'relative';
    wrapper.innerHTML = `
        <button
            type="button"
            class="p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-200 rounded transition-colors flex items-center gap-1"
            aria-label="Heading"
            aria-expanded="false"
            aria-haspopup="listbox"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path></svg>
            <span class="text-xs font-semibold">H</span>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
        </button>
        <div class="absolute top-full left-0 mt-1 min-w-[140px] bg-white border border-gray-200 rounded-md shadow-lg py-1 hidden" role="listbox" aria-label="Headings">
            <button type="button" class="w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100" role="option" data-level="0">Normal Text</button>
            <hr class="my-1 border-gray-200">
            <button type="button" class="w-full px-3 py-1.5 text-left text-xl font-semibold text-gray-700 hover:bg-gray-100" role="option" data-level="1">Heading 1</button>
            <button type="button" class="w-full px-3 py-1.5 text-left text-lg font-semibold text-gray-700 hover:bg-gray-100" role="option" data-level="2">Heading 2</button>
            <button type="button" class="w-full px-3 py-1.5 text-left text-base font-semibold text-gray-700 hover:bg-gray-100" role="option" data-level="3">Heading 3</button>
            <hr class="my-1 border-gray-200">
            <button type="button" class="w-full px-3 py-1.5 text-left text-sm text-gray-700 hover:bg-gray-100" role="option" data-action="clear">Clear Formatting</button>
        </div>
    `;

    const button = wrapper.querySelector('button');
    const dropdown = wrapper.querySelector('div[role="listbox"]');

    button.addEventListener('click', (e) => {
        e.preventDefault();
        const expanded = button.getAttribute('aria-expanded') === 'true';
        button.setAttribute('aria-expanded', (!expanded).toString());
        dropdown.classList.toggle('hidden', expanded);
    });

    dropdown.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const level = btn.dataset.level;
            const action = btn.dataset.action;
            if (action === 'clear') {
                editor.chain().focus().unsetAllMarks().setParagraph().run();
            } else if (level === '0') {
                editor.chain().focus().setParagraph().run();
            } else {
                editor.chain().focus().toggleHeading({ level: parseInt(level) }).run();
            }
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        });
    });

    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) {
            dropdown.classList.add('hidden');
            button.setAttribute('aria-expanded', 'false');
        }
    });

    return wrapper;
}

// Export for compatibility
export default { initTiptapEditor, createToolbar };