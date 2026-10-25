<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailRequest extends FormRequest
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
            'test_email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'test_email.required' => 'Test recipient email is required.',
            'test_email.email' => 'Test recipient email must be a valid email address.',
        ];
    }
}
