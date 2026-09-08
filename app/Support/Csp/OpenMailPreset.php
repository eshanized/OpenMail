<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * CSP preset for OpenMail's Livewire 3 + Alpine.js + Vite stack.
 *
 * Configures directives to allow:
 * - Nonce-based script/style loading via Vite
 * - 'unsafe-inline' fallback for Livewire 3 component hydration and Alpine.js inline handlers
 * - Self-hosted and remote images (user opt-in for remote)
 * - Livewire wire:navigate and API connections
 * - Google Fonts for typography
 *
 * This preset is designed for report-only mode first (D-01).
 * After violation review, switch to enforce mode.
 */
class OpenMailPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            // Base: restrict to self by default
            ->add(Directive::BASE, Keyword::SELF)

            // Form actions: self only (prevents form hijacking)
            ->add(Directive::FORM_ACTION, Keyword::SELF)

            // Objects: none (no plugins allowed)
            ->add(Directive::OBJECT, Keyword::NONE)

            // Frame ancestors: none (prevents clickjacking, complement to X-Frame-Options)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)

            // Scripts: nonce + unsafe-inline fallback for Livewire 3/Alpine.js
            // Livewire 3 uses inline scripts for component hydration
            // Alpine.js uses inline event handlers (x-on:click, etc.)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::SCRIPT, Keyword::UNSAFE_INLINE)

            // Styles: nonce + unsafe-inline for Tailwind utility classes and Alpine inline styles
            ->add(Directive::STYLE, Keyword::SELF)
            ->addNonce(Directive::STYLE)
            ->add(Directive::STYLE, Keyword::UNSAFE_INLINE)

            // Images: self + data: (inline images) + https: (remote images user opts into)
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'https:')

            // Connect: self for Livewire wire:navigate, fetch API calls
            ->add(Directive::CONNECT, Keyword::SELF)

            // Fonts: self + Google Fonts CDN
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FONT, 'fonts.gstatic.com')

            // Media: self only (audio/video elements)
            ->add(Directive::MEDIA, Keyword::SELF)

            // Child src: self (for any child frames/windows)
            ->add(Directive::CHILD, Keyword::SELF);
    }
}
