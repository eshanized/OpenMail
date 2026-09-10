<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class AttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $allowedMimes = config('openmail.attachments.allowed_mimes', []);
        $maxSizeBytes = config('openmail.attachments.max_size', 25 * 1024 * 1024);
        // File::types() expects max size in kilobytes
        $maxSizeKb = $maxSizeBytes / 1024;

        return [
            'attachment' => [
                'required',
                File::types($allowedMimes)
                    ->min(1)
                    ->max($maxSizeKb),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'attachment.mimes' => 'The attachment type is not allowed. Allowed types: '.
                implode(', ', config('openmail.attachments.allowed_mimes', [])),
            'attachment.max' => 'The attachment exceeds the maximum size of '.
                round(config('openmail.attachments.max_size', 25 * 1024 * 1024) / 1024 / 1024).'MB.',
        ];
    }
}
