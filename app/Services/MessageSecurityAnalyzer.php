<?php

declare(strict_types=1);

namespace App\Services;

use DOMDocument;
use DOMElement;
use Throwable;

class MessageSecurityAnalyzer
{
    private const RISKY_ATTACHMENT_EXTENSIONS = [
        'ade', 'adp', 'apk', 'app', 'bat', 'cab', 'cmd', 'com', 'cpl',
        'dll', 'dmg', 'exe', 'hta', 'img', 'iso', 'jar', 'js', 'jse',
        'lnk', 'msi', 'msp', 'mst', 'pif', 'ps1', 'ps1xml', 'psc1',
        'psd1', 'py', 'pyc', 'pyo', 'reg', 'scr', 'sct', 'sh', 'sys',
        'vb', 'vbe', 'vbs', 'wsc', 'wsf', 'wsh', 'xll',
    ];

    /**
     * Analyze observable indicators only; do not infer that a message is safe.
     *
     * @param array<int, array{name?: string, size?: int, contentType?: string}> $attachments
     * @return array<int, array{level: string, title: string, detail: string}>
     */
    public function analyze(
        string $fromAddress,
        string $fromName,
        string $replyToHeader,
        string $authenticationResults,
        ?string $sanitizedHtml,
        array $attachments,
        string $mailboxAddress
    ): array {
        $signals = [];
        $fromDomain = $this->domainFromAddress($fromAddress);
        $replyTo = $this->firstEmailAddress($replyToHeader);
        $replyToDomain = $this->domainFromAddress($replyTo);

        if ($replyTo !== '' && $fromAddress !== '' && strcasecmp($replyTo, $fromAddress) !== 0 && $replyToDomain !== $fromDomain) {
            $signals[] = [
                'level' => 'warning',
                'title' => 'Reply-To domain differs from sender',
                'detail' => 'Replies would go to '.$replyTo.'. This can be legitimate, but confirm the destination before sharing sensitive information.',
            ];
        }

        $mailboxDomain = $this->domainFromAddress($mailboxAddress);
        if ($fromDomain !== '' && $mailboxDomain !== '' && $fromDomain !== $mailboxDomain) {
            $signals[] = [
                'level' => 'info',
                'title' => 'External sender',
                'detail' => 'The sender domain differs from your mailbox domain. External mail is normal; this is context, not a verdict.',
            ];
        }

        foreach ($this->parseAuthenticationClaims($authenticationResults) as $claim) {
            $isFailure = in_array($claim['result'], ['fail', 'softfail', 'permerror', 'temperror'], true);
            $signals[] = [
                'level' => $isFailure ? 'warning' : 'info',
                'title' => strtoupper($claim['mechanism']).': '.strtoupper($claim['result']),
                'detail' => 'This status was read from an Authentication-Results header and has not been independently verified by OpenMail. A forged header is possible.',
            ];
        }

        foreach ($this->analyzeLinks($sanitizedHtml ?? '') as $signal) {
            $signals[] = $signal;
        }

        foreach ($attachments as $attachment) {
            $name = trim((string) ($attachment['name'] ?? ''));
            $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if ($extension !== '' && in_array($extension, self::RISKY_ATTACHMENT_EXTENSIONS, true)) {
                $signals[] = [
                    'level' => 'warning',
                    'title' => 'Potentially executable attachment',
                    'detail' => ($name !== '' ? $name : 'This attachment')
                        .' uses the .'.$extension.' extension. Only open it if you trust the sender and expected this file.',
                ];
            }
        }

        if ($signals === []) {
            $signals[] = [
                'level' => 'info',
                'title' => 'No obvious indicators detected',
                'detail' => 'This is not a safety guarantee. OpenMail does not independently authenticate every sender or scan attachments for malware.',
            ];
        }

        return $signals;
    }

    /**
     * @return array<int, array{mechanism: string, result: string}>
     */
    protected function parseAuthenticationClaims(string $header): array
    {
        if ($header === '') {
            return [];
        }

        preg_match_all('/\b(spf|dkim|dmarc)\s*=\s*(pass|fail|softfail|none|neutral|temperror|permerror)\b/i', $header, $matches, PREG_SET_ORDER);

        $claims = [];
        foreach ($matches as $match) {
            $key = strtolower($match[1]);
            $claims[$key] = ['mechanism' => $key, 'result' => strtolower($match[2])];
        }

        return array_values($claims);
    }

    /**
     * @return array<int, array{level: string, title: string, detail: string}>
     */
    protected function analyzeLinks(string $html): array
    {
        if (trim($html) === '' || ! class_exists(DOMDocument::class)) {
            return [];
        }

        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);

        try {
            $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
            $links = $document->getElementsByTagName('a');
            $signals = [];
            $seen = [];

            foreach ($links as $link) {
                if (! $link instanceof DOMElement) {
                    continue;
                }

                $url = trim($link->getAttribute('href'));
                $parts = parse_url($url);
                $scheme = strtolower((string) ($parts['scheme'] ?? ''));
                $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

                if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
                    continue;
                }

                $normalizedHost = preg_replace('/^www\./i', '', $host) ?? $host;
                $display = trim($link->textContent);
                $displayHost = $this->hostFromVisibleLink($display);

                if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
                    $key = 'ip:'.$host;
                    if (! isset($seen[$key])) {
                        $signals[] = [
                            'level' => 'warning',
                            'title' => 'Link points directly to an IP address',
                            'detail' => 'A link destination uses '.$host.' instead of a domain name. Verify the destination before opening it.',
                        ];
                        $seen[$key] = true;
                    }

                    continue;
                }

                if (str_contains($host, 'xn--')) {
                    $key = 'idn:'.$host;
                    if (! isset($seen[$key])) {
                        $signals[] = [
                            'level' => 'warning',
                            'title' => 'Internationalized link domain',
                            'detail' => 'The destination '.$host.' uses punycode encoding, which can make look-alike domains harder to recognize.',
                        ];
                        $seen[$key] = true;
                    }
                }

                if ($displayHost !== '' && $displayHost !== $normalizedHost) {
                    $key = 'mismatch:'.$displayHost.'>'.$normalizedHost;
                    if (! isset($seen[$key])) {
                        $signals[] = [
                            'level' => 'warning',
                            'title' => 'Visible link and destination differ',
                            'detail' => 'The message displays '.$displayHost.' but the actual destination is '.$host.'.',
                        ];
                        $seen[$key] = true;
                    }
                }
            }

            return $signals;
        } catch (Throwable) {
            return [];
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    protected function hostFromVisibleLink(string $text): string
    {
        $text = trim($text);
        if ($text === '' || mb_strlen($text) > 2048 || preg_match('/\s/', $text)) {
            return '';
        }

        $candidate = preg_match('/^https?:\/\//i', $text) ? $text : 'https://'.$text;
        $parts = parse_url($candidate);
        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));

        if ($host === '' || filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return '';
        }

        // Only treat text that looks like a hostname as a visible URL.
        if (! str_contains($text, '.') || ! preg_match('/^[a-z0-9.-]+(?::\d+)?(?:\/.*)?$/i', $text)) {
            return '';
        }

        return preg_replace('/^www\./i', '', $host) ?? $host;
    }

    protected function firstEmailAddress(string $value): string
    {
        if (preg_match('/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i', $value, $match)) {
            return strtolower($match[0]);
        }

        return '';
    }

    protected function domainFromAddress(string $email): string
    {
        if (! filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
            return '';
        }

        return strtolower(substr(strrchr(trim($email), '@'), 1));
    }
}
