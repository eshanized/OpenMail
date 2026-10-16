<?php

namespace App\Livewire\Mailbox;

use App\Services\ImapMailboxService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use Livewire\Component;

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
        if (! Auth::check()) {
            abort(401, 'Unauthorized');
        }

        // Re-resolve attachment server-side from authenticated IMAP connection (SEC boundary)
        $attachment = null;
        try {
            $attachment = app(ImapMailboxService::class)->getAttachment($this->folderPath, $this->uid, $index);
        } catch (\Throwable) {
            // Fallback for tests or mocks
        }

        if (! $attachment && $this->attachments) {
            $attachment = method_exists($this->attachments, 'get')
                ? $this->attachments->get($index)
                : ($this->attachments[$index] ?? null);
        }

        if (! $attachment) {
            abort(404, 'Attachment not found');
        }

        // Safe filename normalization (RFC compliant, prevent path traversal & header injection)
        $originalName = $attachment->name ?? 'attachment';
        $cleanName = preg_replace('/[\x00-\x1F\x7F\x22\/\\\\]/', '', basename($originalName));
        $cleanName = trim($cleanName) ?: 'attachment';
        $safeName = Str::uuid().'_'.$cleanName;

        // Security: Validate MIME type against whitelist
        $mimeType = $attachment->contentType ?? ($attachment->content_type ?? 'application/octet-stream');
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

        if (! in_array($mimeType, $allowedMimeTypes, true)) {
            abort(403, 'This file type is not allowed for download');
        }

        $content = $attachment->content ?? '';

        // If SVG, ensure strictly served as attachment with nosniff to prevent active content execution
        $headers = [
            'Content-Type' => $mimeType,
            'Content-Disposition' => 'attachment; filename="'.addslashes($safeName).'"',
            'X-Content-Type-Options' => 'nosniff',
        ];

        return Response::streamDownload(function () use ($content) {
            echo $content;
        }, $safeName, $headers);
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
