<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FilterPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('type', UserType::CUSTOMER->value),
            ],
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
            'type' => ['nullable', 'string', Rule::in(['new', 'renew'])],
            'method' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', Rule::in(['pending', 'completed', 'processed', 'failed', 'canceled', 'cancelled'])],
        ];
    }
}
