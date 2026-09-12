# Quick Task Summary: 260912-ghm — Migrate from GitLab to GitHub

## Overview
Successfully migrated repository origin remote, CI/CD pipelines, issue & PR templates, and documentation references from GitLab to GitHub (`https://github.com/eshanized/OpenMail.git`).

## Key Changes
1. **Git Remote**:
   - Updated `origin` remote URL from `https://gitlab.com/eshanized/openmail.git` to `https://github.com/eshanized/OpenMail.git`.
2. **CI/CD Pipelines**:
   - Removed `.gitlab-ci.yml`.
   - Created `.github/workflows/ci.yml` for automated linting (Pint), security auditing (composer & npm audit), frontend compilation, and Pest testing with SQLite.
   - Created `.github/workflows/release.yml` for production packaging (`openmail-<tag>.zip`) and automated GitHub Release publishing on `v*` tag push.
3. **Issue & PR Templates**:
   - Removed `.gitlab/` issue and merge request templates.
   - Created `.github/ISSUE_TEMPLATE/bug_report.md`.
   - Created `.github/ISSUE_TEMPLATE/feature_request.md`.
   - Created `.github/pull_request_template.md`.
4. **Documentation**:
   - Updated `CHANGELOG.md` compare and release links to GitHub.
   - Updated `CONTRIBUTING.md` issue tracker link, fork instructions, clone URLs, Pull Request terminology, and template paths to GitHub.
   - Updated `SECURITY.md` reporting instructions from GitLab Confidential Issues to GitHub Private Vulnerability Reporting.

## Verification
- `git remote -v` outputs `https://github.com/eshanized/OpenMail.git`.
- `git grep -i "gitlab"` returns 0 occurrences across the entire codebase.
