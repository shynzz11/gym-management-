<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'string', Rule::in(['approve', 'deny'])],
            'denial_reason' => ['nullable', 'string', 'max:500', 'required_if:decision,deny'],
        ];
    }

    public function messages(): array
    {
        return [
            'decision.required' => 'Please select a payment action.',
            'decision.in' => 'The selected payment action is invalid.',
            'denial_reason.required_if' => 'A denial reason is required when rejecting payment.',
            'denial_reason.max' => 'Denial reason may not be greater than 500 characters.',
        ];
    }
}
