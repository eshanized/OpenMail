## ISSUES FOUND

**Phase:** 03 - Compose & Send
**Status:** BLOCKED
**Blocking Issues:** 2

### Dimension Results
| Dimension | Verdict | Notes |
|-----------|---------|-------|
| 1 Copywriting | BLOCK | Generic "Cancel" CTA; "Send" and "Discard" are single-word verbs without nouns |
| 2 Visuals | FLAG | No focal point declared for composer modal |
| 3 Color | PASS | 60/30/10 declared; accent reserved for specific elements only |
| 4 Typography | BLOCK | 6 font sizes (max 4), 3 font weights (max 2) |
| 5 Spacing | PASS | All values multiples of 4, standard scale, exceptions justified |
| 6 Registry Safety | PASS | No third-party registries; Tiptap via npm |
| 7 Inventory Provenance | FLAG | Could not enumerate with real reason — inventory is non-exhaustive |

### Blocking Issues
- **Dimension 1 — Copywriting:** Secondary CTA uses generic label "Cancel" (explicitly prohibited). Primary CTA "Send" and destructive CTA "Discard" are single-word verbs without nouns.
  Fix: Replace "Cancel" → "Close Composer" or "Exit Compose"; "Send" → "Send Message"; "Discard" → "Discard Draft".

- **Dimension 4 — Typography:** 6 font sizes declared (Body 16px, Label 14px, Heading 20px, Display 24px, Small/Caption 12px, Mono/Code 13px) — max 4 allowed. 3 font weights declared (400, 500, 600) — max 2 allowed.
  Fix: Reduce to ≤4 sizes (e.g., merge Display into Heading, merge Small/Caption into Label, merge Mono/Code into Small). Reduce to ≤2 weights (e.g., keep 400 and 600, drop 500).

### Recommendations
- **Dimension 2 — Visuals:** Declare focal point for composer modal (e.g., "Tiptap editor is primary visual anchor — occupies majority of modal height, receives initial focus").
- **Dimension 7 — Inventory Provenance:** Inventory is correctly marked non-exhaustive. Ensure executor understands this is not a closed allowlist — additional Tailwind-composed components are permitted.

### Action Required
Fix blocking issues in UI-SPEC.md (Dimensions 1 and 4) and re-run `/gsd-ui-phase`.