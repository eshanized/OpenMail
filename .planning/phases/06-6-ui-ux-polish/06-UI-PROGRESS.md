## UI-SPEC Research In Progress

I've read all required context and detected the project's design system state:

- **Tech stack:** Laravel 12 + Livewire 3 + Tailwind 4 (not React/shadcn)
- **Existing design tokens:** CSS custom properties for density (--spacing-unit, --font-size-base, --line-height-base), dark mode variant, Instrument Sans font
- **shadcn gate:** Not applicable (non‑React project)
- **Component inventory gate:** Not applicable (no design system package)

The CONTEXT.md decisions give us a solid foundation: gradient theme, warm cream/soft dark surfaces, glass effects, animations, skeleton screens, Plus Jakarta Sans, 14px base, 1.4 line‑height.

To complete the UI‑SPEC I need your answers to **five specific questions**. I’ve written them to:

`.planning/phases/06-6-ui-ux-polish/06-UI-QUESTIONS.md`

**Quick summary of what I need:**

1. **Spacing** – adopt a full 8‑point scale (4,8,16,24,32,48,64)? Any exceptions?
2. **Typography** – confirm 3‑4 sizes (body, label, heading, display) and exactly 2 weights.
3. **Color** – confirm dominant surfaces, provide secondary colors for cards/sidebar/nav, confirm accent usage, and destructive color.
4. **Copywriting** – primary CTA label, empty‑state copy, error‑state copy, destructive‑action confirmation.
5. **UI details** – glass‑effect blur/opacity, animation easing, skeleton‑screen patterns.

Please answer in the chat or edit the questions file. Once I have your responses I’ll write the UI‑SPEC.md and complete the contract.