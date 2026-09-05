<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\MessageSanitizer;

class MessageSanitizerTest extends TestCase
{
    private MessageSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new MessageSanitizer();
    }

    public function test_script_tag_removed(): void
    {
        $html = '<script>alert("xss")</script><p>Safe content</p>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('<p>Safe content</p>', $result);
    }

    public function test_event_handler_removed(): void
    {
        $html = '<img onerror="alert(1)" src="test.jpg">';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringNotContainsString('onerror', $result);
    }

    public function test_safe_link_preserved(): void
    {
        $html = '<a href="https://example.com">Link</a>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringContainsString('href="https://example.com"', $result);
    }

    public function test_dangerous_link_stripped(): void
    {
        $html = '<a href="javascript:alert(1)">Link</a>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function test_image_src_validation(): void
    {
        $html = '<img src="https://example.com/img.jpg">';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringContainsString('src="https://example.com/img.jpg"', $result);

        $html = '<img src="data:text/html,<script>alert(1)</script>">';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringNotContainsString('data:', $result);
    }

    public function test_css_sanitization(): void
    {
        $html = '<p style="color: red;">Red text</p>';
        $result = $this->sanitizer->sanitizeHtml($html);
        // HTMLPurifier normalizes color to hex
        $this->assertStringContainsString('color:', $result);

        $html = '<p style="background: url(javascript:alert(1))">Bad</p>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    public function test_nested_html_structure_preserved(): void
    {
        $html = '<div><table><tr><td>Cell</td></tr></table></div>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringContainsString('<div>', $result);
        $this->assertStringContainsString('<table>', $result);
        $this->assertStringContainsString('<tr>', $result);
        $this->assertStringContainsString('<td>Cell</td>', $result);
    }

    public function test_charset_handling(): void
    {
        $html = '<p>UTF-8 content: 日本語</p>';
        $result = $this->sanitizer->sanitizeHtml($html);
        $this->assertStringContainsString('日本語', $result);
    }

    public function test_sanitize_text_escapes(): void
    {
        $text = '<script>alert(1)</script>';
        $result = $this->sanitizer->sanitizeText($text);
        // e() helper escapes HTML entities
        $this->assertEquals('&lt;script&gt;alert(1)&lt;/script&gt;', $result);
    }

    public function test_block_remote_images(): void
    {
        $html = '<img src="https://example.com/tracking.png">';
        $result = $this->sanitizer->blockRemoteImages($html);
        $this->assertStringContainsString('data-src="https://example.com/tracking.png"', $result);
    }

    public function test_block_remote_images_preserves_local(): void
    {
        $html = '<img src="/images/local.png">';
        $result = $this->sanitizer->blockRemoteImages($html);
        $this->assertStringContainsString('src="/images/local.png"', $result);
    }
}