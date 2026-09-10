<?php

declare(strict_types=1);

namespace App\Install;

/**
 * InstallationBootstrap
 *
 * Called BEFORE Laravel fully boots (from public/index.php) when the
 * application has not yet been installed.
 *
 * Responsibilities:
 * 1. Create .env from .env.example if .env does not exist.
 * 2. Generate APP_KEY if .env has no key set.
 *
 * This ensures Laravel can boot into "installer mode" even on a completely
 * fresh deployment with no .env or APP_KEY.
 *
 * IMPORTANT: This file may NOT use any Laravel facades or the service
 * container — it runs before autoloading of app classes is complete.
 * Only PHP stdlib is allowed here.
 */
class InstallationBootstrap
{
    public static function ensureBootable(string $basePath): void
    {
        $envPath = $basePath.DIRECTORY_SEPARATOR.'.env';
        $examplePath = $basePath.DIRECTORY_SEPARATOR.'.env.example';

        // ── Step 1: Create .env if missing ───────────────────────────────────
        if (! file_exists($envPath)) {
            if (file_exists($examplePath)) {
                @copy($examplePath, $envPath);
            } else {
                @file_put_contents($envPath, self::minimalEnvContents($basePath));
            }
        }

        // ── Step 2: Generate APP_KEY if missing or blank ──────────────────────
        if (file_exists($envPath)) {
            $content = file_get_contents($envPath) ?: '';

            $hasKey = (bool) preg_match('/^APP_KEY=.{10,}$/m', $content);

            if (! $hasKey) {
                $key = 'base64:'.base64_encode(random_bytes(32));
                if (preg_match('/^APP_KEY=.*$/m', $content)) {
                    // Replace existing blank/short key line
                    $content = preg_replace('/^APP_KEY=.*$/m', 'APP_KEY='.$key, $content);
                } else {
                    // Append after APP_ENV line, or at end
                    $content .= "\nAPP_KEY=".$key."\n";
                }
                @file_put_contents($envPath, $content, LOCK_EX);
            }
        }
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────────────

    private static function minimalEnvContents(string $basePath): string
    {
        $key = 'base64:'.base64_encode(random_bytes(32));

        return <<<ENV
APP_NAME=OpenMail
APP_ENV=production
APP_KEY={$key}
APP_DEBUG=false
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_STACK=single
LOG_LEVEL=error

DB_CONNECTION=sqlite

SESSION_DRIVER=file
SESSION_LIFETIME=1440
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=file

MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=465
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="\${APP_NAME}"

VITE_APP_NAME="\${APP_NAME}"

OPENMAIL_IMAP_HOST=127.0.0.1
OPENMAIL_IMAP_PORT=993
OPENMAIL_IMAP_ENCRYPTION=ssl
OPENMAIL_SMTP_HOST=127.0.0.1
OPENMAIL_SMTP_PORT=465
OPENMAIL_SMTP_ENCRYPTION=ssl
ENV;
    }
}
