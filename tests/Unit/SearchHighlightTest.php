<?php

namespace Tests\Unit;

use App\Services\SearchService;
use Tests\TestCase;

class SearchHighlightTest extends TestCase
{
    /** @test */
    public function highlight_matches_wrapped_in_mark_tags_after_sanitization(): void
    {
        $service = new SearchService;

        $snippet = 'This is a test message about project alpha and beta testing';
        $query = 'project alpha';

        $highlighted = $service->highlightMatches($snippet, $query);

        // Should contain <mark> tags with matching classes for each word
        $this->assertStringContainsString('<mark class="bg-yellow-100 text-yellow-900 px-0.5 rounded">', $highlighted);
        $this->assertStringContainsString('</mark>', $highlighted);
        $this->assertStringContainsString('project', $highlighted);
        $this->assertStringContainsString('alpha', $highlighted);
    }

    /** @test */
    public function highlight_is_case_insensitive(): void
    {
        $service = new SearchService;

        $snippet = 'PROJECT ALPHA is important';
        $query = 'project alpha';

        $highlighted = $service->highlightMatches($snippet, $query);

        $this->assertStringContainsString('<mark', $highlighted);
    }

    /** @test */
    public function highlight_escapes_regex_special_characters_in_query(): void
    {
        $service = new SearchService;

        $snippet = 'Price is $100.00 (discounted)';
        $query = '$100.00';

        $highlighted = $service->highlightMatches($snippet, $query);

        $this->assertStringContainsString('<mark', $highlighted);
        $this->assertStringContainsString('$100.00', $highlighted);
    }

    /** @test */
    public function highlight_sanitize_before_mark_wrapping_for_xss_prevention(): void
    {
        $service = new SearchService;

        // Simulate a malicious snippet that has already been sanitized
        $snippet = 'Safe text about &lt;script&gt;alert("xss")&lt;/script&gt; project';
        $query = 'project';

        $highlighted = $service->highlightMatches($snippet, $query);

        // The <mark> wrapping should not introduce XSS
        $this->assertStringContainsString('<mark', $highlighted);
        $this->assertStringContainsString('project', $highlighted);
        // Script should remain escaped
        $this->assertStringContainsString('&lt;script&gt;', $highlighted);
        $this->assertStringNotContainsString('<script>', $highlighted);
    }

    /** @test */
    public function highlight_preserves_html_safe_snippet_content(): void
    {
        $service = new SearchService;

        $snippet = 'Message about meeting at 3:00 PM & conference';
        $query = 'meeting';

        $highlighted = $service->highlightMatches($snippet, $query);

        $this->assertStringContainsString('<mark', $highlighted);
        $this->assertStringContainsString('3:00 PM & conference', $highlighted);
    }

    /** @test */
    public function highlight_returns_original_when_no_query(): void
    {
        $service = new SearchService;

        $snippet = 'Test message content';
        $highlighted = $service->highlightMatches($snippet, '');

        $this->assertEquals('Test message content', $highlighted);
    }

    /** @test */
    public function highlight_returns_original_when_no_match(): void
    {
        $service = new SearchService;

        $snippet = 'Hello world';
        $query = 'goodbye';

        $highlighted = $service->highlightMatches($snippet, $query);

        $this->assertEquals('Hello world', $highlighted);
    }
}
