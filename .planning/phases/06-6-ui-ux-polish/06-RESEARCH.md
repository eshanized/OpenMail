# Phase 6: UI/UX Polish - Research

**Researched:** 2026-09-09
**Domain:** Tailwind CSS v4 design-system theming, Livewire 4 loading & navigation UX, Alpine.js transitions, self-hosted variable-font delivery, WCAG 2.x color contrast, glassmorphism
**Confidence:** HIGH (stack verified in-repo; API behavior fetched from official Livewire/Tailwind/Alpine docs)

## Summary

Phase 6 restyles the whole OpenMail UI — gradient primary actions, glass cards/inputs, fade animations, skeleton loading screens, route transitions, Plus Jakarta Sans typography — on top of a working Tailwind 4 (4.3.3) CSS-first codebase. Every requested effect maps to **built-in Tailwind 4 utilities or Livewire 4 features**: no new Tailwind plugins, no new frontend frameworks, and exactly **one new dependency** (`@fontsource-variable/plus-jakarta-sans`, legitimacy-verified).

The one significant stack hazard is **documentation drift**: `AGENTS.md`/`STACK.md` prescribe Livewire 3.x, but the installed version is **livewire/livewire 4.4.3** [VERIFIED: composer.lock]. Livewire 4 reworked loading states around a `data-loading` attribute system ([CITED: livewire.laravel.com/docs/4.x/loading-states] — "Prefer data-loading over wire:loading") with Tailwind variants `data-loading:*`, `not-data-loading:*`, `in-data-loading:*`, `has-data-loading:*`, `peer-data-loading:*` — the code examples here target the actual installed versions. Tailwind v4 also renamed gradients (`bg-linear-to-*`, not `bg-gradient-to-*`) and re-scaled blur utilities ([CITED: tailwindcss.com/docs/upgrade-guide]).

Two decision-level recommendations prior to planning:
1. **D-04 gradient fails WCAG AA** for 14px white text: #3b82f6 → 3.68:1, #8b5cf6 → 4.23:1 (need 4.5:1). The discretion area explicitly covers "exact gradient color stops" and "accessibility contrast ratios", so this research recommends **#2563eb → #9333ea** (5.17:1 / 5.38:1 — passing). Blue/purple *600* becomes the implementation stop for all text-bearing elements.
2. **D-12 cross-fade**: Livewire 4's HTML swap is synchronous and cannot be delayed, so a true DOM cross-fade (old fades out, THEN new swaps in) is unsupported. The recommended implementation is a fixed, theme-aware **route overlay** that fades in on `livewire:navigating` and fades out on `livewire:navigated` — producing the locked visual effect with deterministic timing and no interference with morphing.

**Primary recommendation:** Plan Phase 6 as CSS-first work — (1) `npm install @fontsource-variable/plus-jakarta-sans` and import it in `resources/js/app.js`; (2) extend `resources/css/app.css` with `@theme` semantic tokens + `.dark` overrides, keyframes, glass component styles, and the missing `[x-cloak]` rule; (3) restyle views with built-in utilities (`bg-linear-to-*`, `backdrop-blur-sm`, `data-loading:` variants, Alpine `x-transition`); (4) add a ~30-line route-overlay hook in `app.js`; (5) replace the full-screen spinner overlay (`message-list.blade.php:203`) with per-component skeletons driven by `wire:loading`.

<user_constraints>
## User Constraints (from CONTEXT.md)

