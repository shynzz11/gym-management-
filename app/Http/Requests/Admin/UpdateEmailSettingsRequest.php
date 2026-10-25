<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEmailSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'MAIL_HOST' => 'required|string',
            'MAIL_PORT' => 'required|integer|min:1',
            'MAIL_USERNAME' => 'required|string',
            'MAIL_PASSWORD' => 'required|string',
            'MAIL_ENCRYPTION' => 'nullable|string|in:tls,ssl,none',
            'MAIL_FROM_ADDRESS' => 'required|email',
            'MAIL_FROM_NAME' => 'required|string',
        ];
    }

    public function messages(): array
    {
        return [
            'MAIL_HOST.required' => 'SMTP host is required.',
            'MAIL_PORT.required' => 'SMTP port is required.',
            'MAIL_PORT.integer' => 'SMTP port must be a valid number.',
            'MAIL_PORT.min' => 'SMTP port must be greater than zero.',
            'MAIL_USERNAME.required' => 'SMTP username is required.',
            'MAIL_PASSWORD.required' => 'SMTP password is required.',
            'MAIL_ENCRYPTION.in' => 'Encryption must be tls, ssl, or none.',
            'MAIL_FROM_ADDRESS.required' => 'From email address is required.',
            'MAIL_FROM_ADDRESS.email' => 'From email address must be a valid email.',
            'MAIL_FROM_NAME.required' => 'From name is required.',
        ];
    }
}
