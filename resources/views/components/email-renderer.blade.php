@props(['html', 'showImages' => false])

<?php
$sanitizer = app(\App\Services\MessageSanitizer::class);
$renderHtml = $html;
if (!$showImages) {
    $renderHtml = $sanitizer->blockRemoteImages($html);
}
// SSRF protection: sanitize URLs to block dangerous schemes and private IPs (SEC-07)
$renderHtml = $sanitizer->sanitizeUrls($renderHtml);
// Add link-blocked styles inside the iframe for blocked link visual indication
$blockedLinkCss = '<style>
a.link-blocked {
    text-decoration: line-through !important;
    opacity: 0.5 !important;
    cursor: not-allowed !important;
    color: #9ca3af !important;
    position: relative;
}
a.link-blocked:hover::after {
    content: attr(data-original-href) " — Link blocked for security";
    position: absolute;
    bottom: 100%;
    left: 0;
    background: #1f2937;
    color: #f9fafb;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
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
    <div x-show="!showImages" class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4" x-transition>
        <p class="text-sm text-yellow-800">
            This email contains remote images that are blocked for privacy.
            <button wire:click="toggleImages" class="underline font-medium text-yellow-900 hover:text-yellow-700 ml-2">
                Display images
            </button>
        </p>
    </div>

    {{-- Sandboxed iframe for HTML email --}}
    <iframe
        title="Email content"
        sandbox=""
        :srcdoc="atob('{{ $srcdoc }}')"
        class="w-full border border-gray-200 rounded-lg"
        style="min-height: 400px;"
        onload="this.style.height = this.contentDocument.body.scrollHeight + 20 + 'px';"
        x-ref="emailFrame"
    ></iframe>
</div>