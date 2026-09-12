---
task_id: 260912-8hk
status: complete
commit: 39351e4
date: 2026-09-12
---

# Quick Task Summary: Create Detailed Non-Technical Setup Guide (SETUP.md)

## Overview
Created a comprehensive, user-friendly setup and user guide in `SETUP.md` specifically designed for non-technical users and administrators deploying OpenMail on shared cPanel hosting or VPS.

## Key Sections Added in SETUP.md
1. **Prerequisites & Architecture Overview**: Clear explanations of hosting requirements and zero-CLI WordPress-style installation flow.
2. **Step-by-Step cPanel Deployment Guide**:
   - File upload and extraction via cPanel File Manager.
   - Configuring Document Root to `public/` (including an `.htaccess` proxy fallback for hosts locking primary domains to `public_html`).
   - MySQL database and user creation with proper privilege assignment.
   - Verifying folder permissions (`755` on `storage/` and `bootstrap/cache/`).
3. **Walkthrough of the 8-Step Web Setup Wizard**: Detailed guidance for each step of the web installer (`/install`).
4. **End-User Guide**: Guidance on first login, inbox navigation, reading threaded conversations, rich text composing, attachments, labels, color tagging, signatures, and theme selection.
5. **Troubleshooting & FAQ**: Fixes for common shared hosting issues (404 errors, directory indexing, PHP extensions in cPanel PHP Selector, database connection issues, firewall ports, and 500 permission errors).
