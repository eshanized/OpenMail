<?php

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;

class MessageSanitizer
{
    private ?HTMLPurifier $purifier = null;

    private function getPurifier(): HTMLPurifier
    {
        if ($this->purifier !== null) {
            return $this->purifier;
        }

        $config = HTMLPurifier_Config::createDefault();
        $config->set('HTML.Doctype', 'HTML 4.01 Transitional');
        $config->set('HTML.DefinitionID', 'openmail-email-sanitizer');
        $config->set('HTML.DefinitionRev', 1);
        $config->set('HTML.AllowedElements', [
            'p', 'br', 'a', 'img', 'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'div', 'span',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote',
            'pre', 'code', 'strong', 'b', 'em', 'i', 'u', 's', 'sub', 'sup', 'hr',
        ]);
        $config->set('HTML.AllowedAttributes', [
            'a.href', 'a.target', 'a.rel', 'a.title', 'a.class',
            'img.src', 'img.alt', 'img.title', 'img.width', 'img.height', 'img.style', 'img.data-src', 'img.data-srcset',
            'table.border', 'table.cellpadding', 'table.cellspacing', 'table.width', 'table.style',
            'td.colspan', 'td.rowspan', 'td.width', 'td.height', 'td.style',
            'th.colspan', 'th.rowspan', 'th.width', 'th.height', 'th.style',
            'div.style', 'div.class', 'span.style', 'span.class', 'p.style', 'p.class',
            'h1.style', 'h2.style', 'h3.style', 'h4.style', 'h5.style', 'h6.style',
            'ul.style', 'ol.style', 'li.style',
            'blockquote.style', 'pre.style', 'code.style',
            'strong.style', 'em.style', 'u.style', 's.style', 'sub.style', 'sup.style',
        ]);
        $config->set('CSS.AllowedProperties', [
            'color', 'background-color', 'font-size', 'font-family', 'font-weight',
            'font-style', 'text-align', 'text-decoration', 'line-height',
            'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
            'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
            'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
            'border-color', 'border-width', 'border-style',
            'width', 'min-width', 'max-width', 'height', 'min-height', 'max-height',
            'float', 'clear', 'table-layout', 'vertical-align',
        ]);
        $config->set('URI.AllowedSchemes', ['http', 'https', 'mailto', 'tel']);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Core.Encoding', 'UTF-8');

        if ($def = $config->maybeGetRawHTMLDefinition()) {
            $def->addAttribute('img', 'data-src', 'Text');
            $def->addAttribute('img', 'data-srcset', 'Text');
        }

        $this->purifier = new HTMLPurifier($config);

        return $this->purifier;
    }

    public function sanitizeHtml(string $html): string
    {
        return $this->getPurifier()->purify($html);
    }

    public function sanitizeText(string $text): string
    {
        return e($text);
    }

    /**
     * Rewrite remote tracking images and style-based background images by default.
     * Rewrites http/https, protocol-relative (//), case-insensitive schemes and srcset attributes.
     */
    public function blockRemoteImages(string $html): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // 1. Process <img> tags
        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = trim($img->getAttribute('src'));
            if ($this->isRemoteUrl($src)) {
                $img->setAttribute('data-src', $src);
                $img->setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            }

