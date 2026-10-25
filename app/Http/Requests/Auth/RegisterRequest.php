<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterRequest extends FormRequest
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
            'email' => 'required|string|email|max:255|unique:'.User::class,
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+[0-9\s]{6,20}$/'],
            'address' => 'required|string|max:1000',
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where('is_active', true),
            ],
            'country_iso' => ['nullable', 'string', 'size:2'],
            'payment_gateway' => [
                'nullable',
                'in:sslcommerz,stripe,cash',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value && $value !== 'cash' && ! config("payment.gateways.{$value}.enabled", true)) {
                        $fail('Selected payment gateway is currently disabled.');
                    }

                    if ($value && ! app(PaymentGatewayService::class)->isGatewayAvailableForSystemCurrency((string) $value)) {
                        $fail('Selected payment gateway is unavailable for current system currency.');
                    }
                },
            ],
            'terms' => 'accepted',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }
}
