# Phase 6: UI/UX Polish - Context

**Gathered:** 2026-09-08
**Status:** Ready for planning

<domain>
## Phase Boundary

Visual design improvements across the entire application — modern color scheme with vibrant gradients, glass-effect components, smooth animations, and refined typography. This phase transforms the functional UI into a polished, premium experience without changing features or capabilities.

</domain>

<decisions>
## Implementation Decisions

### Color Palette & Theming
- **D-01:** Vibrant gradient theme — Blue → Purple gradient for primary actions (Send, Compose, Archive) — **Reversibility:** reversible — CSS gradient definitions only; changing palette is local
- **D-02:** Light mode surfaces: Warm Cream (#fafaf9) — softer on eyes, less stark than pure white — **Reversibility:** reversible — CSS custom property change
- **D-03:** Dark mode surfaces: Soft Dark (#1a1a2e) — easier on eyes than true black, less stark — **Reversibility:** reversible — CSS custom property change
- **D-04:** Primary gradient: Blue (#3b82f6) → Purple (#8b5cf6) for buttons, links, and accent elements — **Reversibility:** reversible — gradient definitions in CSS

### Component Restyling
- **D-05:** Primary buttons: Solid color with glow shadow on hover — blue bg, white text, rounded-lg, glow effect — **Reversibility:** reversible — CSS class changes only
- **D-06:** Text inputs: Glass effect — semi-transparent background with backdrop-blur, focus ring — **Reversibility:** reversible — CSS class changes; backdrop-filter widely supported
- **D-07:** Cards/containers: Glass card — semi-transparent bg with backdrop-blur, subtle border — **Reversibility:** reversible — CSS class changes only
- **D-08:** Status badges: Soft tag style — light bg with colored text, rounded — **Reversibility:** reversible — CSS class changes only

### Animations & Transitions
- **D-09:** Page load: Fade-in animation (150-200ms opacity) — subtle, professional — **Reversibility:** reversible — CSS transition changes only
- **D-10:** Hover effects: Scale (1.02) + glow shadow on interactive elements — playful, modern — **Reversibility:** reversible — CSS transition changes only
- **D-11:** Loading states: Skeleton screens — gray placeholder shapes matching content layout — **Reversibility:** reversible — New component additions only
- **D-12:** Route transitions: Fade cross — old page fades out, new fades in — **Reversibility:** reversible — Livewire/Alpine transition config

### Typography & Spacing
- **D-13:** Font family: Plus Jakarta Sans — geometric, modern, premium feel — **Reversibility:** reversible — Font import + CSS change
- **D-14:** Base font size: 14px (0.875rem) — compact, more content visible like Gmail — **Reversibility:** reversible — CSS custom property change
- **D-15:** Spacing: Balanced — comfortable breathing room, not too tight or loose — **Reversibility:** reversible — Density system already exists; adjust defaults
- **D-16:** Line height: 1.4 — tighter, more text per line, denser feel — **Reversibility:** reversible — CSS custom property change

### the agent's Discretion
- Exact gradient color stops and CSS implementation (Tailwind gradient utilities or custom CSS)
- Glass effect backdrop-blur values and opacity levels
- Animation timing functions and easing curves
- Skeleton screen shape patterns per component type
- Font weight variations for headings vs body
- Focus ring styles and accessibility contrast ratios
- Dark mode gradient adjustments (if gradients need different stops)
- Responsive breakpoint adjustments for mobile glass effects

</decisions>

<canonical_refs>
## Canonical References

**Downstream agents MUST read these before planning or implementing.**

### Project Definition
- `.planning/PROJECT.md` — Project context, requirements, constraints, key decisions
- `.planning/REQUIREMENTS.md` — Full v1 requirements
- `.planning/ROADMAP.md` — Phase details, success criteria, dependency graph

### Stack & Architecture
- `AGENTS.md` §Technology Stack — Laravel 12, Livewire 3, Tailwind 4, Alpine.js
- `AGENTS.md` §Architecture Decision Records — ADR-001 through ADR-004

### Phase 5 Decisions (Carried Forward)
- `.planning/phases/05-security-polish/05-CONTEXT.md` — Theme/density system (CSS custom properties, class-based dark mode), Settings page structure, Tailwind 4 configuration

### Existing Codebase Patterns
- `resources/css/app.css` — Current CSS with density system, Tiptap styles, dark mode variant — extend for new color palette, glass effects, animations
- `resources/views/layouts/mailbox.blade.php` — Mailbox layout with sidebar — restyle with glass effects
- `resources/views/layouts/app.blade.php` — Base layout with theme initializer — update font import, color variables
- `tailwind.config.js` or `vite.config.js` — Build configuration — may need new Tailwind plugins for glass effects

No external specs — requirements fully captured in decisions above.

</canonical_refs>

<code_context>
## Existing Code Insights

### Reusable Assets
- **CSS Custom Properties System** (app.css lines 179-225): Density variables (--spacing-unit, --font-size-base, --line-height-base) — extend with color variables for new palette
- **Dark Mode Variant** (app.css line 187): `@custom-variant dark` — class-based dark mode already works; new colors just need dark: variants
- **Glass Effect Foundation**: No existing glass styles — need to add backdrop-blur utilities and semi-transparent backgrounds
- **Animation Utilities**: No existing animation utilities — need to add transition classes and keyframe animations

### Established Patterns
- Tailwind 4 CSS-first configuration — no tailwind.config.js; use @theme directive
- Alpine.js for interactivity — transitions via x-transition directives
- Livewire wire:navigate for SPA-like navigation — route transitions need Livewire/Alpine integration
- CSS custom properties for theming — extend pattern with color variables

### Integration Points
- `resources/css/app.css` — Main stylesheet; add new color palette, glass effects, animations here
- `resources/views/layouts/app.blade.php` — Add Plus Jakarta Sans font import
- `resources/views/layouts/mailbox.blade.php` — Apply glass effects to sidebar, restyle components
- Individual component views — Apply new button, input, badge styles

</code_context>

<specifics>
## Specific Ideas

- Gmail/Outlook as density benchmarks — compact but readable, more content per screen
- Glass effects should be subtle — not too transparent, maintain readability
- Animations should be quick (150-200ms) — email clients need to feel fast
- Skeleton screens for message list loading, folder loading, search results
- Gradient only on primary actions — don't overuse; keep secondary actions neutral
- Plus Jakarta Sans has nice weight variations — use for visual hierarchy
- Warm cream background reduces eye strain for daily email use
- Soft dark mode (#1a1a2e) is easier on eyes than pure black for evening use

</specifics>

<deferred>
## Deferred Ideas

None — discussion stayed within phase scope.

</deferred>

---

*Phase: 6-UI/UX Polish*
*Context gathered: 2026-09-08*
