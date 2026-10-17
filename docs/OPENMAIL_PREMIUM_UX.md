# OpenMail Premium UX

## Product intent

OpenMail should feel like a calm, polished, dependable email workspace: clear hierarchy, fast feedback, low visual noise, accessible controls, and careful treatment of private mail. The visual system should support real work rather than imitate another mail client's brand.

## Visual principles

- Keep Coral Red (#CF4234) as the signature accent, reserved for primary actions, selected navigation, unread cues, and focus.
- Use warm light surfaces and deep charcoal dark surfaces with the same semantic tokens in both themes.
- Prefer restrained borders, subtle elevation, 8–16px control radii, consistent spacing, and readable typography.
- Keep inbox rows information-dense but easy to scan; distinguish unread state through type weight and a restrained accent rail.
- Use motion only for feedback and context. Honor prefers-reduced-motion.
- Avoid decorative gradients, heavy glass effects, excessive cards, and large animations that compete with message content.

## UX workstreams

1. **Foundation** — shared shell, design tokens, typography, control sizing, semantic focus styles, consistent light/dark treatment.
2. **Inbox** — clearer folder hierarchy, scanning rhythm, selection and bulk-action states, skeletons, errors, and empty states.
3. **Reader** — comfortable reading width, clear sender/recipient/date hierarchy, predictable attachment treatment, safe remote-image controls, and thread navigation.
4. **Composer** — focused layout, clear recipient chips, attachment affordances, visible draft-save state, keyboard support, and send/undo feedback.
5. **Workspace** — three-pane desktop view, adaptive mobile navigation, command palette and shortcuts, accessible focus management.
6. **Perceived performance** — avoid layout shifts; show immediate optimistic feedback only when rollback is possible; instrument IMAP/SMTP and database waits; never hide real server failures.
7. **Verification** — browser checks at mobile/tablet/desktop widths, keyboard-only paths, screen-reader labels, no horizontal overflow, no console errors, and regression tests for mail operations.

## Implemented foundation slice

- Aligned the global application bar to a 56px workspace header and the Coral Red brand token.
- Refined mailbox content spacing and increased the desktop folder-sidebar width for easier scanning.
- Strengthened inbox heading and status hierarchy, primary compose actions, search-field sizing, unread row cues, and surface separation.
- Refined the message reader and composer surfaces while preserving existing Livewire actions and full-screen behavior on mobile.
- Added restrained hover/focus feedback and reduced-motion handling without adding a frontend dependency.

## Acceptance criteria for subsequent slices

- Every primary action provides a clear loading, success, or error state.
- The inbox and message reader remain usable at 360px wide without horizontal scrolling.
- Keyboard focus is visible and follows visual order; dialogs trap and restore focus correctly.
- Theme and density preferences remain consistent across routes and authenticated views.
- Visual changes do not change authorization, message sanitization, remote-image consent, attachment validation, Bcc privacy, or SMTP outcome semantics.
- Run npm run build, the PHP/Pest suite, Pint, and npm run test:browser where a configured browser environment is available.
- Measure mailbox render and IMAP/SMTP timings before claiming performance improvements.
