@props(['html', 'showImages' => false])

<?php
$sanitizer = app(\App\Services\MessageSanitizer::class);
$renderHtml = $html;
if (!$showImages) {
    $renderHtml = $sanitizer->blockRemoteImages($html);
}
$renderHtml = $sanitizer->sanitizeUrls($renderHtml);

$blockedLinkCss = '<style>
a.link-blocked {
    text-decoration: line-through !important;
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    color: #8b90a0 !important;
    position: relative;
}
a.link-blocked:hover::after {
    content: attr(data-original-href) " — Link blocked for security";
    position: absolute;
    bottom: 100%;
    left: 0;
    background: #1a1d23;
    color: #e8eaef;
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    white-space: nowrap;
    z-index: 10;
    pointer-events: none;
}
</style>';

$doc = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base target="_blank">
    ' . $blockedLinkCss . '
    <style>
        html {
            overflow-y: auto;
        }
        body {
            margin: 0;
            padding: 1.5rem;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1a1d23;
            background-color: transparent;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.5;
        }
        img {
            max-width: 100%;
            height: auto;
        }
        table {
            max-width: 100% !important;
        }
        pre {
            white-space: pre-wrap;
            word-break: break-word;
        }
    </style>
</head>
<body>' . $renderHtml . '</body>
</html>';
?>

<div x-data="{
    showImages: @js($showImages),
    resize() {
        const frame = this.$refs.emailFrame;
        if (!frame) return;
        try {
            const doc = frame.contentDocument || frame.contentWindow?.document;
            if (doc && doc.body) {
                const height = Math.max(
                    doc.body.scrollHeight,
                    doc.body.offsetHeight,
                    doc.documentElement.scrollHeight,
                    doc.documentElement.offsetHeight
                );
                if (height > 0) {
                    frame.style.height = (height + 24) + 'px';
                }
            }
        } catch (e) {
            // Sandboxed fallback
        }
    },
    init() {
        const frame = this.$refs.emailFrame;
        if (frame) {
            frame.addEventListener('load', () => {
                this.resize();
                try {
                    const doc = frame.contentDocument || frame.contentWindow?.document;
                    if (doc && doc.body && window.ResizeObserver) {
                        const observer = new ResizeObserver(() => this.resize());
                        observer.observe(doc.body);
                    }
                } catch (e) {}
            });
        }
        this.$nextTick(() => this.resize());
    }
}" class="email-renderer">
    {{-- Remote images blocked banner --}}
    <div x-show="!showImages" x-transition
         class="m-4 p-3 bg-warning-subtle border border-warning/20 rounded-lg flex items-center justify-between">
        <p class="text-xs text-warning flex items-center gap-2">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
            </svg>
            Remote images are blocked for privacy.
        </p>
        <button wire:click="toggleImages"
                class="text-xs font-semibold text-primary hover:text-primary-hover ml-3 flex-shrink-0 transition-colors">
            Display images
        </button>
    </div>

    {{-- Sandboxed iframe --}}
    <iframe
        title="Email content"
        sandbox="allow-same-origin allow-popups allow-popups-to-escape-sandbox"
        srcdoc="{{ $doc }}"
        class="w-full border-0 block"
        style="min-height: 200px; overflow: hidden;"
        @load="resize()"
        x-ref="emailFrame"
    ></iframe>
</div>
