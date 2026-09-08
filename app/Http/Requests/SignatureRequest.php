<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SignatureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:100',
            'content_json' => 'required|array',
            'is_default' => 'boolean',
        ];
    }
}
