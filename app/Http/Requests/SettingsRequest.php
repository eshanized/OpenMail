<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [];

        // Profile tab rules
        if ($this->routeIs('settings.profile.update') || $this->input('tab') === 'profile') {
            $rules['name'] = ['required', 'string', 'max:255'];
            $rules['email'] = ['required', 'email', 'max:255', 'unique:users,email,'.$this->user()->id];
        }

        // Mail tab rules
        if ($this->routeIs('settings.mail.update') || $this->input('tab') === 'mail') {
            $rules['page_size'] = ['required', 'integer', 'in:10,25,50,100'];
            $rules['default_folder'] = ['required', 'string', 'max:255'];
            $rules['reply_behavior'] = ['required', 'string', 'in:reply,reply_all'];
        }

        return $rules;
    }
}
