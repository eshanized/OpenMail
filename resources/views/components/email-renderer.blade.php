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
$srcdoc = base64_encode('<meta charset="utf-8">' . $blockedLinkCss . $renderHtml);
?>

<div x-data="{ showImages: @js($showImages) }" class="email-renderer">
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
        sandbox=""
        :srcdoc="atob('{{ $srcdoc }}')"
        class="w-full border border-border rounded-lg"
        style="min-height: 300px;"
        onload="this.style.height = this.contentDocument.body.scrollHeight + 20 + 'px';"
        x-ref="emailFrame"
    ></iframe>
</div>
