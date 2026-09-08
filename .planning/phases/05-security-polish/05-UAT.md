---
status: testing
phase: 05-security-polish
source: [05-VERIFICATION.md]
started: 2026-09-08T00:00:00Z
updated: 2026-09-08T00:00:00Z
---

## Current Test

number: 1
name: CSP headers delivery and Livewire/Alpine compatibility
expected: |
  Content-Security-Policy-Report-Only header present on mailbox page with nonce directives
  and unsafe-inline fallback for Livewire 3 and Alpine.js
awaiting: user response

## Tests

### 1. CSP Headers Delivery
expected: Content-Security-Policy-Report-Only header present on /mailbox with nonce-* for script-src and style-src
result: [pending]

### 2. Livewire 3 and Alpine.js under CSP
expected: No console CSP errors when interacting with mailbox components (composing, viewing messages, using sidebar)
result: [pending]

### 3. Theme/Density Flash Prevention
expected: No visible flash of unstyled content on page load — dark theme and density applied before body renders
result: [pending]

### 4. Density Live Preview
expected: Changing density in Settings → Appearance immediately updates spacing in the preview area
result: [pending]

### 5. Session Revocation
expected: Active sessions list shows current session; revoking other sessions logs them out
result: [pending]

### 6. Signature Composer Integration
expected: Default signature auto-inserted on new compose; dropdown allows swapping between signatures; signature HTML properly sanitized
result: [pending]

## Summary

total: 6
passed: 0
issues: 0
pending: 6
skipped: 0
blocked: 0

## Gaps
