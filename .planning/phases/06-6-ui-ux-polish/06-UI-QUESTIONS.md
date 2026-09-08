# Phase 6: UI/UX Polish — Design Contract Questions

The upstream CONTEXT.md already decided many design choices. I need to confirm a few specifics to complete the UI-SPEC. Please answer the following:

## 1. Spacing Scale
The existing density system uses 8px, 16px, 24px steps. Should we adopt a full 8-point scale (4, 8, 16, 24, 32, 48, 64) for consistency? Any exceptions (e.g., icon-only touch targets at 44px)?

## 2. Typography
We have base font size 14px (0.875rem). Need exactly 3-4 sizes. Could you confirm sizes for:
- Body (14px)
- Label (e.g., 12px?)
- Heading (e.g., 20px?)
- Display (e.g., 28px?)

Also exactly 2 font weights: regular (400) and semibold (600) or bold (700)?

## 3. Color
- **Dominant 60%:** Warm Cream (#fafaf9) for light mode, Soft Dark (#1a1a2e) for dark mode. Confirm?
- **Secondary 30%:** Cards, sidebar, nav — need specific colors. Provide secondary background color for light and dark mode (e.g., #f5f5f4 for light, #2a2a3e for dark).
- **Accent 10%:** Gradient Blue (#3b82f6) → Purple (#8b5cf6). Reserved for: primary actions (Send, Compose, Archive), links, active states. Confirm?
- **Destructive color:** Red (#ef4444) for delete/trash actions. Need?

## 4. Copywriting
- **Primary CTA label for this phase:** Since it's UI polish, maybe "Compose"? Or "Send"? Could you specify?
- **Empty state copy:** "No messages" or "Your inbox is empty"?
- **Error state copy:** "Something went wrong. Please try again."
- **Destructive actions:** "Delete" confirmation: "Are you sure you want to delete this message?"

## 5. Additional UI Considerations
The context mentions glass effects, animations, skeleton screens. Any specific values for:
- Glass effect backdrop-blur (e.g., 12px) and opacity (e.g., 0.7)?
- Animation timing functions (e.g., ease-out)?
- Skeleton screen shape patterns per component type (message list, folder sidebar, search results)?

Please answer each question concisely. If you want to keep existing defaults, just say "keep default".