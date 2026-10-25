<?php

namespace App\Http\Requests\Plan;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $plan = $this->route('plan');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('plans', 'name')->ignore($plan)],
            'price' => ['required', 'numeric', 'min:0'],
            'duration_days' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string'],
            'facilities' => ['nullable', 'array'],
            'facilities.*' => ['nullable', 'string', 'max:120'],
            'is_active' => ['boolean'],
            'is_default' => ['boolean'],
            'access_type' => ['required', 'string', 'exists:plan_access_types,slug'],
            'is_offer' => ['boolean'],
            'offer_price' => ['nullable', 'required_if:is_offer,true', 'numeric', 'min:0', 'lt:price'],
            'offer_ends_at' => ['nullable', 'required_if:is_offer,true', 'date', 'after:now'],
        ];
    }
}
