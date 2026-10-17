<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\MessageSecurityAnalyzer;
use Tests\TestCase;

class MessageSecurityAnalyzerTest extends TestCase
{
    public function test_analyzer_surfaces_reply_to_mismatch_unverified_auth_claims_and_risky_attachments(): void
    {
        $signals = app(MessageSecurityAnalyzer::class)->analyze(
            'billing@outside.example',
            'Reply desk <payments@different.example>',
            'mx.example; spf=fail; dkim=pass; dmarc=fail',
            '<p><a href="https://xn--pple-43d.com/login">apple.com</a></p>',
            [
                ['name' => 'invoice.js', 'contentType' => 'application/javascript', 'size' => 100],
            ],
            'eshan@company.example'
        );

        $titles = array_column($signals, 'title');

        $this->assertContains('Reply-To domain differs from sender', $titles);
        $this->assertContains('External sender', $titles);
        $this->assertContains('SPF: FAIL', $titles);
        $this->assertContains('DKIM: PASS', $titles);
        $this->assertContains('DMARC: FAIL', $titles);
        $this->assertContains('Potentially executable attachment', $titles);
        $this->assertContains('Internationalized link domain', $titles);
        $this->assertContains('Visible link and destination differ', $titles);
    }

    public function test_analyzer_does_not_claim_a_message_is_safe_when_no_obvious_indicators_exist(): void
    {
        $signals = app(MessageSecurityAnalyzer::class)->analyze(
            'colleague@company.example',
            '',
            '',
            '<p>Hello</p>',
            [],
            'user@company.example'
        );

        $this->assertCount(1, $signals);
        $this->assertSame('No obvious indicators detected', $signals[0]['title']);
        $this->assertStringContainsString('not a safety guarantee', $signals[0]['detail']);
    }
}
