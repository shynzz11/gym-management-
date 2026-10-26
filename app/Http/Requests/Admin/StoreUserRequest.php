<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isCustomerCreateRoute = $this->routeIs('admin.customers.store');

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'role_id' => $isCustomerCreateRoute ? 'nullable|exists:roles,id' : 'required|exists:roles,id',
            'phone' => $isCustomerCreateRoute
                ? ['required', 'string', 'max:30', Rule::unique('customer_profiles', 'phone')]
                : 'nullable|string|max:30',
            'address' => 'nullable|string|max:1000',
            'plan_id' => [
                'nullable',
                Rule::exists('plans', 'id')->where('is_active', true),
            ],
            'brand_id' => 'nullable|exists:brands,id',
        ];

        if (! $isCustomerCreateRoute) {
            $rules['password'] = 'required|string|min:8|confirmed';
        }

        return $rules;
    }
}
