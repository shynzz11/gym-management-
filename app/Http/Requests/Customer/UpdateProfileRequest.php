<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->user()->id),
            ],
            'phone' => [
                'required',
                'string',
                'max:30',
                'regex:/^\+[0-9\s]{6,20}$/',
                Rule::unique('customer_profiles', 'phone')->ignore($this->user()->id, 'user_id'),
            ],
            'address' => 'required|string|max:1000',
            'password' => 'nullable|string|min:8|confirmed',
        ];
    }
}
