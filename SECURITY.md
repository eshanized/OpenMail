# Security Policy

The OpenMail team takes security and user privacy very seriously. Because OpenMail directly handles organizational communications, email credentials, and untrusted message payloads, we apply rigorous defense-in-depth principles across our architecture.

---

## Supported Versions

Only the latest release branch receives security updates and bug fixes:

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |
| < 1.0   | :x:                |

---

## Core Security Architecture

OpenMail implements multiple layers of protection:

1. **Credential Protection**:
   - Mailbox credentials (IMAP/SMTP passwords) are encrypted at rest using AES-256-CBC via Laravel's `Crypt` facade.
   - Credentials are never stored in plaintext and never logged.
2. **HTML Email Isolation**:
   - Incoming HTML emails are treated as untrusted user input.
   - Server-side sanitization via **HTMLPurifier** removes dangerous tags, scripts, and attributes.
   - Client-side sanitization via **DOMPurify** executes within a sandboxed `<iframe>` to prevent parser differential attacks.
3. **Remote Content Blocking**:
   - External images and tracking pixels are stripped by default and only fetched upon explicit user consent.
4. **SSRF Mitigation**:
   - Outbound HTTP requests (e.g. proxying avatars or webhooks) block private IPv4/IPv6 ranges (`127.0.0.0/8`, `10.0.0.0/8`, `192.168.0.0/16`, `172.16.0.0/12`, and link-local addresses).
5. **Content Security Policy (CSP)**:
   - Strict CSP headers are applied with dynamic cryptographic nonces to neutralize cross-site scripting (XSS).

---

## Reporting a Vulnerability

**Please do NOT report security vulnerabilities through public GitLab issues.**

If you discover a potential security vulnerability in OpenMail, please report it responsibly:

- **Email**: Send an encrypted or confidential email to **`m.eshanized@gmail.com`**.
- **GitLab**: Use GitLab's **Confidential Issue** feature on the project repository.

### What to Include in Your Report:
- A clear description of the vulnerability.
- The affected component, file, or endpoint.
- Step-by-step instructions or proof-of-concept (PoC) to reproduce the vulnerability.
- An assessment of the potential impact (e.g., information disclosure, remote code execution, XSS).

### Response Timeline:
- **Initial Acknowledgment**: Within 48 hours of receipt.
- **Assessment & Confirmation**: Within 5 business days.
- **Remediation & Patch Release**: Usually within 14 days, depending on severity and complexity.

We will coordinate public disclosure with you after a patch has been published to ensure our users have time to update safely.
