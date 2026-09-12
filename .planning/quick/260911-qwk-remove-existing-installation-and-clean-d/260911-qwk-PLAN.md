---
task_id: 260911-qwk
slug: remove-existing-installation-and-clean-d
description: Remove existing installation and clean deployment artifacts
date: 2026-09-11
files_modified:
  - .gitignore
  - storage/installed
  - scratch.php
  - test.out
  - .env
---

# Quick Task: Remove Existing Installation and Clean Deployment Artifacts

## Goal
Prepare OpenMail for upload to a production server by removing the existing installation lock (`storage/installed`), removing scratch/test dump files (`scratch.php`, `test.out`), cleaning stale caches and local database files, updating `.gitignore` to ignore `/storage/installed`, and cleaning local `.env` configuration so the fresh setup wizard can run smoothly on the server.

## Tasks

### Task 1: Untrack and remove `storage/installed` and add to `.gitignore`
- Remove `storage/installed` from git tracking and disk (`git rm storage/installed`).
- Add `/storage/installed` to `.gitignore` so future installs do not accidentally commit the lock file.

### Task 2: Untrack and remove temporary development files
- Remove `scratch.php` and `test.out` from git tracking and disk (`git rm scratch.php test.out`).

### Task 3: Clean runtime caches, sessions, logs, and test artifacts
- Remove `storage/app/test_attachment.txt`.
- Remove `database/database.sqlite` (local test SQLite database).
- Remove `.phpunit.result.cache`.
- Clear compiled Blade templates in `storage/framework/views/` (preserving directory and `.gitignore`).
- Clear sessions in `storage/framework/sessions/` (preserving `.gitignore`).
- Clear cache in `storage/framework/cache/data/` (preserving `.gitignore`).
- Truncate/clean `storage/logs/laravel.log` (preserving `.gitignore`).
- Clear bootstrap compiled cache files (`bootstrap/cache/packages.php`, `bootstrap/cache/services.php`).

### Task 4: Reset local `.env`
- Backup local `.env` to `.env.backup` (which is already in `.gitignore`) and remove `.env` so that when uploaded to the server, OpenMail's `InstallationBootstrap` will automatically initialize `.env` from `.env.example` with a fresh `APP_KEY` and allow the setup wizard to configure the production database and mail infrastructure.

### Task 5: Verification
- Verify `storage/installed`, `scratch.php`, `test.out` are not tracked by git and do not exist on disk.
- Verify `storage/` directory structure with its `.gitignore` files is intact.
- Verify that `php artisan` / web bootstrap treats OpenMail as uninstalled (`InstallationLock::isInstalled()` returns `false`).
