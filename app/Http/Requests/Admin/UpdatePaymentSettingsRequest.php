<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePaymentSettingsRequest extends FormRequest
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
            'SSLCOMMERZ_ENABLED' => ['sometimes', 'boolean'],
            'SSLCOMMERZ_STORE_ID' => ['required_if_accepted:SSLCOMMERZ_ENABLED', 'nullable', 'string', 'max:255'],
            'SSLCOMMERZ_STORE_PASSWORD' => ['required_if_accepted:SSLCOMMERZ_ENABLED', 'nullable', 'string', 'max:255'],
            'SSLCOMMERZ_SANDBOX' => ['sometimes', 'boolean'],
            'STRIPE_ENABLED' => ['sometimes', 'boolean'],
            'STRIPE_PUBLIC_KEY' => ['required_if_accepted:STRIPE_ENABLED', 'nullable', 'string', 'max:255'],
            'STRIPE_SECRET_KEY' => ['required_if_accepted:STRIPE_ENABLED', 'nullable', 'string', 'max:255'],
            'STRIPE_WEBHOOK_SECRET' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'SSLCOMMERZ_STORE_ID' => 'Store ID',
            'SSLCOMMERZ_STORE_PASSWORD' => 'Store Password',
            'SSLCOMMERZ_ENABLED' => 'SSLCommerz Status',
            'STRIPE_PUBLIC_KEY' => 'Public Key',
            'STRIPE_SECRET_KEY' => 'Secret Key',
            'STRIPE_ENABLED' => 'Stripe Status',
        ];
    }

    public function messages(): array
    {
        return [
            'required_if_accepted' => 'The :attribute is required to enable this payment gateway.',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $isEnablingAny = $this->input('SSLCOMMERZ_ENABLED') || $this->input('STRIPE_ENABLED');
            if ($isEnablingAny) {
                $currency = \App\Models\BusinessSetting::where('key', 'currency_symbol')->value('value');
                if (empty(trim((string) $currency))) {
                    if ($this->input('SSLCOMMERZ_ENABLED')) {
                        $validator->errors()->add('SSLCOMMERZ_ENABLED', 'A business currency must be configured in Business Settings before activating this payment gateway.');
                    }
                    if ($this->input('STRIPE_ENABLED')) {
                        $validator->errors()->add('STRIPE_ENABLED', 'A business currency must be configured in Business Settings before activating this payment gateway.');
                    }
                }
            }

            $paymentService = app(\App\Services\PaymentGatewayService::class);

            if ($this->input('SSLCOMMERZ_ENABLED') && ! $paymentService->isGatewayAvailableForSystemCurrency('sslcommerz')) {
                $validator->errors()->add('SSLCOMMERZ_ENABLED', 'SSLCommerz is only supported when the system currency is Bangladeshi Taka (BDT).');
            }

            if ($this->input('STRIPE_ENABLED') && ! $paymentService->isGatewayAvailableForSystemCurrency('stripe')) {
                $validator->errors()->add('STRIPE_ENABLED', 'Stripe is not supported when the system currency is Bangladeshi Taka (BDT).');
            }
        });
    }
}
