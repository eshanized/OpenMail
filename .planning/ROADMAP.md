# Roadmap: OpenMail

## Overview

OpenMail delivers a self-hosted webmail application in 5 phases, progressing from a working setup wizard and authentication system through core mailbox reading, message composition, organizational features, and finally security hardening with UI polish. Each phase builds a complete technical layer that the next phase depends on.

## Phases
 
 - [x] **Phase 1: Foundation & Setup Wizard** - Project scaffold, MailProvider interface, database schema, authentication, 8-step setup wizard (completed 2026-09-05)
 - [x] **Phase 2: Mailbox Core** - Folder navigation, message list, message viewer with HTML sanitization, attachment handling (completed 2026-09-06)
 - [ ] **Phase 3: Compose & Send** - Full composer, Reply/Reply All/Forward, draft autosave, signatures, SMTP sending
 - [ ] **Phase 4: Organization & Intelligence** - Full-text search, contacts with autocomplete, message threading, labels
 - [ ] **Phase 5: Security & Polish** - CSP headers, rate limiting, audit logging, theme toggle, density settings, profile management

## Phase Details

### Phase 1: Foundation & Setup Wizard

**Goal**: Deploy and log in — the setup wizard guides administrators through installation, and users can authenticate via IMAP
**Depends on**: Nothing (first phase)
**Requirements**: SETUP-01 through SETUP-12, AUTH-01 through AUTH-08, DB-01, DB-02, DB-05
**Success Criteria** (what must be TRUE):

  1. Administrator completes the 8-step setup wizard and reaches a working login page
  2. User can log in with email/password and is redirected to a (mostly empty) mailbox view
  3. Re-running the setup wizard URL is blocked by the installation lock
  4. Sessions persist across browser tabs and expire after configured timeout
  5. IMAP/SMTP connection testing in the wizard reports success or specific failure

**Plans**: 3/3 plans executed

Plans:

- [x] 01-01-PLAN.md — Laravel scaffold, DB schema, install lock, services, login/wizard stubs (tracer)
- [x] 01-02-PLAN.md — Full 8-step setup wizard with mail config, auto-detection, verification suite
- [x] 01-03-PLAN.md — IMAP authentication guard, session management, login throttling, security

### Phase 2: Mailbox Core
 
 **Goal**: Users can browse folders and read messages — the core mailbox experience works end-to-end
 **Depends on**: Phase 1
 **Requirements**: MAIL-01 through MAIL-04, MSG-01 through MSG-07, VIEW-01 through VIEW-10, DB-03, DB-04
 **Success Criteria** (what must be TRUE):
 
   1. User can navigate between Inbox, Sent, Drafts, Trash, Spam, Archive, and custom folders
   2. Message list displays sender, subject, timestamp, read/unread state, star, and attachment indicators with pagination
   3. User can open a message and see rendered HTML (in sandboxed iframe) or plain text with headers
   4. User can download attachments from messages without path traversal or filename issues
   5. Bulk selection (checkbox, shift-click, select all) works on the message list
 
 **Plans**: 2/2 plans executed
 
 Plans:
 
 - [x] 02-01-PLAN.md — Core mailbox infrastructure: DB schema, IMAP service, folder sidebar, message list with pagination (tracer)
 - [x] 02-02-PLAN.md — Message viewer, dual HTML sanitization, attachments, actions, bulk selection toolbar

### Phase 3: Compose & Send

**Goal**: Users can compose, reply to, and send emails — the full send/receive loop is complete
**Depends on**: Phase 2
**Requirements**: COMP-01 through COMP-13
**Success Criteria** (what must be TRUE):

  1. User can compose a new email with To/CC/BCC, subject, plain text or HTML body, and attachments
  2. User can reply, reply all, and forward messages with inline quoted text
  3. Draft autosave preserves composition progress and drafts appear in the Drafts folder
  4. User can send an email via SMTP and it appears in the Sent folder
  5. Undo send delay (configurable 5-30s) prevents accidental immediate delivery

**Plans**: 2 plans

Plans:

- [ ] 03-01-PLAN.md — Core compose-send infrastructure: DB schema, ComposerService, basic composer modal UI (tracer)
- [ ] 03-02-PLAN.md — Tiptap rich text editor, draft autosave, undo send, recipient autocomplete

### Phase 4: Organization & Intelligence

**Goal**: Users can search, organize, and thread messages — the mailbox becomes smart and navigable
**Depends on**: Phase 3
**Requirements**: SRCH-01 through SRCH-07, CONT-01 through CONT-06, THR-01 through THR-05, LBL-01 through LBL-05
**Success Criteria** (what must be TRUE):

  1. User can search across subject, sender, recipients, and body with highlighted results
  2. Composer autocomplete suggests contacts from recent recipients and stored address book
  3. Related messages are grouped into conversation threads using Message-ID/In-Reply-To/References headers
  4. User can apply, remove, and filter by color-coded labels alongside folder navigation
  5. Search supports filtering by folder, date range, attachment presence, and read/unread status

**Plans**: TBD

### Phase 5: Security & Polish

**Goal**: The application is production-ready with security hardening and a polished user experience
**Depends on**: Phase 4
**Requirements**: SEC-01 through SEC-10, SET-01 through SET-06
**Success Criteria** (what must be TRUE):

  1. CSP headers block inline scripts and restrict resource loading to trusted origins
  2. Login and API endpoints enforce rate limiting with progressive delays on brute force attempts
  3. User can toggle between light and dark themes (respecting system preference) and adjust density (compact/regular/comfortable)
  4. Audit log records login events, failed attempts, and send actions
  5. User can manage signatures and view/edit profile information

**Plans**: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 → 2 → 3 → 4 → 5

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Foundation & Setup Wizard | 3/3 | Complete    | 2026-09-05 |
| 2. Mailbox Core | 2/2 | Complete    | 2026-09-06 |
| 3. Compose & Send | 0/2 | Not started | - |
| 4. Organization & Intelligence | 0/TBD | Not started | - |
| 5. Security & Polish | 0/TBD | Not started | - |