### Locked Decisions
#### Color Palette & Theming
- **D-01:** Vibrant gradient theme — Blue → Purple gradient for primary actions (Send, Compose, Archive) — **Reversibility:** reversible — CSS gradient definitions only; changing palette is local
- **D-02:** Light mode surfaces: Warm Cream (#fafaf9) — softer on eyes, less stark than pure white — **Reversibility:** reversible — CSS custom property change
- **D-03:** Dark mode surfaces: Soft Dark (#1a1a2e) — easier on eyes than true black, less stark — **Reversibility:** reversible — CSS custom property change
- **D-04:** Primary gradient: Blue (#3b82f6) → Purple (#8b5cf6) for buttons, links, and accent elements — **Reversibility:** reversible — gradient definitions in CSS

#### Component Restyling
- **D-05:** Primary buttons: Solid color with glow shadow on hover — blue bg, white text, rounded-lg, glow effect — **Reversibility:** reversible — CSS class changes only
- **D-06:** Text inputs: Glass effect — semi-transparent background with backdrop-blur, focus ring — **Reversibility:** reversible — CSS class changes; backdrop-filter widely supported
- **D-07:** Cards/containers: Glass card — semi-transparent bg with backdrop-blur, subtle border — **Reversibility:** reversible — CSS class changes only
- **D-08:** Status badges: Soft tag style — light bg with colored text, rounded — **Reversibility:** reversible — CSS class changes only

#### Animations & Transitions
- **D-09:** Page load: Fade-in animation (150-200ms opacity) — subtle, professional — **Reversibility:** reversible — CSS transition changes only
- **D-10:** Hover effects: Scale (1.02) + glow shadow on interactive elements — playful, modern — **Reversibility:** reversible — CSS transition changes only
- **D-11:** Loading states: Skeleton screens — gray placeholder shapes matching content layout — **Reversibility:** reversible — New component additions only
- **D-12:** Route transitions: Fade cross — old page fades out, new fades in — **Reversibility:** reversible — Livewire/Alpine transition config

#### Typography & Spacing
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

### Deferred Ideas (OUT OF SCOPE)
None — discussion stayed within phase scope.
</user_constraints>

## Phase Requirements

> ROADMAP.md has no requirement IDs assigned to Phase 6 yet (success criteria TBD). The binding requirement set is the 16 locked decisions above plus UI-SPEC.md (phase deliverable this milestone). Mapping below ties each decision to its research support so the planner can translate decisions into plans.

| Decision | Research Support |
|----------|------------------|
| D-01 Gradient primary actions | `bg-linear-to-*` + `from-*`/`to-*` stops are the canonical v4 gradient utilities [VERIFIED: tailwindcss.com/docs/background-image, fetched 2026-09-09]. Bridges to D-04; see Contrast Table for stop selection. |
| D-02 Cream #fafaf9 light surface | `--color-surface` token; supported by `@theme` [VERIFIED: tailwindcss.com/docs/theme]. Contrast table: pairs fine with 600-level accents; gray-400 muted text FAILS (use gray-500). |
| D-03 Soft dark #1a1a2e dark surface | Same token mechanism with `.dark` runtime override of the custom property; all dark-mode text pairs pass (16.3:1 worst) — see Contrast Table. `.dark` class already wired via `@custom-variant dark` [VERIFIED: resources/css/app.css:187]. |
| D-04 Gradient #3b82f6→#8b5cf6 | **Fails AA for 14px white text (3.68:1 / 4.23:1).** Discretion covers stops; recommend **#2563eb→#9333ea** (5.17:1 / 5.38:1) — see Contrast Table. |
| D-05 Button glow+scale | `--shadow-glow` token in `@theme` + `hover:scale-[1.02]`. v4 `scale` is an individual transitionable property and `transition` includes it [VERIFIED: tailwindcss.com/docs/upgrade-guide — individual properties]. 150ms ease-out; `motion-reduce:transform-none`. |
| D-06 Glass inputs | `backdrop-blur-sm` = 8px in v4 scale [VERIFIED: tailwindcss.com/docs/upgrade-guide — blur scale]; `focus:ring-2 ring-blue-600/35` (600 for AA); dark: `bg-white/10`, light: `bg-white/60`. Needs `@supports` fallback — see Pattern 4. |
| D-07 Glass cards | Pattern 4: `backdrop-filter` stack + `-webkit-` prefix + fallback; invisible over flat cream/#1a1a2e without varied backdrop (blob layer) — see Pitfall 4. |
| D-08 Status badges | Translucent tinted bg + 600-level text on cream (red-600 #dc2626 = 4.62:1 PASS vs red-500 3.60 FAIL; gray-500 for muted 5.11 PASS vs gray-400 2.43 FAIL). |
| D-09 Page fade 150-200ms | `--animate-fade-in` token with nested `@keyframes fade-in` inside `@theme` [VERIFIED: tailwindcss.com/docs/animation — keyframes in @theme]. Applies to main content region; replays on SPA nav via hook (Pattern 3) or forced reflow. Gate with `prefers-reduced-motion`. |
| D-10 Hover scale+glow | Same mechanism as D-05; keep transform-only (never animate backdrop-filter — see Pitfall 6). |
| D-11 Skeleton screens | Livewire 4 `wire:loading` (component-scoped, `wire:target` for cross-element triggers) + `data-loading:` variants for same-element swaps [CITED: livewire.laravel.com/docs/4.x/loading-states]. Replaces overlay at message-list.blade.php:203 [VERIFIED: quote below in Pitfall 5]. `animate-pulse` default skeleton or custom shimmer `--animate-shimmer`. |
| D-12 Route cross-fade | Livewire 4 swap is synchronous — no delay hook; true double-sided cross-fade unsupported. Overlay approach (Pattern 3) achieves locked intent deterministically. Planner should confirm this interpretation at plan time (see Open Question 1). |
| D-13 Plus Jakarta Sans | `@fontsource-variable/plus-jakarta-sans` 5.3.0 — legitimacy OK, self-hosted (zero external requests → no CSP edits, see Pattern font section). Family name is "Plus Jakarta Sans Variable". |
| D-14 Base 14px | Already satisfied: `--font-size-base: 0.875rem` [VERIFIED: resources/css/app.css:182]. No change needed. |
| D-15 Balanced spacing | Density system already exists (`.density-*` blocks, app.css:190-225). Adjust default `--spacing-unit` only if cream/dark tokens make current values feel off; keep 3 modes. |
| D-16 Line height 1.4 | **Conflict:** `.density-regular` sets `--line-height-base: 1.5` [VERIFIED: app.css:199] and `.density-compact` sets 1.4 [VERIFIED: app.css:193]. Implementing D-16 requires updating the `.density-regular` block (and the duplicate `@utility density-regular` at 215-218) to 1.4. Impact: thread-list row heights; verify density still feels right. |

## Architectural Responsibility Map

| Capability | Primary Tier | Secondary Tier | Rationale |
|------------|-------------|----------------|-----------|
| Color tokens, glass, gradients, animations | Browser / Client (CSS) | CDN / Static | Compiled by Vite into static CSS; shipped same-origin; runtime `.dark` overrides live in CSS |
| Font delivery | CDN / Static (Vite build) | — | @fontsource WOFF2 emitted into build by Vite; no runtime server involvement |
| Skeleton loading states | API / Backend (Livewire) | Browser / Client | Livewire round-trips set `data-loading` and drive `wire:loading`; browser renders the skeleton markup |
| Route transitions (SPA swap) | API / Backend (Livewire navigate) | Browser / Client | wire:navigate performs the swap server-agnostically; fade overlay is a small browser-side hook on Livewire's events |
| Theme/density preference persistence | API / Backend (database) | Browser / Client | Preference stored server-side; inline initializer applies `<html>` classes (existing Phase 5 pattern) |
| CSP policy for styles/fonts | API / Backend (middleware) | — | spatie/laravel-csp emits headers; self-hosted fonts require no policy relaxation |

## Standard Stack

### Core

| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| tailwindcss | 4.3.3 [VERIFIED: node_modules] | Utility CSS + `@theme` tokens | Installed; canonical v4 CSS-first; gradients/glass/animations all built-in |
| @tailwindcss/vite | 4.3.3 [VERIFIED: node_modules] | Vite plugin | Installed; no tailwind.config.js (CSS-first confirmed) |
| vite | 7.3.6 [VERIFIED: node_modules] | Asset build | Installed; `npm run build` emits fonts/styles |
| livewire/livewire | 4.4.3 [VERIFIED: composer.lock] | SPA navigation + loading states | Installed. **Not 3.x as AGENTS.md says** — target v4 docs exclusively |
| @fontsource-variable/plus-jakarta-sans | ^5.3.0 | Self-hosted variable font | Sole new dependency; CSP-safe (no external hosts); legitimacy OK |
| spatie/laravel-csp | 3.28.3 [VERIFIED: composer.lock] | CSP headers + nonces | Installed, Phase 5 wired; no policy changes needed for self-hosted assets |

### Supporting

| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| Alpine.js | bundled with Livewire 4 [VERIFIED: vendor/livewire/livewire/dist/livewire.js] | x-transition, x-show, x-cloak | No npm install; import from Livewire bundle only |
| PHPUnit | 11.5.56 [VERIFIED: composer.lock (packages-dev)] | Feature tests | `php artisan test` |
| MyCLabs/… (existing Tiptap deps) | — | Editor (unchanged) | Tiptap styles exist in app.css; migrate hardcoded hex to tokens where touching |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| @fontsource (self-hosted) | Google Fonts CDN `<link>` | CDN leaks requests to fonts.googleapis.com + requires CSP `style-src`/`font-src` relaxation; self-hosting needs zero policy edits |
| Variable font package | @fontsource/plus-jakarta-sans (static weights) | Variable = single WOFF2 with full weight range; family name differs by the suffix "Variable" (only visible in DevTools, not visually) |
| Livewire `data-loading:` variants | `wire:loading` everywhere | data-loading is the v4-recommended default for same-element swaps; `wire:loading` still required for sibling/cross-element cases (folder switch → list skeleton) |
| Route-overlay cross-fade | Custom SPA router / full reload per nav | Overlay reuses Livewire's own morph swap; a hand-rolled router breaks every wire: feature |

**Installation:**
```bash
npm install @fontsource-variable/plus-jakarta-sans
```

**Version verification (run 2026-09-09):** all stack versions read from installed `composer.lock`, `node_modules`, and `public/build` this session — not from training data.

## Package Legitimacy Audit

> Protocol: seam check `package-legitimacy` + `npm view` + postinstall inspection. Both candidates verified 2026-09-09.

| Package | Registry | Age | Downloads | Source Repo | Verdict | Disposition |
|---------|----------|-----|-----------|-------------|---------|-------------|
| @fontsource-variable/plus-jakarta-sans | npm | v5.3.0 (2026-07-19); org ~5 yrs | 272,780/wk | github.com/fontsource/font-files | OK | **Approved** — `npm install` in plan |
| @fontsource/plus-jakarta-sans | npm | v5.3.0 | 220,834/wk | github.com/fontsource/font-files | OK | Approved as alternative (static weights) |

- **Postinstall scripts:** none on either package (`npm view <pkg> scripts.postinstall` → empty) — no high-risk install behavior.
- **Discovery path:** fontsource.org (official project site) fetched this session, plus seam verdict OK → tagged `[VERIFIED: npm registry]`.
- **Packages removed due to [SLOP] verdict:** none
- **Packages flagged as suspicious [SUS]:** none
- No composer-side packages are added by this phase.

## Architecture Patterns

### System Architecture Diagram

```
                      ┌────────────────────────────────────────────────────┐
                      │  PUBLIC  (deployment: cPanel)                      │
                      └──────────────────────┬─────────────────────────────┘
                                             │ HTTP
                              ┌──────────────▼───────────────┐
                              │  Laravel 12 (PHP 8.5)        │
                              │  spatie/laravel-csp headers  │
                              │  + nonces                    │
                              └──┬───────────────┬───────────┘
                                 │              │
      ┌────────────────────────  ▼              ▼ ─────────────────────────┐
      │  app.blade.php         [Livewire 4.4.3]  [theme/density prefs DB] │
      │  (theme initializer,   │                 │                        │
      │   CSP nonce tags,      │  wire:click / wire:model / $dispatch     │
      │   #route-overlay)      │                 │                        │
      │                        ▼                 ▼                        │
      │  Livewire components: message-list, message-row, folder-sidebar, │
      │  search-results-dropdown, appearance-tab …                       │
      │   └─ loading states: data-loading attr (auto) + wire:loading     │
      └───────────────────────────┬───────────────────────────────────────┘
                                  │ HTML + Alpine (bundled Alpine 3.x)
                    ┌─────────────▼─────────────┐
                    │  BROWSER                  │
                    │  Alpine x-transition      │
                    │  app.js hooks:            │
                    │   livewire:navigating →   │  route overlay fade
                    │   livewire:navigated  →   │  (150ms in/out)
                    │   forced-reflow replay    │  page fade-in (D-09)
                    └─────────────┬─────────────┘
                                  │  ┌──────────────────────────────┐
                                  └──│ Vite build (contrib/CI):      │
                                     │ app.css → tokens/utilities    │
                                     │ @fontsource WOFF2 (same-origin│
                                     │ app.js → hooks                │
                                     └──────────────────────────────┘
```
Data flow for the primary use case (compose): user clicks Send (gradient button) → Livewire action → server validates/persists → response morph → Alpine transitions update UI. Data flow for loading: any wire: action/update sets `data-loading` on the trigger element while the round-trip is in flight; message-list/folder skeletons show via `wire:loading` until the swapped HTML replaces them.

### Recommended Project Structure

Only the following files change or are added this phase (everything else is untouched):

```
resources/
├── css/
│   └── app.css                      # EXTEND: @theme tokens, keyframes, glass classes,
│                                    #          [x-cloak], shimmer, reduced-motion guard
├── js/
│   └── app.js                       # ADD: route-overlay hook (~30 lines, vanilla JS)
└── views/
    ├── layouts/
    │   ├── app.blade.php            # ADD: #route-overlay element after <body> content
    │   └── mailbox.blade.php        # RESTYLE: sidebar glass, mobile overlay transition
    ├── livewire/
    │   ├── mailbox/
    │   │   ├── message-list.blade.php        # REPLACE overlay (line 203) with skeleton
    │   │   ├── message-row.blade.php         # RESTYLE rows (hover state, density)
    │   │   ├── folder-sidebar.blade.php      # RESTYLE active tab + folder rows
    │   │   └── search-results-dropdown.blade.php  # MATCH new skeleton style (lines 55-75)
    │   └── settings/
    │       └── appearance-tab.blade.php      # RESTYLE theme/density pickers
    └── components/ (new, optional)
        ├── ui/buttons/primary-gradient.blade.php  # reusable gradient button partial
        ├── ui/glass-card.blade.php                # glass card partial w/ fallback class
        └── ui/skeleton/message-row.blade.php      # skeleton shape partial (D-11)
```

No `tailwind.config.js` (CSS-first, verified absent). No new Blade layout. Optional partials are only worth creating if the same markup appears 2+ times (Compose/Send/Archive buttons qualify).

### Pattern 1: Tailwind v4 token architecture (`@theme` + runtime dark overrides)

**What:** Define semantic tokens once in `@theme` inside app.css; Tailwind generates `bg-surface`, `text-surface`, `shadow-glow`, `animate-fade-in` utilities from them. Keyframes live *inside* `@theme` so `--animate-*` tokens carry their keyframes.

**When to use:** All new colors, shadows, easing, and animations for this phase.

```css
/* resources/css/app.css — extend existing block (currently app.css:8-11 [VERIFIED]) */
@theme {
    --font-sans: 'Plus Jakarta Sans Variable', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';

    /* Semantic surfaces (D-02, D-03) — utilities: bg-surface, text-surface … */
    --color-surface: #fafaf9;        /* light: warm cream */
    --color-surface-raised: #ffffff; /* light: cards on cream */
    --color-ink: #1f2937;            /* primary text (gray-800) */

    /* Brand (D-01/D-04) — use *600 stops for text-bearing elements (see Contrast Table) */
    --color-primary: #2563eb;        /* blue-600 */
    --color-accent: #9333ea;         /* purple-600 */
    --color-primary-deep: #3b82f6;   /* blue-500 — decorative-large use only */
    --color-accent-deep: #8b5cf6;    /* violet-500 — decorative-large use only */

    /* Effects (D-05/D-10) */
    --shadow-glow: 0 4px 20px -6px rgb(37 99 235 / 0.45);
    --shadow-glow-strong: 0 8px 28px -8px rgb(147 51 234 / 0.55);

    /* Animations (D-09) — keyframes nested inside @theme [VERIFIED: tailwindcss.com/docs/animation] */
    --animate-fade-in: fade-in 0.18s ease-out;
    --animate-shimmer: shimmer 1.4s linear infinite;

    @keyframes fade-in {
        from { opacity: 0; }
        to   { opacity: 1; }
    }
    @keyframes shimmer {
        from { background-position: 200% 0; }
        to   { background-position: -200% 0; }
    }
}

/* Runtime dark overrides: utilities resolve var(--color-*) at use-site, so a plain
   .dark-scoped override wins (equal specificity to :root, later in file). */
:where(.dark) {
    --color-surface: #1a1a2e;        /* soft dark (D-03) */
    --color-surface-raised: #23233c; /* raised dark card */
    --color-ink: #e5e7eb;
    --route-overlay-bg: #1a1a2e;     /* used by route overlay below */
}
:where(:root) {
    --route-overlay-bg: #fafaf9;     /* light default for #route-overlay */
}
```

### Pattern 2: Livewire 4 loading states — skeletons (D-11)

**What:** Livewire 4 auto-adds a `data-loading` attribute to the element that triggered the request; Tailwind v4 variants style it. For same-element swaps use `not-data-loading:hidden`; for sibling/cross-component cases (folder click in the sidebar loading the message list) use classic `wire:loading` with `wire:target`.

**When to use:** `data-loading:` for button-internal spinners/text swap; `wire:loading` + `wire:target="selectFolder, refresh"` for the message-list skeleton (trigger is a different component/sibling).

```blade
{{-- CITED: livewire.laravel.com/docs/4.x/loading-states — "Prefer data-loading over wire:loading" --}}
{{-- Same-element swap: submit text → spinner, no wire:target needed --}}
<button wire:click="send" class="rounded-lg bg-linear-to-r from-blue-600 to-purple-600 ...">
    <span class="not-data-loading:inline">Send</span>
    <span class="not-data-loading:hidden inline-flex items-center gap-2">
        <svg class="w-4 h-4 animate-spin" ...></svg> Sending…
    </span>
</button>

{{-- Sibling-scoped skeleton: folder click in sidebar → list skeleton (classic wire:loading) --}}
<div wire:loading wire:target="selectFolder" class="space-y-3"> …skeleton rows… </div>
<div wire:loading.remove wire:target="selectFolder" class="divide-y divide-gray-100">
    …real message rows (@foreach over $messages)…
</div>
```

Key nuance: `data-loading` only lands on the **trigger element** — a message list updated by a sidebar folder click does NOT carry the attribute, so `wire:loading` + `wire:target` remains the correct tool there. Delay modifiers (`wire:loading.delay`) prevent skeleton flash on fast folder switches.

### Pattern 3: Route cross-fade overlay (D-12)

**What:** Livewire 4's swap is synchronous — no official delay/await hook, so a true DOM cross-fade is unsupported. An opaque, theme-aware overlay fades in over the old page on `livewire:navigating` (150ms), the swap happens invisibly beneath it, then it fades out on `livewire:navigated` (150ms) to reveal the new page — the locked "old fades out, new fades in" effect with deterministic timing.

**When to use:** Every wire:navigate transition. Also replay the D-09 content fade-in on `livewire:navigated` (morphing preserves the layout element, so a plain CSS animation would only run on full loads — force a reflow to restart it, or toggle a class).

```blade
{{-- app.blade.php — place just inside <body>, before @yield content --}}
<div id="route-overlay"
     class="pointer-events-none fixed inset-0 z-[9999] opacity-0 transition-opacity duration-150"
     style="background-color: var(--route-overlay-bg)"
     aria-hidden="true"></div>
```

```js
// resources/js/app.js — tail of file
// CITED: livewire.laravel.com/docs/4.x/navigate (livewire:navigating / livewire:navigated)
const overlay = () => document.getElementById('route-overlay');
document.addEventListener('livewire:navigating', () => overlay()?.classList.add('opacity-100'));
document.addEventListener('livewire:navigated',  () => overlay()?.classList.remove('opacity-100'));

// D-09 page fade replay after SPA swaps (morph preserves the main element, so re-trigger)
document.addEventListener('livewire:navigated', () => {
    const main = document.getElementById('app-main');
    if (! main) return;
    main.classList.remove('animate-fade-in');
    void main.offsetWidth; // force reflow to restart animation
    main.classList.add('animate-fade-in');
});
```

Note: listeners persist across navigations; no cleanup needed for document-level page-load hooks like these.

### Pattern 4: Glass surfaces (D-06, D-07)

**What:** Glass = translucent fill + `backdrop-filter` blur (4–8px subtle, 10–16px pronounced; cap 16px) + 1px light border + soft shadow. With @supports fallback for older browsers. **Glass is invisible over flat solid backgrounds** — subtle gradient blobs behind panels make the blur visible (see Pitfall 4).

**When to use:** Inputs, sidebar container, cards — max 2–3 glass elements per viewport, never on list rows (GPU cost), never animated blur radius.

```html
<!-- Input (D-06): 8px = backdrop-blur-sm on the v4 scale [VERIFIED: upgrade guide] -->
<input type="text"
       class="w-full rounded-lg border border-gray-200/80 bg-white/60
              backdrop-blur-sm px-3 py-2 text-sm
              focus:border-blue-600 focus:ring-2 focus:ring-blue-600/35
              dark:border-white/10 dark:bg-white/10 dark:focus:border-blue-400 motion-reduce:backdrop-blur-none" />

<!-- Card (D-07) -->
<div class="glass-card rounded-xl border border-white/60 bg-white/60 backdrop-blur-sm
            shadow-[0_8px_24px_-16px_rgba(30,27,75,0.35)]
            dark:border-white/10 dark:bg-white/10">
    …content…
</div>
```

```css
/* app.css — component class + @supports fallback (fill becomes near-solid when no backdrop) */
@utility glass-card {
    /* Tailwind v4 auto-prefixes backdrop-filter via Lightning CSS [CITED: upgrade guide];
       the -webkit- line below is belt-and-braces for hand-written CSS reuse */
    -webkit-backdrop-filter: blur(8px);
    backdrop-filter: blur(8px);
}
@supports not ((backdrop-filter: blur(8px)) or (-webkit-backdrop-filter: blur(8px))) {
    .glass-card, .glass-input {
        background-color: rgb(250 250 249 / 0.92);        /* light fallback */
    }
    :where(.dark) .glass-card, :where(.dark) .glass-input {
        background-color: rgb(26 26 46 / 0.92);           /* dark fallback */
    }
}
```

Dark-mode tint guidance: light `white/60` over cream; dark `white/10` over #1a1a2e keeps AA contrast with gray-300+ ink.

### Pattern 5: Gradient buttons with accessible stops (D-01, D-04, D-05)

**What:** The locked 500-level stops fail AA for 14px white text; the discretion explicitly covers stops and contrast. Use 600-level stops for anything text-bearing; keep 500-level only for large decorative elements (no text).

```html
{{-- Primary action (Send / Compose / Archive) — white on #2563eb→#9333ea = 5.17:1/5.38:1 PASS --}}
<button class="group inline-flex items-center gap-2 rounded-lg
               bg-linear-to-r from-blue-600 to-purple-600
               px-4 py-2 text-sm font-semibold text-white
               shadow-glow
               transition duration-150 ease-out
               hover:scale-[1.02] hover:shadow-glow-strong
               focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2
               focus-visible:ring-blue-600
               motion-reduce:transform-none motion-reduce:transition-none">
    {{ $slot ?? 'Send' }}
</button>
```

### Anti-Patterns to Avoid

- **Gradient on every button:** D-01 is explicit — gradient on primary actions only; secondary actions stay neutral (gray/white surfaces). Overuse reads as "2018 gradient meme".
- **Glass over flat backgrounds:** `backdrop-blur` over uniform cream or #1a1a2e shows *nothing* — the blur samples a flat image. Add subtle radial gradient blobs behind the glass layer (fixed, `pointer-events-none`, aria-hidden) so the blur has something to diffuse, and remember `motion-reduce`/`reduced-transparency` users get solid fallback.
- **`wire:loading` without `wire:target`:** defaults to *every* request in the component (that is how the current overlay at message-list.blade.php:203 behaves — it fires on any mailbox round-trip). Always scope with `wire:target` for the message list, or opt into `data-loading:` for same-element swaps.
- **Animating backdrop-filter:** transitions on `backdrop-filter` are GPU-expensive and janky — animate opacity/transform only.
- **Re-adding a full-screen spinner:** Phase's own D-11 supersedes the overlay; do not keep both.
- **Hand-writing @font-face:** exactly what @fontsource exists to avoid (subsetting, unicode-range, weight maps, metadata).

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Font delivery | Hand-written `@font-face` / Google Fonts `<link>` | `@fontsource-variable/plus-jakarta-sans` | Subsetting, unicode-range, weight swapping, and multi-language coverage are packaged; self-hosting avoids CSP edits and third-party requests |
| Loading-state management | Per-component `is-loading` JS flags / axios interceptors | Livewire 4 `data-loading` attribute + `wire:loading` + `wire:target` | Auto-scoped to the triggering element, works across component boundaries and events, ships in the installed dist |
| SPA navigation | Custom router / full-page reloads per click | `wire:navigate` (already in use) + event hooks | Morph diffing, scroll/focus management, prefetch, and back/forward handling are Livewire's job |
| CSS vendor prefixing | Autoprefixer / manual `-webkit-` everywhere | Tailwind v4's Lightning CSS pipeline | Prefixing is built-in; legacy configs are obsolete |
| Focus visible styling | Random `:focus { outline: none }` + bespoke states | Tailwind `ring-*` utilities + `focus-visible:` variants | Token-driven width/offset/color, WCAG 2.4.7 focus-visible behavior included |
| Route transition orchestration | A second SPA layer to "fix" Livewire's synchronous swap | Overlay fade (Pattern 3) on Livewire's own events | Adding a competing router breaks morphing, wire:*, and server-rendered state |

**Key insight:** every effect in this phase is a *styling/declarative* problem, not a *behavioral* problem. Livewire 4.4.3 + Tailwind 4.3.3 ship 95% of it; the only hand-written JS is the ~30-line route-overlay hook. Anything that feels like it needs a new framework or a new server dependency is a sign of over-engineering (no Redis, no queue, no JS framework — per project constraints).

## Common Pitfalls

### Pitfall 1: Documentation drift — Livewire 3.x vs installed 4.4.3
**What goes wrong:** AGENTS.md/STACK.md prescribe Livewire 3.x, but composer.lock locks `livewire/livewire 4.4.3` [VERIFIED: composer.lock]. Copy-pasting 3.x examples (e.g. `wire:loading` defaults, `@entangle` quirks, `wire:navigate` event semantics) can quietly produce unexpected behavior.
**Why it happens:** The stack doc predates the Phase 1-5 dependency resolves; nobody re-verified after install.
**How to avoid:** Use only `livewire.laravel.com/docs/4.x/*` pages as the API reference. STACK.md's "avoid Livewire 4.x" recommendation is now moot — the app already runs 4.4.3, and downgrading is out of scope.
**Warning signs:** References to `this.$wire`, `@this`, or v3-only modifiers in fetched snippets; any code tagged as "Livewire 3" in search results.

### Pitfall 2: Tailwind gradient utility rename (`bg-gradient-to-*` → `bg-linear-to-*`)
**What goes wrong:** v3-era snippets use `bg-gradient-to-r`; v4's canonical name is `bg-linear-to-r` with the same `from-*`/`via-*`/`to-*` stops [VERIFIED: tailwindcss.com/docs/background-image, fetched 2026-09-09]. A stale `bg-gradient-to-*` class either silently no-ops or requires the legacy alias (unverified), breaking all gradient buttons.
**Why it happens:** Two-thirds of web tutorials still show the v3 name.
**How to avoid:** Use `bg-linear-to-*` only. Add a plan verification step: grep built CSS for `bg-gradient-to-` (should be absent) and for `--tw-gradient` stops.
**Warning signs:** Gradients missing in `npm run build` output while `from-*`/`to-*` classes are present in markup.

### Pitfall 3: WCAG contrast failures on cream (D-02)
**What goes wrong:** Several default Tailwind colors fail AA on #fafaf9 (Contrast Table below). Blue-500 links (3.52:1), gray-400 muted text (2.43:1), and red-500 destructive badges (3.60:1) all fail the 4.5:1 normal-text bar.
**Why it happens:** Cream is brighter than #fff, so any colored element slightly darker loses more contrast than designers expect.
**How to avoid:** Standardize on 600-level stops for text-bearing accents (#2563eb, #9333ea, #dc2626) and gray-500+ for muted text (5.11:1 PASS). Keep 500-level only for large/decorative fills. Confirmed pairs in the Contrast Table.
**Warning signs:** `text-blue-500`, `bg-red-500`, `text-gray-400` in new markup on cream surfaces.

### Contrast Table (WCAG 2.x relative-luminance formula, locally computed 2026-09-09 [VERIFIED: computation — WCAG 2.2 Understanding 1.4.3 formula])

| Pair | Ratio | AA normal (4.5) | AA large (3.0) |
|------|-------|-----------------|----------------|
| #ffffff / #3b82f6 (blue-500) | 3.68 | FAIL | PASS |
| #ffffff / #8b5cf6 (violet-500) | 4.23 | FAIL | PASS |
| #ffffff / #2563eb (blue-600) | 5.17 | PASS | PASS |
| #ffffff / #9333ea (purple-600) | 5.38 | PASS | PASS |
| #3b82f6 / #fafaf9 (cream) | 3.52 | FAIL | PASS |
| #2563eb / #fafaf9 (cream) | 4.95 | PASS | PASS |
| #9ca3af (gray-400) / #fafaf9 | 2.43 | FAIL | FAIL |
| #6b7280 (gray-500) / #fafaf9 | 5.11 | PASS | PASS |
| #ef4444 (red-500) / #fafaf9 | 3.60 | FAIL | PASS |
| #dc2626 (red-600) / #fafaf9 | 4.62 | PASS | PASS |
| #fafaf9 / #1a1a2e (dark surface) | 16.33 | PASS | PASS |
| #ffffff / #1a1a2e | 17.06 | PASS | PASS |
| #9ca3af (gray-400) / #1a1a2e | 6.21 | PASS | PASS |
| #d1d5db (gray-300) / #1a1a2e | ~8.9 | PASS | PASS |

D-04's locked 500-level gradient gets **white text on 3.68:1/4.23:1** — below 4.5:1 for 14px semibold (not "large text" per WCAG, which requires ≥18pt/24px or ≥14pt bold). Skeleton gray-300 shapes (~1.4:1) are decorative/transient and exempt from non-text contrast (WCAG 1.4.11) — acceptable.

### Pitfall 4: Glass is invisible over flat backgrounds
**What goes wrong:** `backdrop-blur` diffuses whatever is *behind* the element; behind a uniform cream or #1a1a2e fill it produces no visible change, so "glass cards" look like slightly-transparent gray boxes.
**Why it happens:** backdrop-filter is a sampling operation, not a material.
**How to avoid:** Add a fixed, non-interactive decorative layer of soft gradient blobs (blue/purple, 20-30% opacity, blur-3xl) behind glass panels — `pointer-events-none` + `aria-hidden="true"`, `z-0` with glass at `z-10`. Keep 2-3 blobs max. Disable for `prefers-reduced-motion` if they animate.
**Warning signs:** Screenshot review shows no depth difference with/without the glass layer; hover-state tests on a flat screenshot look identical.

### Pitfall 5: `x-cloak` is inert app-wide (latent flash bug + transition blocker)
**What goes wrong:** Six views use `x-cloak` (folder-sidebar, label-sidebar, label-modal, message-toolbar, label-chips) but **no CSS rule hides it** anywhere: `resources/css/app.css` has no `[x-cloak]`, `public/build/assets/*.css` has no `[x-cloak]`, and the Livewire 4 bundled Alpine only *removes* the attribute at init — it never injects a hiding rule [VERIFIED in-repo: vendor/livewire/livewire/dist/livewire.js:149406 — `directive("cloak", (el) => queueMicrotask(() => mutateDom(() => el.removeAttribute(prefix("cloak")))))`]. Result: hidden-by-default panels flash visible before Alpine boots (visible on slow cPanel/FTP deploys).
**Why it happens:** The Alpine default build used to include the style; the modular bundling into Livewire does not.
**How to avoid:** Add one rule to app.css as part of this phase:
```css
[x-cloak] { display: none !important; }
```
This also makes x-transition enter/leave fade-ins (which this phase adds to those same panels) actually start from hidden instead of flashing.
**Warning signs:** Panels appear briefly when loading a mailbox page on throttled network.

### Pitfall 6: Animation + backdrop-filter performance
**What goes wrong:** `transition` on `backdrop-filter` or animating blur radius causes jank (each frame re-samples the backdrop). 12px+ blur over large areas is GPU-hungry on shared-hosting-era hardware.
**Why it happens:** Blur is one of the most expensive compositing operations.
**How to avoid:** Animate only `opacity`/`transform`/`box-shadow`/colors; keep blur ≤8px for panels, ≤16px absolute cap; max 2-3 glass elements per viewport; never glass on list rows; guard with `motion-reduce:` (disable blur too).
**Warning signs:** DevTools performance flame shows long `Rasterize`/`GPU process` frames during transitions.

### Pitfall 7: Vite CSS `@import` of npm packages
**What goes wrong:** `@import '@fontsource-variable/plus-jakarta-sans';` at the top of app.css must be resolved by Vite's CSS pipeline; ordering mistakes (an @import after any other rule) are invalid CSS and silently drop the fonts.
**Why it happens:** CSS spec requires all @imports before other statements; build-time resolution of bare specifiers differs from PostCSS expectations.
**How to avoid:** Import the font in `resources/js/app.js` instead — `import '@fontsource-variable/plus-jakarta-sans';` — Vite guarantees JS-side CSS imports are bundled, emitted as a hashed same-origin asset. (CSS-side @import also works when kept at the top, but the JS import removes the ordering footgun.) Either way, the font file lands in `public/build` — verify `manifest.json` contains the fontsource css.

### Pitfall 8: `backdrop-filter` creates a containing block for fixed descendants
**What goes wrong:** A `position: fixed` child (dropdown, modal) inside a glass element positions relative to the glass (with padding-box origin) instead of the viewport — appearing cut off or mis-positioned.
**Why it happens:** Any `filter`/`backdrop-filter` on an ancestor establishes a containing block for fixed/absolute descendants.
**How to avoid:** Do not nest popovers/modals inside glass containers; keep `#route-overlay` and dropdowns outside the glass layer (siblings, not children). Note: the existing route overlay is `body`-level, which is safe.

## Code Examples

Verified patterns from official sources (all targets the installed versions: Tailwind 4.3.3, Livewire 4.4.3, Alpine bundled with Livewire):

### 1. Self-hosted font + theme token swap (D-13)
```js
// resources/js/app.js — add BEFORE any app-specific logic
import '@fontsource-variable/plus-jakarta-sans';
```
```css
/* app.css — replace existing --font-sans (app.css:9-10 [VERIFIED]) */
@theme {
    --font-sans: 'Plus Jakarta Sans Variable', ui-sans-serif, system-ui, sans-serif,
        'Apple Color Emoji', 'Segoe UI Emoji', 'Segoe UI Symbol', 'Noto Color Emoji';
}
```
The variable package covers weights 200-800; use 400 body / 600 headings & buttons (D-13's "nice weight variations" intent [CITED: fontsource.org, npm view]).

### 2. Gradient primary button (D-01/D-04/D-05) — accessible stops
```html
<a href="{{ route('compose') }}" wire:navigate
   class="inline-flex items-center gap-2 rounded-lg bg-linear-to-r from-blue-600 to-purple-600
          px-4 py-2 text-sm font-semibold text-white shadow-glow
          transition duration-150 ease-out hover:scale-[1.02] hover:shadow-glow-strong
          focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600 focus-visible:ring-offset-2
          motion-reduce:transform-none">
    <svg class="w-4 h-4" ...></svg>
    Compose
</a>
```

### 3. Message-list skeleton replacing the overlay (D-11)
```blade
{{-- Replace overlay at message-list.blade.php:203 [VERIFIED quote: <div wire:loading class="fixed inset-0 bg-white/80 z-50 flex items-center justify-center" style="display: none;">] --}}
{{-- Skeleton (shows during list round-trips) --}}
<div wire:loading wire:target="selectFolder" class="divide-y divide-gray-100 dark:divide-white/5" aria-hidden="true">
    @foreach([1, 2, 3, 4, 5] as $i)
        <div class="flex items-start gap-3 px-4 py-3">
            <div class="h-9 w-9 rounded-full bg-gray-200 dark:bg-white/10 animate-pulse"></div>
            <div class="flex-1 space-y-2">
                <div class="h-3 w-1/3 rounded bg-gray-200 dark:bg-white/10 animate-pulse"></div>
                <div class="h-3 w-3/4 rounded bg-gray-200 dark:bg-white/10 animate-pulse"></div>
                <div class="h-3 w-1/2 rounded bg-gray-200 dark:bg-white/10 animate-pulse"></div>
            </div>
        </div>
    @endforeach
</div>
{{-- Real content (hidden while loading) --}}
<div wire:loading.remove wire:target="selectFolder" class="divide-y divide-gray-100 dark:divide-white/5">
    @foreach($messages as $message)
        <livewire:mailbox.message-row :message="$message"
            :selected="$selectedUids->contains($message->uid)" :key="$message->uid" />
    @endforeach
</div>
```
Same pattern (with `wire:target` per action) for folder-sidebar folder switches and search dropdown (which already has an Alpine-flag skeleton at search-results-dropdown.blade.php:55-75 [VERIFIED] — restyle it to match).

### 4. Alpine transitions on existing panels (D-09/D-12 supporting)
```blade
{{-- Consistent with existing mobile overlay pattern in mailbox.blade.php (x-transition opacity, VERIFIED in-repo) --}}
<div x-show="open" x-transition:enter="transition ease-out duration-150"
     x-transition:enter-start="opacity-0 translate-y-1"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-100"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-1"
     x-cloak>
</div>
```
[VERIFIED: alpinejs.dev/directives/transition — full enter/leave class syntax for x-transition]

### 5. Reduced-motion + reduced-transparency guard (a11y gate)
```css
/* app.css — global guard (WCAG 2.3.3 + 1.4.11 intent) */
@media (prefers-reduced-motion: reduce) {
    *, ::before, ::after {
        animation-duration: 0.01ms !important;
        animation-iteration-count: 1 !important;
        transition-duration: 0.01ms !important;
        scroll-behavior: auto !important;
    }
    #route-overlay { display: none; }            /* skip cross-fade */
}
@media (prefers-reduced-transparency: reduce) {
    .glass-card, .glass-input {
        background-color: rgb(250 250 249 / 0.95) !important;
        -webkit-backdrop-filter: none !important;
        backdrop-filter: none !important;
    }
    :where(.dark) .glass-card, :where(.dark) .glass-input {
        background-color: rgb(26 26 46 / 0.95) !important;
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| tailwind.config.js (JS config, `content`, keyframes in JS) | CSS-first `@theme` + `@utility` in app.css | Tailwind v4 (Jan 2025) | This phase's tokens/animations all live in app.css; no config file exists in the repo |
| `bg-gradient-to-r` utilities | `bg-linear-to-r` (same stops) | Tailwind v4 | Use canonical name only (Pitfall 2) |
| Legacy blur scale (`blur-sm` = 4px, bare `blur` = 8px) | v4 scale: `backdrop-blur-xs` = 4px, `backdrop-blur-sm` = 8px | Tailwind v4 | D-06's "8px" = `backdrop-blur-sm` in v4 |
| Transforms composed from `transform: translate() scale()` | Individual CSS properties (`translate`, `scale`, `rotate`) | Tailwind v4 | `hover:scale-[1.02]` transitions independently; no transform-composition bugs |
| `wire:loading` as primary loading tool | Livewire 4 `data-loading` attribute + Tailwind `data-loading:` variants | Livewire 4.x (2026) | "Prefer data-loading over wire:loading" [CITED: docs 4.x/loading-states]; `wire:loading` still needed for cross-element triggers |
| Google Fonts CDN `<link>` | Self-hosted variable fonts via @fontsource | 2020-2026 maturation | Zero external requests; CSP immutable (no font-src/style-src changes); faster on shared hosting |
| Automatic vendor prefixing via Autoprefixer/PostCSS config | Built into Tailwind v4 (Lightning CSS) | Tailwind v4 | No config changes; `-webkit-backdrop-filter` handled for utilities |

**Deprecated/outdated:**
- `tailwind.config.js` — obsolete; leave it absent. All theming moves to `@theme` in app.css (current file already CSS-first).
- Autoprefixer/legacy PostCSS presets — v4 prefixes automatically.
- Google Fonts `<link>`/`@import` from fonts.googleapis.com — replaced by @fontsource self-hosting (privacy + CSP).
- Full-screen loading spinners for mailbox round-trips — replaced by skeletons (D-11); the overlay at message-list.blade.php:203 is the phase's removal target.

## Assumptions Log

| # | Claim | Section | Risk if Wrong |
|---|-------|---------|---------------|
| A1 | `bg-gradient-to-*` legacy alias behavior in v4.3 is unverified (may silently no-op or work via alias) | Standard Stack / Pitfall 2 | Low — plan mandates canonical `bg-linear-to-*` only; a stray v3 name failing to compile is caught by build output check |
| A2 | Vite resolves npm-package `@import` inside CSS files; the JS-side import in `app.js` is the guaranteed path | Code Examples / Pitfall 7 | Low — if CSS-side @import misbehaves, the JS import (documented primary) still works |
| A3 | `.dark { --color-*: … }` runtime overrides of `@theme` tokens reliably retarget generated utilities | Pattern 1 | Medium — standard v4 practice but not fetched verbatim from official docs this session; fallback is explicit `dark:` variants, which lose nothing but add repetition |
| A4 | `backdrop-filter` containing-block behavior for fixed descendants (Pitfall 8) follows standard CSS spec | Pitfall 8 | Low — spec-stable behavior; mitigation (keep overlays outside glass) costs nothing |
| A5 | No visual regression tooling exists (no Playwright/screenshots in repo) | Validation | Low — visual verification is manual; CSS-class assertions carry automated coverage |
| A6 | Alpine bundled with Livewire 4.4.3 is the only Alpine in the bundle and supports `x-transition`/`x-cloak` identically to standalone Alpine | Patterns / Pitfalls | Low — dist strings confirmed present [VERIFIED in-repo]; usage mirrors existing mailbox.blade.php pattern |
| A7 | 14px semibold body text is "normal size" per WCAG (large ≥18pt/24px, or ≥14pt/18.6px bold) | Contrast Table | Low — standard WCAG 2.x definition; using 600-level stops keeps AA regardless |

## Open Questions (RESOLVED)

1. **RESOLVED: D-12 "fade cross" interpretation**
   - What we know: Livewire 4's HTML swap is synchronous; `livewire:navigating` cannot delay it, so a two-sided DOM cross-fade is impossible without breaking morphing. The overlay technique (Pattern 3) delivers the locked visual intent (old fades out, new fades in) with deterministic timing.
   - What's unclear: Whether the user wants *literally* the old DOM fading to 0 before the new DOM appears, or accepts the overlay-equivalent. They are visually indistinguishable to a user.
   - Recommendation: Implement Pattern 3. Planner records the choice in PLAN.md as "D-12 implemented via route-overlay (synchronous swap constraint)" so verification has an explicit target. (This is the only locked decision the research modifies in implementation form.)

2. **RESOLVED: Gradient stop finalization (D-04 discretion exercised)**
   - What we know: 500-level stops fail AA (3.68/4.23); 600-level pass (5.17/5.38). Discretion explicitly includes "exact gradient color stops" and "accessibility contrast ratios".
   - What's unclear: None — the discretion resolves it. But since D-04 as written names #3b82f6/#8b5cf6, the plan should state the chosen implementation stops for traceability.
   - Recommendation: `from-blue-600 to-purple-600`; keep blue-500/violet-500 only for large decorative fills with no text (e.g., background blobs).

3. **RESOLVED: D-16 line-height 1.4 vs existing `.density-regular` 1.5**
   - What we know: `.density-regular` sets 1.5 (app.css:199) and `.density-compact` already sets 1.4 (app.css:193). D-16 requires 1.4 as the base feel.
   - What's unclear: Whether "1.4 default" means regular mode becomes 1.4 (compressing thread rows) or stays 1.5 with compact remaining the "dense" option (contradicting D-16).
   - Recommendation: Set `.density-regular` to 1.4 (matching D-16) and let `.density-comfortable` carry the 1.5-1.6 range; verify thread-list density in review. Flag in plan as an explicit change so the earlier 1.5 expectation is knowingly updated.

4. **RESOLVED: Variable vs static font package (family name nuance)**
   - What we know: `@fontsource-variable/plus-jakarta-sans` registers family "Plus Jakarta Sans Variable"; the static package registers exactly "Plus Jakarta Sans". Visually identical; DevTools will show the suffix in the family list.
   - What's unclear: Any consumer that matches the exact family string (none known in repo — no external CSS references fonts.googleapis.com today).
   - Recommendation: Use the variable package (one asset, all weights); if a stakeholder objects to the family-name suffix in DevTools, swap to the static package at build time — zero markup changes.

5. **RESOLVED: Initial full-page load fade (non-SPA)**
   - What we know: D-09 says "page load" — covers both the first load and SPA swaps. The CSS `animate-fade-in` on `#app-main` runs on full loads automatically.
   - What's unclear: Whether the first-paint fade should also run behind the route overlay on initial load (wrap first load in overlay fade too).
   - Recommendation: Yes — `livewire:navigated` fires on the initial page load as well [CITED: livewire.laravel.com/docs/4.x/navigate], so the same hook covers both; no extra code.

## Environment Availability

Audited 2026-09-09 (all probes run locally):

| Dependency | Required By | Available | Version | Fallback |
|------------|------------|-----------|---------|----------|
| Node.js | Vite build, npm install | ✓ | v26.8.1 | — |
| npm | install @fontsource-variable/plus-jakarta-sans | ✓ | 12.0.2 | — |
| PHP | `php artisan test`, asset-free runtime | ✓ | 8.5.10 | — |
| Composer | (unchanged this phase) | ✓ | — | — |
| Tailwind CSS | core styling | ✓ | 4.3.3 (installed) | — |
| @tailwindcss/vite | Vite plugin | ✓ | 4.3.3 (installed) | — |
| Livewire | loading/nav | ✓ | 4.4.3 (installed) | — |
| spatie/laravel-csp | CSP headers/nonces | ✓ | 3.28.3 (installed) | — |
| npm registry access | fontsource install | ✓ (`npm view` OK) | v5.3.0 | — |
| MySQL | app runtime (untouched this phase) | ✓ (installed) | — | tests use sqlite :memory: |
| Browser | visual verification | ✓ (assumed local) | — | — |

**Missing dependencies with no fallback:** none.
**Missing dependencies with fallback:** none — this phase needs zero external services (fonts self-hosted through the existing build pipeline).

## Validation Architecture

### Test Framework

| Property | Value |
|----------|-------|
| Framework | PHPUnit 11.5.56 |
| Config file | phpunit.xml (Unit + Feature suites; sqlite `:memory:`, `CACHE_STORE=array`, sync queue) |
| Quick run command | `php artisan test --filter=Mailbox` |
| Full suite command | `php artisan test` + `npm run build` (asset manifest check) |

### Phase Requirements → Test Map

The 16 decisions are mostly visual; automated coverage targets structure/behavior, visual checks stay manual (browser + optional axe). Existing anchor: `tests/Feature/ThreadUITest.php` [VERIFIED in-repo] — closest current UI test.

| Decision | Behavior | Test Type | Automated Command | File Exists? |
|----------|----------|-----------|-------------------|-------------|
| D-11 | message-list renders skeleton guards + no spinner overlay | unit (Livewire) | `php artisan test --filter=MessageList` | ❌ Wave 0 |
| D-01/D-04 | primary actions carry gradient classes (`from-blue-600 to-purple-600`) | unit (Livewire) | `php artisan test --filter=ComposeButton` | ❌ Wave 0 |
| D-06/D-07/D-08 | glass classes + badge tints present in mailbox sidebar/search markup | unit (Livewire) | `php artisan test --filter=Mailbox` (extend ThreadUI) | ⚠️ extend existing |
| D-13 | font css emitted by Vite | build-time | `node -e "JSON.parse(require('fs').readFileSync('public/build/manifest.json')).*fontsource*"` | ❌ Wave 0 (script) |
| D-14/D-15/D-16 | density tokens still applied (1.4 line-height on regular) | unit (DOM attr) | `php artisan test --filter=Appearance` (new) | ❌ Wave 0 |
| Theme/density persistence | appearance-tab interaction unchanged by restyle | unit (Livewire) | `php artisan test --filter=Appearance` | ❌ Wave 0 |
| CSP | layout still emits nonce meta tag (`@cspNonceMetaTag`) | unit | extend existing layout test | ⚠️ extend existing |
| D-09/D-10/D-12 | animations + overlay hook present in built assets | manual + grep | `rg -l "route-overlay\|animate-fade-in" public/build/assets/*.js` | manual |
| Contrast, blur feel, motion | WCAG AA, visual quality | manual (browser + axe/Lighthouse) | — | manual-only — justified: requires visual judgment |

### Sampling Rate
- **Per task commit:** `php artisan test --filter=Mailbox`
- **Per wave merge:** `php artisan test` + `npm run build` (no CSS build errors)
- **Phase gate:** full suite green + `npm run build` green + manual visual pass (contrast table re-check on shipped colors) before `/gsd-verify-work`

### Wave 0 Gaps
- [ ] `tests/Feature/Mailbox/MessageListLoadingTest.php` — asserts `wire:loading`/`wire:loading.remove` skeleton guards exist and old overlay spinner markup is gone
- [ ] `tests/Feature/Settings/AppearanceTest.php` — theme/density radio states still render and persist after restyle
- [ ] `tests/Feature/UiPolishTest.php` — gradient classes on primary actions; glass classes on inputs/cards; `x-cloak` CSS rule present in app.css via a source-level assertion
- [ ] Build gate script — verify `public/build/manifest.json` includes fontsource css after `npm run build`

## Security Domain

> `security_enforcement` enabled (absent from config = enabled).

### Applicable ASVS Categories

| ASVS Category | Applies | Standard Control |
|---------------|---------|-----------------|
| V2 Authentication | no | — (no auth changes this phase) |
| V3 Session Management | no | — |
| V4 Access Control | no | — |
| V5 Input Validation | partial | No new user inputs. Rule: gradient/glass color stops and class names must remain **static constants** in markup/CSS — never interpolate user input into class lists (a "theme color" field would need an allow-list) |
| V6 Cryptography | no | — |
| V14 Web/Frontend (CSP, XSS posture) | yes | Keep CSP policy unchanged: self-hosted fonts (no `style-src`/`font-src` relaxation); new route-overlay + hooks must live in Vite-bundled `app.js` (already nonce-integrated via spatie/laravel-csp) |

### Known Threat Patterns for {stack}

| Pattern | STRIDE | Standard Mitigation |
|---------|--------|---------------------|
| CSP relaxation for fonts (adding fonts.googleapis.com/.gstatic.com to style-src/font-src) | Tampering / Info disclosure | Self-host via @fontsource — policy file `config/csp.php` stays untouched; verify `CSP_ENABLED` report-only → enforce in review |
| CSS injection via user-controllable class/color values | Tampering | Static classes only; if any dynamic color input is ever added, route through an allow-list of token names, never raw hex from user input |
| Email HTML reusing app chrome classes | Tampering (XSS-adjacent) | Cross-phase invariant untouched: message-body iframe/render container must not carry app chrome classes; new .glass-* / gradient styles are chrome-only and excluded from the sanitized email render path |
| Route overlay rendering outside CSP nonce scope | Info disclosure | Overlay is a static div + `app.js` hook — both Vite-bundled, nonce-tagged by spatie/laravel-csp integration; no new inline `<script>` |
| Focus/visibility & reduced-motion a11y regressions | (not STRIDE) | WCAG 2.4.7 focus-visible rings (Pattern 5), contrast table enforcement, `prefers-reduced-motion`/`prefers-reduced-transparency` guards (Code Example 5) |

## Sources

### Primary (HIGH confidence)
- [Context7] `/websites/tailwindcss` — @theme tokens, @utility, keyframes-in-theme, blur scale, gradient utilities (fetched 2026-09-09)
- [Context7] `/websites/livewire_laravel_4_x` — navigation events (`livewire:navigating`/`navigated`, onSwap), wire-loading semantics (fetched 2026-09-09)
- [Context7] `/websites/alpinejs_dev` — x-transition full class syntax (fetched 2026-09-09)
- livewire.laravel.com/docs/4.x/loading-states — `data-loading` attribute system, `not-data-loading:`/`in-data-loading:` etc. variants (fetched 2026-09-09)
- livewire.laravel.com/docs/4.x/navigate — event lifecycle, `preventDefault`, onSwap, navigated-on-initial-load
- tailwindcss.com/docs/background-image and /docs/upgrade-guide — `bg-linear-to-*` naming, blur scale relocation, individual transform properties, Lightning CSS prefixing
- tailwindcss.com/docs/theme and /docs/animation — `@theme` semantics, keyframes inside @theme
- fontsource.org — Plus Jakarta Sans family pages, install docs
- In-repo verification: composer.lock (livewire 4.4.3, laravel 12.69.1, phpunit 11.5.56), node_modules (tailwindcss 4.3.3, vite 7.3.6), vendor/livewire/livewire/dist/livewire.js (cloak directive at byte 149406, data-loading present), resources/css/app.css, resources/views/** (quoted line ranges)

### Secondary (MEDIUM confidence)
- WebSearch synthesis on glassmorphism (multiple 2024-2026 design-engineering sources) — cross-checked for backdrop-filter behavior, performance caps, @supports fallback
- WebSearch on @fontsource + CSP interplay (fontsource docs page + content-security-policy.com reference)

### Tertiary (LOW confidence)
- None — no claims rest on unverified third-party content; every load-bearing behavior was either fetched from official docs or verified in-repo this session.

## Metadata

**Confidence breakdown:**
- Standard stack: **HIGH** — versions read from installed lockfiles/node_modules; the single new package (fontsource) verified on registry + legitimacy seam
- Architecture: **HIGH** — Livewire 4 event system and Tailwind v4 token mechanics fetched from official docs; overlay pattern is standard CSS/JS with no exotic behavior
- Pitfalls: **HIGH** — x-cloak/overlay/contrast claims verified in-repo or computed locally; the only MEDIUM item (A3 token dark-override) has a trivial fallback
- Assumptions: A1-A7 logged above; none are load-bearing plan decisions

**Research date:** 2026-09-09
**Valid until:** 2026-10-09 (30-day window: framework/library APIs verified are stable; re-check if Livewire or Tailwind minor versions change during implementation)