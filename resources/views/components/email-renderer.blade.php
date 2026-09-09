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
    opacity: 0.55 !important;
    cursor: not-allowed !important;
    color: #94a3b8 !important;
    position: relative;
    background: rgba(148, 163, 184, 0.1);
    padding: 0 0.25rem;
    border-radius: 0.25rem;
}
a.link-blocked:hover::after {
    content: "🔒 Blocked link: " attr(data-original-href);
    position: absolute;
    bottom: calc(100% + 4px);
    left: 0;
    background: #0f172a;
    color: #f8fafc;
    padding: 0.35rem 0.65rem;
    border-radius: 0.5rem;
    font-size: 0.75rem;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    font-weight: 500;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    white-space: nowrap;
    z-index: 50;
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
            -webkit-text-size-adjust: 100%;
        }
        body {
            margin: 0;
            padding: 1.75rem;
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: #1e293b;
            background-color: transparent;
            word-break: break-word;
            overflow-wrap: break-word;
            line-height: 1.65;
            font-size: 15px;
            letter-spacing: -0.01em;
        }
        body.dark-adaptive {
            color: #e2e8f0;
        }
        body.dark-adaptive a {
            color: #60a5fa !important;
        }
        body.dark-adaptive blockquote {
            border-left-color: #3b82f6 !important;
            background: rgba(59, 130, 246, 0.08) !important;
            color: #cbd5e1 !important;
        }
        body.dark-adaptive code {
            background: #1e293b !important;
            color: #f1f5f9 !important;
        }
        body.dark-adaptive pre {
            background: #0f172a !important;
            color: #f1f5f9 !important;
        }
        body.dark-adaptive table th, 
        body.dark-adaptive table td {
            border-color: #334155 !important;
        }
        a {
            color: #2563eb;
            text-decoration: underline;
            text-underline-offset: 2px;
            transition: color 0.15s ease;
        }
        a:hover {
            color: #1d4ed8;
        }
        img {
            max-width: 100%;
            height: auto;
            border-radius: 0.375rem;
        }
        table {
            max-width: 100% !important;
            border-collapse: collapse;
            margin: 1rem 0;
        }
        table th, table td {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
        }
        blockquote {
            margin: 1rem 0;
            padding: 0.625rem 1.125rem;
            border-left: 3.5px solid #2563eb;
            background: rgba(37, 99, 235, 0.04);
            border-radius: 0 0.5rem 0.5rem 0;
            color: #475569;
        }
        code {
            font-family: "SF Mono", Consolas, Monaco, monospace;
            background: #f1f5f9;
            color: #0f172a;
            padding: 0.15rem 0.35rem;
            border-radius: 0.25rem;
            font-size: 0.88em;
        }
        pre {
            white-space: pre-wrap;
            word-break: break-word;
            background: #0f172a;
            color: #f8fafc;
            padding: 1rem 1.25rem;
            border-radius: 0.625rem;
            font-size: 0.88em;
            line-height: 1.5;
            overflow-x: auto;
        }
        pre code {
            background: transparent;
            color: inherit;
            padding: 0;
        }
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.75rem 0;
        }
    </style>
</head>
<body>' . $renderHtml . '</body>
</html>';
?>

