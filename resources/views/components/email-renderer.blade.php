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
        @media (max-width: 640px) {
            body {
                padding: 1rem 0.75rem !important;
                font-size: 14px !important;
            }
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
    <div class="email-control-bar border-b border-border bg-surface-sunken px-2.5 sm:px-4 py-1.5 sm:py-2 flex items-center justify-between gap-1.5 sm:gap-2 flex-wrap sm:flex-nowrap">
        <div class="flex items-center gap-1">
            {{-- Reading Mode Toggle (Focused vs Full) --}}
            <button
                type="button"
                @click="toggleReadingMode()"
                class="email-control-btn px-2 py-1 text-xs text-ink-secondary hover:text-ink rounded transition-colors"
                :class="{ 'font-semibold text-primary': readingMode === 'focused' }"
                :title="readingMode === 'focused' ? 'Switch to Full Width' : 'Switch to Focused Reading Width'"
            >
                <span x-text="readingMode === 'focused' ? 'Focused width' : 'Full width'">Full width</span>
            </button>

            {{-- Zoom Controls --}}
            <div class="inline-flex items-center rounded border border-border bg-surface-raised px-1 py-0.5 ml-2 gap-1 text-xs">
                <button
                    type="button"
                    @click="changeZoom(-10)"
                    class="p-0.5 text-ink-tertiary hover:text-ink rounded transition-colors"
                    title="Zoom Out"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 12h-15"/>
                    </svg>
                </button>
                <button
                    type="button"
                    @click="resetZoom()"
                    class="px-1 text-[11px] font-mono text-ink-secondary hover:text-ink transition-colors"
                    title="Reset Zoom"
                    x-text="zoomLevel + '%'"
                >100%</button>
                <button
                    type="button"
                    @click="changeZoom(10)"
                    class="p-0.5 text-ink-tertiary hover:text-ink rounded transition-colors"
                    title="Zoom In"
                >
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Dark mode adaptive toggle for email canvas --}}
            <button
                type="button"
                @click="toggleAdaptiveTheme()"
                class="email-control-btn text-xs text-ink-secondary hover:text-ink px-2 py-1 rounded transition-colors"
                :class="{ 'text-primary font-medium': adaptiveTheme }"
                title="Toggle eye-comfort dark reading canvas"
            >
                <span class="hidden sm:inline">Dark canvas</span>
            </button>

            {{-- Print email --}}
            <button
                type="button"
                onclick="window.print()"
                class="email-control-btn text-xs text-ink-secondary hover:text-ink px-2 py-1 rounded transition-colors"
                title="Print this message"
            >
                <span>Print</span>
            </button>
        </div>
    </div>

    {{-- Remote images blocked banner (Utility notice) --}}
    <div x-show="!showImages" x-transition class="border-b border-border bg-surface-sunken px-4 py-2 flex items-center justify-between gap-3 text-xs">
        <div class="flex items-center gap-2 text-ink-secondary">
            <svg class="w-3.5 h-3.5 text-ink-tertiary shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
            </svg>
            <span>Remote images blocked for privacy.</span>
        </div>
        <button
            wire:click="toggleImages"
            wire:loading.attr="disabled"
            class="text-xs font-medium text-primary hover:underline cursor-pointer flex-shrink-0"
        >
            <span wire:loading.remove wire:target="toggleImages">Display images</span>
            <span wire:loading wire:target="toggleImages">Loading…</span>
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

