# OpenMail

OpenMail is a modern, self-hosted webmail application for organizations. It connects to existing company mail infrastructure (IMAP/SMTP) through a clean provider abstraction, delivering a Gmail-like experience without requiring a mail server.

## Installation

OpenMail comes with a built-in Setup Wizard.

1. Upload the files to your server (e.g., via cPanel/FTP).
2. Point your domain to the `public/` directory.
3. Open your browser and navigate to your domain. You will automatically be redirected to the Setup Wizard.
4. Follow the instructions to configure your database, mail server, and admin account.

Alternatively, you can install via CLI:
```bash
php artisan openmail:install
```

## Features

- Complete webmail experience
- Clean provider abstraction
- Multi-device responsive UI
- No command line required for end users

## Requirements

- PHP 8.2+
- MySQL / MariaDB
- Composer (for development)
