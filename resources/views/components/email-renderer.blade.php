@props(['html', 'showImages' => false])

<?php
$sanitizer = app(\App\Services\MessageSanitizer::class);
$renderHtml = $html;
if (!$showImages) {
    $renderHtml = $sanitizer->blockRemoteImages($html);
}
// Base64 encode for safe srcdoc attribute
$srcdoc = base64_encode('<meta charset="utf-8">' . $renderHtml);
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