<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Stricter CSP preset for report-only testing.
 *
 * This preset can be used alongside or instead of OpenMailPreset
 * to test a more restrictive policy. It intentionally omits
 * 'unsafe-inline' to identify which inline scripts/styles need
 * refactoring before enforce mode.
 *
 * Use this to gradually tighten CSP:
 * 1. Deploy with OpenMailPreset (allows unsafe-inline)
 * 2. Add OpenMailReportOnlyPreset to report_only_presets
 * 3. Monitor violations from the stricter policy
 * 4. Refactor inline code to use nonces
 * 5. Switch to enforce mode with stricter policy
 */
class OpenMailReportOnlyPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            // Base: restrict to self
            ->add(Directive::BASE, Keyword::SELF)

            // Form actions: self only
            ->add(Directive::FORM_ACTION, Keyword::SELF)

            // Objects: none
            ->add(Directive::OBJECT, Keyword::NONE)

            // Frame ancestors: none
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)

            // Scripts: nonce only (no unsafe-inline) — stricter testing
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)

            // Styles: nonce only (no unsafe-inline) — stricter testing
            ->add(Directive::STYLE, Keyword::SELF)
            ->addNonce(Directive::STYLE)

            // Images: same as enforced policy
            ->add(Directive::IMG, Keyword::SELF)
            ->add(Directive::IMG, 'data:')
            ->add(Directive::IMG, 'https:')

            // Connect: self only
            ->add(Directive::CONNECT, Keyword::SELF)

            // Fonts: self + Google Fonts
            ->add(Directive::FONT, Keyword::SELF)
            ->add(Directive::FONT, 'fonts.gstatic.com')

            // Media: self only
            ->add(Directive::MEDIA, Keyword::SELF)

            // Child src: self
            ->add(Directive::CHILD, Keyword::SELF);
    }
}