<div x-data="{
    showImages: @js($showImages),
    readingMode: 'full', // 'focused' or 'full'
    zoomLevel: 100, // percentage
    adaptiveTheme: false,
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
                    frame.style.height = (height + 32) + 'px';
                }
            }
        } catch (e) {
            // Sandboxed fallback
        }
    },
    toggleReadingMode() {
        this.readingMode = this.readingMode === 'focused' ? 'full' : 'focused';
        this.$nextTick(() => this.resize());
    },
    changeZoom(delta) {
        this.zoomLevel = Math.min(150, Math.max(80, this.zoomLevel + delta));
        const frame = this.$refs.emailFrame;
        if (frame) {
            try {
                const doc = frame.contentDocument || frame.contentWindow?.document;
                if (doc && doc.body) {
                    doc.body.style.zoom = (this.zoomLevel / 100);
                    this.resize();
                }
            } catch (e) {}
        }
    },
    resetZoom() {
        this.zoomLevel = 100;
        const frame = this.$refs.emailFrame;
        if (frame) {
            try {
                const doc = frame.contentDocument || frame.contentWindow?.document;
                if (doc && doc.body) {
                    doc.body.style.zoom = '1';
                    this.resize();
                }
            } catch (e) {}
        }
    },
    toggleAdaptiveTheme() {
        this.adaptiveTheme = !this.adaptiveTheme;
        const frame = this.$refs.emailFrame;
        if (frame) {
            try {
                const doc = frame.contentDocument || frame.contentWindow?.document;
                if (doc && doc.body) {
                    if (this.adaptiveTheme) {
                        doc.body.classList.add('dark-adaptive');
                    } else {
                        doc.body.classList.remove('dark-adaptive');
                    }
                }
            } catch (e) {}
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
}" class="email-renderer w-full">

    {{-- Reader Control Toolbar --}}
    <div class="email-control-bar">
        <div class="flex items-center gap-1">
            {{-- Reading Mode Toggle (Focused vs Full) --}}
            <button
                type="button"
                @click="toggleReadingMode()"
                class="email-control-btn"
                :class="{ 'active': readingMode === 'focused' }"
                :title="readingMode === 'focused' ? 'Switch to Full Width' : 'Switch to Focused Reading Width'"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                </svg>
                <span x-text="readingMode === 'focused' ? 'Focused' : 'Full width'">Full width</span>
            </button>

            {{-- Zoom Controls --}}
            <div class="inline-flex items-center rounded-lg border border-border-subtle bg-surface-sunken/60 p-0.5 ml-1">
                <button
                    type="button"
                    @click="changeZoom(-10)"
                    class="p-1 text-ink-tertiary hover:text-ink rounded hover:bg-hover transition-colors"
                    title="Zoom Out"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="resetZoom()"
                    class="px-1.5 py-0.5 text-[11px] font-semibold text-ink-secondary hover:text-ink transition-colors font-mono"
                    title="Reset Zoom"
                    x-text="zoomLevel + '%'"
                >100%</button>
                <button
                    type="button"
                    @click="changeZoom(10)"
                    class="p-1 text-ink-tertiary hover:text-ink rounded hover:bg-hover transition-colors"
                    title="Zoom In"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-1">
            {{-- Dark mode adaptive toggle for email canvas --}}
            <button
                type="button"
                @click="toggleAdaptiveTheme()"
                class="email-control-btn"
                :class="{ 'active': adaptiveTheme }"
                title="Toggle eye-comfort dark reading canvas"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z"/>
                </svg>
                <span class="hidden sm:inline">Dark view</span>
            </button>

            {{-- Print email --}}
            <button
                type="button"
                onclick="window.print()"
                class="email-control-btn"
                title="Print this message"
            >
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m0 0a48.113 48.113 0 018.5 0"/>
                </svg>
                <span class="hidden sm:inline">Print</span>
            </button>
        </div>
    </div>

    {{-- Remote images blocked banner (Modern Privacy Shield) --}}
    <div x-show="!showImages" x-transition class="email-privacy-shield">
        <div class="flex items-center gap-2.5 min-w-0">
            <div class="w-7 h-7 rounded-lg bg-warning/15 text-warning flex items-center justify-center flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-ink">
                    Remote images are blocked for privacy.
                </p>
                <p class="text-[11px] text-ink-tertiary hidden sm:block">
                    Tracking pixels and external resources are prevented from loading.
                </p>
            </div>
        </div>
        <button
            wire:click="toggleImages"
            wire:loading.attr="disabled"
            class="px-3 py-1.5 text-xs font-semibold text-white bg-primary hover:bg-primary-hover rounded-lg shadow-sm transition-all duration-150 flex-shrink-0 flex items-center gap-1.5 cursor-pointer"
        >
            <span wire:loading.remove wire:target="toggleImages">Display images</span>
            <span wire:loading wire:target="toggleImages">Loading...</span>
        </button>
    </div>

    {{-- Email Reading Surface Container --}}
    <div
        class="email-reading-surface"
        :class="{ 'mode-focused': readingMode === 'focused', 'mode-full': readingMode === 'full' }"
    >
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
</div>

