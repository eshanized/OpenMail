<?php

namespace Tests\Feature\Composer;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TiptapIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_tiptap_editor_module_exists_and_exports_init()
    {
        $jsPath = base_path('resources/js/components/TiptapEditor.js');
        $this->assertFileExists($jsPath, 'TiptapEditor.js module should exist');

        $content = file_get_contents($jsPath);
        $this->assertStringContainsString('export async function initTiptapEditor', $content, 'Should export initTiptapEditor function');
        $this->assertStringContainsString('export function createToolbar', $content, 'Should export createToolbar function');
    }

    public function test_composer_blade_contains_tiptap_editor_container()
    {
        $bladePath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $this->assertFileExists($bladePath, 'Composer blade template should exist');

        $content = file_get_contents($bladePath);
        $this->assertStringContainsString('wire:ignore', $content, 'Should have wire:ignore to prevent Livewire re-rendering Tiptap');
        $this->assertStringContainsString('x-ref="editor"', $content, 'Should have x-ref="editor" for Tiptap mounting');
        $this->assertStringContainsString('x-ref="toolbar"', $content, 'Should have x-ref="toolbar" for toolbar mounting');
        $this->assertStringContainsString('class="tiptap-editor"', $content, 'Should have tiptap-editor class on editor container');
    }

    public function test_hidden_textarea_receives_content_from_editor()
    {
        $bladePath = base_path('resources/views/livewire/mailbox/composer.blade.php');
        $content = file_get_contents($bladePath);

        $this->assertStringContainsString('wire:model="bodyHtml"', $content, 'Should have wire:model on hidden textarea for Livewire sync');
        $this->assertStringContainsString('class="hidden"', $content, 'Hidden textarea should have hidden class');
        $this->assertStringContainsString('aria-hidden="true"', $content, 'Hidden textarea should have aria-hidden');
    }

    public function test_toolbar_buttons_render_with_aria_labels()
    {
        $jsPath = base_path('resources/js/components/TiptapEditor.js');
        $content = file_get_contents($jsPath);

        // Check for toolbar button creation with aria attributes
        $this->assertStringContainsString('aria-label', $content, 'Toolbar buttons should have aria-label');
        $this->assertStringContainsString('aria-pressed', $content, 'Toggle buttons should have aria-pressed');
        $this->assertStringContainsString("setAttribute('role', 'button')", $content, 'Buttons should have role="button"');
        $this->assertStringContainsString("setAttribute('role', 'toolbar')", $content, 'Toolbar should have role="toolbar"');
        $this->assertStringContainsString('role="listbox"', $content, 'Dropdowns should have role="listbox"');
        $this->assertStringContainsString('role="option"', $content, 'Dropdown options should have role="option"');
        $this->assertStringContainsString('aria-expanded', $content, 'Dropdown toggles should have aria-expanded');
    }

    public function test_quote_component_renders_attribution_and_collapsible_content()
    {
        $componentPath = base_path('resources/views/components/composer-quote.blade.php');
        $this->assertFileExists($componentPath, 'composer-quote component should exist');

        $content = file_get_contents($componentPath);
        $this->assertStringContainsString('quote-block', $content, 'Should have quote-block class');
        $this->assertStringContainsString('border-l-4 border-primary/20', $content, 'Should have blue left border');
        $this->assertStringContainsString('x-show="open"', $content, 'Should use x-show for collapsible content');
        $this->assertStringContainsString('x-transition', $content, 'Should use x-transition for animation');
        $this->assertStringContainsString('aria-expanded', $content, 'Toggle button should have aria-expanded');
        $this->assertStringContainsString('aria-controls', $content, 'Toggle button should have aria-controls');
        $this->assertStringContainsString('Show quoted text', $content, 'Should show "Show quoted text" when collapsed');
        $this->assertStringContainsString('Hide quoted text', $content, 'Should show "Hide quoted text" when expanded');
        $this->assertStringContainsString('{!! $quotedHtml !!}', $content, 'Should render quoted HTML with sanitization');
    }

    public function test_tiptap_extensions_configured_in_editor()
    {
        $jsPath = base_path('resources/js/components/TiptapEditor.js');
        $content = file_get_contents($jsPath);

        // Check for all required extensions per D-04
        $this->assertStringContainsString('StarterKit', $content, 'Should include StarterKit');
        $this->assertStringContainsString('Link', $content, 'Should include Link extension');
        $this->assertStringContainsString('Image', $content, 'Should include Image extension');
        $this->assertStringContainsString('Table', $content, 'Should include Table extension');
        $this->assertStringContainsString('TaskList', $content, 'Should include TaskList extension');
        $this->assertStringContainsString('TaskItem', $content, 'Should include TaskItem extension');
        $this->assertStringContainsString('TextAlign', $content, 'Should include TextAlign extension');
        $this->assertStringContainsString('TextStyle', $content, 'Should include TextStyle extension');
        $this->assertStringContainsString('Color', $content, 'Should include Color extension');
        $this->assertStringContainsString('Placeholder', $content, 'Should include Placeholder extension');
        $this->assertStringContainsString('History', $content, 'Should include History extension');
        $this->assertStringContainsString('CharacterCount', $content, 'Should include CharacterCount extension');
        $this->assertStringContainsString('Underline', $content, 'Should include Underline extension');
        $this->assertStringContainsString('Highlight', $content, 'Should include Highlight extension');
        $this->assertStringContainsString('Emoji', $content, 'Should include Emoji extension');

        // Check configuration
        $this->assertStringContainsString('heading: { levels: [1, 2, 3] }', $content, 'Should configure heading levels 1-3');
        $this->assertStringContainsString('allowBase64: false', $content, 'Should disable base64 images');
        $this->assertStringContainsString('resizable: true', $content, 'Should enable table resizing');
        $this->assertStringContainsString('multicolor: true', $content, 'Should enable multicolor highlight');
        $this->assertStringContainsString('depth: 100', $content, 'Should configure history depth');
        $this->assertStringContainsString('limit: 100000', $content, 'Should configure character count limit');
    }

    public function test_tiptap_css_styles_added()
    {
        $cssPath = base_path('resources/css/app.css');
        $content = file_get_contents($cssPath);

        $this->assertStringContainsString('.tiptap-editor .ProseMirror', $content, 'Should have Tiptap editor ProseMirror styles');
        $this->assertStringContainsString('min-height: 300px', $content, 'Should set min-height for editor');
        $this->assertStringContainsString('data-placeholder', $content, 'Should style placeholder');
        $this->assertStringContainsString('.quote-block', $content, 'Should have quote-block styles');
        $this->assertStringContainsString('.signature-placeholder', $content, 'Should have signature placeholder styles');
        $this->assertStringContainsString('.tiptap-toolbar button.bg-blue-100', $content, 'Should style active toolbar buttons');
    }

    public function test_composer_component_has_syncbodfromeditor_method()
    {
        $phpPath = base_path('app/Livewire/Mailbox/Composer.php');
        $content = file_get_contents($phpPath);

        $this->assertStringContainsString('public function syncBodyFromEditor', $content, 'Composer should have syncBodyFromEditor method');
        $this->assertStringContainsString('$this->bodyHtml = $html', $content, 'Should store HTML in bodyHtml property');
        $this->assertStringContainsString('bodyText', $content, 'Should have bodyText property for plain text fallback');
    }

    public function test_composer_getcomposerdata_uses_bodyhtml()
    {
        $phpPath = base_path('app/Livewire/Mailbox/Composer.php');
        $content = file_get_contents($phpPath);

        $this->assertStringContainsString('bodyHtml', $content, 'Should use bodyHtml in getComposerData');
        $this->assertStringContainsString("'body' => \$this->bodyHtml", $content, 'getComposerData should return bodyHtml as body');
    }
}
