# Quick Task: 260912-ghm — Migrate from GitLab to GitHub

## Description
Remove all GitLab configurations, pipelines, and references, and replace them with GitHub equivalents: update git remote URL, add GitHub Actions CI/CD workflows, migrate issue & pull request templates, and update documentation links and instructions.

## Tasks

### Task 1: Update Git Remote Configuration
- **Action**: Change `origin` remote URL from GitLab to `https://github.com/eshanized/OpenMail.git`.
- **Verify**: `git remote -v`.

### Task 2: Replace GitLab CI/CD with GitHub Actions Workflows
- **Action**:
  - Remove `.gitlab-ci.yml`.
  - Create `.github/workflows/ci.yml` covering backend linting (Laravel Pint), security auditing (composer & npm audit), frontend building (npm ci & build), and testing with Pest / SQLite.
  - Create `.github/workflows/release.yml` covering release archive generation (`openmail-<tag>.zip`) and GitHub Release creation on tag push.
- **Verify**: Workflow syntax validation and file structure.

### Task 3: Migrate Issue & PR Templates from `.gitlab/` to `.github/`
- **Action**:
  - Remove `.gitlab/` directory.
  - Create `.github/ISSUE_TEMPLATE/bug_report.md`.
  - Create `.github/ISSUE_TEMPLATE/feature_request.md`.
  - Create `.github/pull_request_template.md`.
- **Verify**: Files exist in `.github/` with correct GitHub markdown frontmatter.

### Task 4: Update Documentation and Repository References
- **Files**: `CONTRIBUTING.md`, `SECURITY.md`, `CHANGELOG.md`
- **Action**:
  - Update issue tracker, PR / merge request terminology, fork & clone commands, compare/release links, and security reporting from GitLab to GitHub.
- **Verify**: `git grep -i "gitlab"` returns zero unexpected references.