            $srcset = trim($img->getAttribute('srcset'));
            if (! empty($srcset)) {
                $img->setAttribute('data-srcset', $srcset);
                $img->removeAttribute('srcset');
            }
        }

        // 2. Process <source> tags
        foreach ($dom->getElementsByTagName('source') as $source) {
            $srcset = trim($source->getAttribute('srcset'));
            if (! empty($srcset)) {
                $source->setAttribute('data-srcset', $srcset);
                $source->removeAttribute('srcset');
            }
            $src = trim($source->getAttribute('src'));
            if ($this->isRemoteUrl($src)) {
                $source->setAttribute('data-src', $src);
                $source->setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            }
        }

        // 3. Process style attributes with background / background-image url(...)
        $xpath = new \DOMXPath($dom);
        $styledNodes = $xpath->query('//*[@style]');
        if ($styledNodes) {
            foreach ($styledNodes as $node) {
                if ($node instanceof \DOMElement) {
                    $style = $node->getAttribute('style');
                    if (preg_match('/url\s*\(\s*[\'"]?(https?:|\/\/)[^\'")]*[\'"]?\s*\)/i', $style)) {
                        $node->setAttribute('data-style', $style);
                        $cleanedStyle = preg_replace('/url\s*\(\s*[\'"]?(https?:|\/\/)[^\'")]*[\'"]?\s*\)/i', 'none', $style);
                        $node->setAttribute('style', $cleanedStyle);
                    }
                }
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $output = $body ? $dom->saveHTML($body) : $dom->saveHTML();

        return preg_replace('/<\!--\?xml.*?-->\s*/i', '', $output);
    }

    /**
     * Check if a URL represents a remote resource.
     */
    protected function isRemoteUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        // Protocol-relative //example.com/img.png
        if (str_starts_with($url, '//')) {
            return true;
        }

        // Case-insensitive http: or https:
        if (preg_match('/^https?:\/\//i', $url)) {
            return true;
        }

        return false;
    }

    /**
     * Sanitize signature HTML content (SEC-07).
     */
    public function sanitizeSignatureHtml(string $html): string
    {
        return $this->getPurifier()->purify($html);
    }

    /**
     * Sanitize URLs in message HTML content.
     * Blocks dangerous schemes (javascript:, data:, vbscript:, file:) and private IP addresses.
     */
    public function sanitizeUrls(string $html): string
    {
        if (! str_contains(strtolower($html), '<a')) {
            return $html;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('a') as $link) {
            $href = $link->getAttribute('href');
            if ($href && $this->isDangerousUrl($href)) {
                $link->setAttribute('href', '#');
                $link->setAttribute('data-original-href', $href);
                $link->setAttribute('class', trim(($link->getAttribute('class') ?? '').' link-blocked'));
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $output = $body ? $dom->saveHTML($body) : $dom->saveHTML();

        return preg_replace('/<\!--\?xml.*?-->\s*/i', '', $output);
    }

    /**
     * Determine if a URL is dangerous (SSRF or XSS vector).
     * Uses parsed URL and allowlist-based scheme policy rather than pure regex prefix matching.
     */
    public function isDangerousUrl(string $url): bool
    {
        $trimmed = trim(rawurldecode($url));

        // Block dangerous schemes
        if (preg_match('/^(javascript|data|vbscript|file):/i', $trimmed)) {
            return true;
        }

        // Parse URL
        $parsed = parse_url($trimmed);
        if ($parsed === false) {
            return true;
        }

        // Check scheme if present
        $scheme = strtolower($parsed['scheme'] ?? '');
        if ($scheme !== '' && ! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)) {
            return true;
        }

        // Check host for SSRF vectors
        $host = $parsed['host'] ?? '';
        if ($host === '' && str_starts_with($trimmed, '//')) {
            // Protocol-relative URL
            $parts = explode('/', ltrim($trimmed, '/'));
            $host = $parts[0] ?? '';
        }

        if ($host !== '') {
            $host = strtolower(trim($host, '[] '));

            // Localhost and loopback
            if ($host === 'localhost' || $host === '0.0.0.0' || $host === '::1' || $host === '::') {
                return true;
            }

            // Decimal IP encoding (e.g. 2130706433 = 127.0.0.1)
            if (ctype_digit($host)) {
                $long = (int) $host;
                if ($long >= 0 && $long <= 4294967295) {
                    $ip = long2ip($long);
                    if ($this->isPrivateOrReservedIp($ip)) {
                        return true;
                    }
                }
            }

            // Hex IP encoding (e.g. 0x7f.0x0.0x0.0x1 or 0x7f000001)
            if (str_starts_with($host, '0x')) {
                return true;
            }

            // Direct IP validation
            if (filter_var($host, FILTER_VALIDATE_IP)) {
                if ($this->isPrivateOrReservedIp($host)) {
                    return true;
                }
            }

            // Octal IP check
            if (preg_match('/^0\d+(\.|$)/', $host)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address belongs to private or reserved ranges.
     */
    public function isPrivateOrReservedIp(string $ip): bool
    {
        return ! filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }
}
