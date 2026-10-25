<?php

namespace App\Http\Requests\Subscription;

use App\Enums\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                Rule::exists('users', 'id')->where('type', UserType::CUSTOMER->value),
            ],
            'plan_id' => ['required', 'exists:plans,id'],
            'status' => ['nullable', 'in:active,pending'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
        ];
    }
}
