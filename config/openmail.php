<?php

return [
    /*
    |--------------------------------------------------------------------------
    | IMAP Configuration
    |--------------------------------------------------------------------------
    |
    | Default IMAP connection settings. These can be overridden by the
    | setup wizard configuration stored in the database.
    |
    */

    'imap' => [
        'host' => env('OPENMAIL_IMAP_HOST', '127.0.0.1'),
        'port' => env('OPENMAIL_IMAP_PORT', 993),
        'encryption' => env('OPENMAIL_IMAP_ENCRYPTION', 'ssl'),
        'username' => null,
        'password' => null,
        'protocol' => 'imap',
        'timeout' => 10,
        'validate_cert' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMTP Configuration
    |--------------------------------------------------------------------------
    |
    | Default SMTP connection settings. These can be overridden by the
    | setup wizard configuration stored in the database.
    |
    */

    'smtp' => [
        'host' => env('OPENMAIL_SMTP_HOST', '127.0.0.1'),
        'port' => env('OPENMAIL_SMTP_PORT', 465),
        'encryption' => env('OPENMAIL_SMTP_ENCRYPTION', 'ssl'),
        'username' => null,
        'password' => null,
        'timeout' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Encryption
    |--------------------------------------------------------------------------
    |
    | Default encryption method for IMAP/SMTP connections.
    | Per D-12: SSL/TLS is the default.
    |
    */

    'default_encryption' => 'ssl',
    'default_imap_port' => 993,
    'default_smtp_port' => 465,

    /*
    |--------------------------------------------------------------------------
    | Compose & Send Settings
    |--------------------------------------------------------------------------
    */

    'undo_send_delay' => env('OPENMAIL_UNDO_SEND_DELAY', 10), // seconds, 5-30 range
    'sent_folder' => env('OPENMAIL_SENT_FOLDER', 'Sent'),
    'drafts_folder' => env('OPENMAIL_DRAFTS_FOLDER', 'Drafts'),
    'max_attachment_size_mb' => 25,
    'max_total_attachment_size_mb' => 50,

    /*
    |--------------------------------------------------------------------------
    | Attachment Configuration (SEC-08, SEC-09)
    |--------------------------------------------------------------------------
    |
    | MIME type allowlist and size limits for file attachments.
    | File::types() inspects file contents, not just extension.
    |
    */

    'attachments' => [
        'allowed_mimes' => [
            // Images
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            // Documents
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
            // Archives
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
        ],
        'max_size' => 25 * 1024 * 1024, // 25MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | General security configuration for the application.
    |
    */

    'security' => [
        'csp_enabled' => env('CSP_ENABLED', false),
        'csp_report_only' => env('CSP_REPORT_ONLY', true),
        'session_timeout' => 120, // minutes
    ],
];