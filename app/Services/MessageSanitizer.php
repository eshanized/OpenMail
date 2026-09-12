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
        $config->set('HTML.AllowedElements', [
            'p', 'br', 'a', 'img', 'table', 'tr', 'td', 'th', 'div', 'span',
            'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'ul', 'ol', 'li', 'blockquote',
            'pre', 'code', 'strong', 'em', 'u', 's', 'sub', 'sup',
        ]);
        $config->set('HTML.AllowedAttributes', [
            'a.href', 'a.target', 'a.rel', 'a.title',
            'img.src', 'img.alt', 'img.width', 'img.height', 'img.style',
            'table.border', 'table.cellpadding', 'table.cellspacing', 'table.width',
            'td.colspan', 'td.rowspan', 'td.width', 'td.height',
            'th.colspan', 'th.rowspan', 'th.width', 'th.height',
            'div.style', 'span.style', 'p.style',
            'h1.style', 'h2.style', 'h3.style', 'h4.style', 'h5.style', 'h6.style',
            'ul.style', 'ol.style', 'li.style',
            'blockquote.style', 'pre.style', 'code.style',
            'strong.style', 'em.style', 'u.style', 's.style', 'sub.style', 'sup.style',
        ]);
        $config->set('CSS.AllowedProperties', [
            'color', 'background-color', 'font-size', 'font-family', 'font-weight',
            'font-style', 'text-align', 'text-decoration', 'margin', 'margin-top',
            'margin-right', 'margin-bottom', 'margin-left', 'padding', 'padding-top',
            'padding-right', 'padding-bottom', 'padding-left', 'border', 'border-top',
            'border-right', 'border-bottom', 'border-left', 'width', 'height',
            'float', 'clear',
        ]);
        $config->set('URI.AllowedSchemes', ['http', 'https', 'mailto']);
        $config->set('AutoFormat.AutoParagraph', true);
        $config->set('AutoFormat.RemoveEmpty', true);
        $config->set('Core.Encoding', 'UTF-8');

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

    public function blockRemoteImages(string $html): string
    {
        if (! str_contains(strtolower($html), '<img')) {
            return $html;
        }

        $dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $encoded = mb_encode_numericentity($html, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8');
        $dom->loadHTML($encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('img') as $img) {
            $src = $img->getAttribute('src');
            if ($src && (str_starts_with($src, 'http://') || str_starts_with($src, 'https://'))) {
                $img->setAttribute('data-src', $src);
                $img->setAttribute('src', 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);
        $output = $body ? $dom->saveHTML($body) : $dom->saveHTML();

        return preg_replace('/<\!--\?xml.*?-->\s*/i', '', $output);
    }

    /**
     * Sanitize signature HTML content (SEC-07).
     * Reuses the purifier for safe rendering in composer and message view.
     */
    public function sanitizeSignatureHtml(string $html): string
    {
        return $this->getPurifier()->purify($html);
    }

    /**
     * Sanitize URLs in message HTML content (SEC-07).
     * Blocks dangerous schemes (javascript:, data:, vbscript:, file:)
     * and private IP addresses to prevent SSRF attacks.
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
     * Used by sanitizeUrls() to neutralize malicious links.
     *
     * Blocks: javascript/data/vbscript/file schemes, private/reserved IPs,
     * decimal/octal/hex IP encodings, IPv6 private ranges, and URL-encoded
     * bypass variants.
     */
    public function isDangerousUrl(string $url): bool
    {
        $normalized = rawurldecode($url);

        // Block javascript:, data:, vbscript:, file: schemes
        if (preg_match('/^(javascript|data|vbscript|file):/i', $normalized)) {
            return true;
        }

        // Block localhost and private/reserved IPv4 ranges
        if (preg_match('/^https?:\/\/(localhost|0\.0\.0\.0|127\.\d{1,3}\.\d{1,3}\.\d{1,3})/i', $normalized)) {
            return true;
        }

        // RFC 1918 private ranges
        if (preg_match('/^https?:\/\/(10\.\d{1,3}\.\d{1,3}\.\d{1,3}|172\.(1[6-9]|2\d|3[01])\.\d{1,3}\.\d{1,3}|192\.168\.\d{1,3}\.\d{1,3})/i', $normalized)) {
            return true;
        }

        // Link-local
        if (preg_match('/^https?:\/\/169\.254\.\d{1,3}\.\d{1,3}/i', $normalized)) {
            return true;
        }

        // IPv6 loopback and private ranges
        if (preg_match('/^https?:\/\/\[?(::1|::ffff:127\.\d{1,3}\.\d{1,3}\.\d{1,3}|fc00|fd[0-9a-f]{2}|fe80|::)/i', $normalized)) {
            return true;
        }

        // Decimal IP (e.g. http://2130706433 = 127.0.0.1)
        // Block 9+ digit numbers (covers 10.0.0.0/8 through 255.255.255.255)
        if (preg_match('/^https?:\/\/\d{9,}(\:|\/|$)/i', $normalized)) {
            return true;
        }

        // Octal IP (e.g. http://0177.0.0.1)
        if (preg_match('/^https?:\/\/0\d{2,3}\.\d{1,3}\.\d{1,3}/i', $normalized)) {
            return true;
        }

        // Hex IP (e.g. http://0x7f.0x0.0x0.0x1)
        if (preg_match('/^https?:\/\/0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}\.0x[0-9a-f]{1,2}/i', $normalized)) {
            return true;
        }

        return false;
    }
}
