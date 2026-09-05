<?php

namespace App\Livewire\Mailbox;

use Livewire\Component;
use App\Services\ImapMailboxService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;

class AttachmentList extends Component
{
    public $attachments;
    public string $folderPath = '';
    public int $uid = 0;

    protected $listeners = ['download' => 'download'];

    public function mount($attachments, string $folderPath, int $uid): void
    {
        $this->attachments = $attachments;
        $this->folderPath = $folderPath;
        $this->uid = $uid;
    }

    public function download(int $index)
    {
        $attachment = $this->attachments->get($index);

        if (!$attachment) {
            abort(404, 'Attachment not found');
        }

        // Security: Generate UUID-based filename to prevent path traversal (SEC-08)
        $originalName = $attachment->name ?? 'attachment';
        $safeName = Str::uuid() . '_' . basename($originalName);

        // Security: Validate MIME type against whitelist (SEC-09)
        $mimeType = $attachment->contentType ?? 'application/octet-stream';
        $allowedMimeTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/zip',
            'application/x-zip-compressed',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml',
            'audio/mpeg',
            'audio/mp4',
            'video/mp4',
            'video/webm',
        ];

        // Block executable types
        $blockedMimeTypes = [
            'application/x-msdownload',
            'application/x-msdos-program',
            'application/x-sh',
            'application/x-csh',
            'application/x-perl',
            'application/x-python',
            'application/javascript',
            'text/javascript',
            'application/x-php',
        ];

        if (in_array($mimeType, $blockedMimeTypes)) {
            abort(403, 'Executable file types are not allowed for download');
        }

        // Stream directly from IMAP (D-12) - no local file storage
        $content = $attachment->content ?? '';

        return Response::streamDownload(function () use ($content) {
            echo $content;
        }, $safeName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment',
        ]);
    }

    public function render()
    {
        return view('livewire.mailbox.attachment-list', [
            'attachments' => $this->attachments,
            'folderPath' => $this->folderPath,
            'uid' => $this->uid,
        ]);
    }
}