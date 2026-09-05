<?php

namespace App\Services;

class MailConfigDetector
{
    private array $providers = [
        'gmail.com' => [
            'name' => 'Gmail',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
        'googlemail.com' => [
            'name' => 'Gmail',
            'imap_host' => 'imap.gmail.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
        'outlook.com' => [
            'name' => 'Microsoft Outlook',
            'imap_host' => 'outlook.office365.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ],
        'hotmail.com' => [
            'name' => 'Microsoft Outlook',
            'imap_host' => 'outlook.office365.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ],
        'live.com' => [
            'name' => 'Microsoft Outlook',
            'imap_host' => 'outlook.office365.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.office365.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
        ],
        'yahoo.com' => [
            'name' => 'Yahoo Mail',
            'imap_host' => 'imap.mail.yahoo.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.mail.yahoo.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
        'ymail.com' => [
            'name' => 'Yahoo Mail',
            'imap_host' => 'imap.mail.yahoo.com',
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => 'smtp.mail.yahoo.com',
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ],
    ];

    public function detect(string $email): ?array
    {
        $domain = strtolower(substr(strrchr($email, '@'), 1));
        return $this->providers[$domain] ?? $this->genericFallback($domain);
    }

    private function genericFallback(string $domain): array
    {
        return [
            'name' => 'Custom',
            'imap_host' => "mail.{$domain}",
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'smtp_host' => "smtp.{$domain}",
            'smtp_port' => 465,
            'smtp_encryption' => 'ssl',
        ];
    }
}