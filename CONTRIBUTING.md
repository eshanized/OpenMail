# Contributing to OpenMail

Thank you for your interest in contributing to **OpenMail**! OpenMail is built to provide organizations with a clean, modern, and privacy-respecting self-hosted webmail solution that runs easily on standard shared hosting or modern VPS infrastructure.

We welcome bug reports, feature suggestions, documentation enhancements, and code contributions.

---

## Table of Contents

1. [Code of Conduct](#code-of-conduct)
2. [How Can I Contribute?](#how-can-i-contribute)
   - [Reporting Bugs](#reporting-bugs)
   - [Suggesting Enhancements](#suggesting-enhancements)
   - [Submitting a Pull Request](#submitting-a-pull-request)
3. [Local Development Setup](#local-development-setup)
4. [Coding Standards & Tooling](#coding-standards--tooling)
   - [PHP Code Style (Pint)](#php-code-style-pint)
   - [Frontend Assets (Tailwind & Vite)](#frontend-assets-tailwind--vite)
   - [Testing (Pest)](#testing-pest)
5. [Git Conventions](#git-conventions)

---

## Code of Conduct

All contributors and maintainers are expected to adhere to our [Code of Conduct](CODE_OF_CONDUCT.md). Please treat everyone with respect, patience, and kindness.

---

## How Can I Contribute?

### Reporting Bugs

Before creating a bug report, check the [issue tracker](https://github.com/eshanized/OpenMail/issues) to make sure your issue has not already been reported.

When submitting a bug report:
- Use our [Bug Report Template](.github/ISSUE_TEMPLATE/bug_report.md).
- Provide a clear and descriptive title.
- Describe the exact steps to reproduce the issue.
- Include your environment details: PHP version, database engine (MySQL/MariaDB), browser, and mail server provider (Gmail, Outlook, Dovecot, cPanel, etc.).
- Attach relevant error logs from `storage/logs/laravel.log` (make sure to redact sensitive email addresses and passwords!).

### Suggesting Enhancements

We love good ideas! To suggest an enhancement:
- Use our [Feature Request Template](.github/ISSUE_TEMPLATE/feature_request.md).
- Clearly explain the problem the feature solves and who benefits from it.
- Remember our primary architectural constraint: **OpenMail must run on shared hosting without requiring Redis, Elasticsearch, or background daemons**.

### Submitting a Pull Request

1. **Fork** the repository on GitHub.
2. Clone your fork locally:
   ```bash
   git clone https://github.com/<your-username>/OpenMail.git
   cd openmail
   ```
3. Create a descriptive feature branch:
   ```bash
   git checkout -b feat/my-new-feature
   ```
4. Follow our coding and testing standards.
5. Commit your changes following [Conventional Commits](#git-conventions).
6. Push to your fork and open a **Pull Request (PR)** against the `master` branch of `eshanized/OpenMail`.
7. Fill out the [Pull Request Template](.github/pull_request_template.md).

---

## Local Development Setup

### Requirements
- **PHP**: 8.2 or 8.3 with extensions (`mbstring`, `xml`, `pdo_mysql`, `curl`, `json`)
- **Composer**: 2.x
- **Node.js**: 18+ & **npm**: 9+
- **MySQL** 8.0+ / **MariaDB** 10.6+ or **SQLite** (for quick local testing)

### Quick Setup

```bash
# 1. Clone your fork
git clone https://github.com/<your-username>/OpenMail.git
cd openmail

# 2. Install dependencies & initialize environment
composer setup

# 3. Start local development servers (Laravel + Vite HMR)
composer dev
```

The application will be accessible at `http://localhost:8000`.

---

## Coding Standards & Tooling

### PHP Code Style (Pint)

OpenMail adheres to PSR-12 coding standards with strict typing. We use **Laravel Pint** to format all PHP code automatically:

```bash
# Format code with Pint
./vendor/bin/pint

# Check for style violations without modifying files
./vendor/bin/pint --test
```

### Frontend Assets (Tailwind & Vite)

- Styles are built with **Tailwind CSS 4**. Avoid bloated custom CSS; use semantic utility classes where possible.
- JavaScript interactivity relies on **Alpine.js** and **Livewire 3**.
- Always verify your build succeeds before submitting code:
  ```bash
  npm run build
  ```

### Testing (Pest)

All new features and bug fixes must include unit or feature tests written with **Pest PHP**:

```bash
# Run all tests
composer test
# or directly
./vendor/bin/pest
```

---

## Git Conventions

We use **Conventional Commits** for clean git history and automated changelog generation:

- `feat:` A new user-facing feature
- `fix:` A bug fix
- `docs:` Documentation changes only
- `style:` Code style/formatting changes (no production logic change)
- `refactor:` Code changes that neither fix a bug nor add a feature
- `test:` Adding or updating tests
- `chore:` Maintenance tasks, build tool configuration, package updates

**Example:**
```bash
git commit -m "feat: add keyboard shortcut for marking email as read"
git commit -m "fix: resolve draft autosave synchronization conflict"
```

---

Thank you for helping make OpenMail better for everyone!
