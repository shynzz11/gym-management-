<?php

namespace App\Http\Requests\Customer;

use App\Services\PaymentGatewayService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartCheckoutRequest extends FormRequest
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
            'plan_id' => [
                'required',
                Rule::exists('plans', 'id')->where('is_active', true),
            ],
            'action' => ['required', 'in:renew,switch,register'],
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
            'country_iso' => ['nullable', 'string', 'size:2'],
        ];
    }
}
