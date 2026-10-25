<?php

namespace App\Services;

use App\Models\BusinessSetting;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\MembershipRenewed;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PaymentGatewayService
{
    private ?string $systemCurrencyCode = null;

    private ?bool $supportsPaymentTransactionType = null;

    public function resolveGatewayByCountry(?string $countryIso): string
    {
        return $this->resolveGatewayBySystemCurrency();
    }

    public function resolveGatewayBySystemCurrency(): string
    {
        $systemCurrencyCode = $this->resolveSystemCurrencyCode();

        if ($systemCurrencyCode === 'BDT' && $this->isGatewayAvailableForSystemCurrency('sslcommerz')) {
            return 'sslcommerz';
        }

        if ($this->isGatewayAvailableForSystemCurrency('stripe')) {
            return 'stripe';
        }

        if ($this->isGatewayAvailableForSystemCurrency('sslcommerz')) {
            return 'sslcommerz';
        }

        return 'cash';
    }

    public function resolveSystemCurrencyCode(): string
    {
        if (is_string($this->systemCurrencyCode)) {
            return $this->systemCurrencyCode;
        }

        try {
            $rawCurrency = (string) BusinessSetting::query()
                ->where('key', 'currency_symbol')
                ->value('value');
        } catch (\Throwable) {
            $rawCurrency = '₹';
        }

        $normalizedCurrency = strtoupper(trim($rawCurrency));

        if ($normalizedCurrency === '') {
            $this->systemCurrencyCode = 'INR';

            return $this->systemCurrencyCode;
        }

        if (preg_match('/^[A-Z]{3}$/', $normalizedCurrency) === 1) {
            $this->systemCurrencyCode = $normalizedCurrency;

            return $this->systemCurrencyCode;
        }

        $this->systemCurrencyCode = match ($normalizedCurrency) {
            '৳' => 'BDT',
            '$' => 'USD',
            '€' => 'EUR',
            '£' => 'GBP',
            '¥', '￥' => 'JPY',
            '₹' => 'INR',
            default => 'INR',
        };

        return $this->systemCurrencyCode;
    }

    public function isGatewayAvailableForSystemCurrency(string $gateway): bool
    {
        $normalizedGateway = strtolower(trim($gateway));
        if ($normalizedGateway === 'cash') {
            return true;
        }

        if (! $this->isGatewayEnabled($normalizedGateway)) {
            return false;
        }

        $systemCurrencyCode = $this->resolveSystemCurrencyCode();
        if ($normalizedGateway === 'sslcommerz') {
            return $systemCurrencyCode === 'BDT';
        }

        if ($normalizedGateway === 'stripe') {
            return $systemCurrencyCode !== 'BDT';
        }

        return false;
    }

    /**
     * @return array{currency_code: string, sslcommerz: bool, stripe: bool, cash: bool, default_gateway: string}
     */
    public function getCustomerPaymentOptions(): array
    {
        return [
            'currency_code' => $this->resolveSystemCurrencyCode(),
            'sslcommerz' => $this->isGatewayAvailableForSystemCurrency('sslcommerz'),
            'stripe' => $this->isGatewayAvailableForSystemCurrency('stripe'),
            'cash' => true,
            'default_gateway' => $this->resolveGatewayBySystemCurrency(),
        ];
    }

    public function createCheckoutUrl(Subscription $subscription, User $user, string $gateway, string $action = 'renew'): string
    {
        if (! $this->isGatewayAvailableForSystemCurrency($gateway)) {
            throw new RuntimeException('Selected payment gateway is currently unavailable for current system currency.');
        }

        return match ($gateway) {
            'sslcommerz' => $this->createSslCommerzCheckoutUrl($subscription, $user, $action),
            'stripe' => $this->createStripeCheckoutUrl($subscription, $user, $action),
            default => throw new RuntimeException('Unsupported payment gateway.'),
        };
    }

    public function buildCheckoutReturnToken(string $context, int $subscriptionId, int $userId): string
    {
        return hash_hmac(
            'sha256',
            "{$context}|{$subscriptionId}|{$userId}",
            (string) config('app.key'),
        );
    }

    public function isGatewayEnabled(string $gateway): bool
    {
        return (bool) config("payment.gateways.{$gateway}.enabled", true);
    }

    public function createStripeCheckoutUrl(Subscription $subscription, User $user, string $action): string
    {
        $stripeSecretKey = (string) config('services.stripe.secret', config('cashier.secret', ''));

        if ($stripeSecretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $plan = $subscription->plan;
        if (! $plan) {
            throw new RuntimeException('Subscription plan not found.');
        }

        $currencyCode = strtolower($this->resolveSystemCurrencyCode());
        $amount = max((float) $plan->current_price, 0.5);
        $amountCents = (int) round($amount * 100);
        $cancelToken = $this->buildCheckoutReturnToken('stripe-cancel', (int) $subscription->id, (int) $user->id);
        $successUrl = route('customer.payments.stripe.success', ['subscription' => $subscription->id]).'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('customer.payments.stripe.cancel', [
            'subscription' => $subscription->id,
            'cancel_token' => $cancelToken,
        ]);

        $payload = [
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'client_reference_id' => (string) $subscription->id,
            'line_items[0][quantity]' => 1,
            'line_items[0][price_data][currency]' => $currencyCode,
            'line_items[0][price_data][unit_amount]' => $amountCents,
            'line_items[0][price_data][product_data][name]' => $plan->name,
            'metadata[user_id]' => (string) $user->id,
            'metadata[subscription_id]' => (string) $subscription->id,
            'metadata[action]' => $action,
        ];

        $response = Http::asForm()
            ->withToken($stripeSecretKey)
            ->post('https://api.stripe.com/v1/checkout/sessions', $payload);

        if (! $response->successful() || ! $response->json('url')) {
            throw new RuntimeException('Unable to create Stripe checkout session.');
        }

        return (string) $response->json('url');
    }

    public function createSslCommerzCheckoutUrl(Subscription $subscription, User $user, string $action): string
    {
        $storeId = (string) config('sslcommerz.store_id', '');
        $storePassword = (string) config('sslcommerz.store_password', '');

        if ($storeId === '' || $storePassword === '') {
            throw new RuntimeException('SSLCommerz credentials are not configured.');
        }

        $plan = $subscription->plan;
        if (! $plan) {
            throw new RuntimeException('Subscription plan not found.');
        }

        $transactionId = sprintf('GMS-%d-%d', $subscription->id, now()->getTimestamp());
        $baseUrl = $this->getSslCommerzBaseUrl();
        $customerProfile = $user->customerProfile;

        $response = Http::asForm()->post($baseUrl.'/gwprocess/v4/api.php', [
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'total_amount' => (float) $plan->current_price,
            'currency' => 'BDT',
            'tran_id' => $transactionId,
            'success_url' => route('customer.payments.ssl.success'),
            'fail_url' => route('customer.payments.ssl.fail'),
            'cancel_url' => route('customer.payments.ssl.cancel'),
            'ipn_url' => route('customer.payments.ssl.success'),
            'product_name' => $plan->name,
            'product_category' => 'Gym Membership',
            'product_profile' => 'general',
            'cus_name' => $user->name,
            'cus_email' => $user->email,
            'cus_add1' => $customerProfile?->address ?: 'N/A',
            'cus_city' => 'N/A',
            'cus_country' => 'Bangladesh',
            'cus_phone' => $customerProfile?->phone ?: 'N/A',
            'shipping_method' => 'NO',
            'num_of_item' => 1,
            'value_a' => (string) $subscription->id,
            'value_b' => (string) $user->id,
            'value_c' => $action,
            'value_d' => $this->buildCheckoutReturnToken('ssl', (int) $subscription->id, (int) $user->id),
        ]);

        if (! $response->successful() || ! $response->json('GatewayPageURL')) {
            throw new RuntimeException('Unable to initialize SSLCommerz checkout.');
        }

        return (string) $response->json('GatewayPageURL');
    }

    public function verifyStripeCheckoutSession(string $sessionId): array
    {
        $stripeSecretKey = (string) config('services.stripe.secret', config('cashier.secret', ''));

        if ($stripeSecretKey === '') {
            throw new RuntimeException('Stripe secret key is not configured.');
        }

        $response = Http::withToken($stripeSecretKey)
            ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        if (! $response->successful()) {
            throw new RuntimeException('Unable to verify Stripe checkout session.');
        }

        return (array) $response->json();
    }

    public function verifySslTransaction(string $validationId): array
    {
        $storeId = (string) config('sslcommerz.store_id', '');
        $storePassword = (string) config('sslcommerz.store_password', '');

        if ($storeId === '' || $storePassword === '') {
            throw new RuntimeException('SSLCommerz credentials are not configured.');
        }

        $response = Http::acceptJson()->get($this->getSslCommerzBaseUrl().'/validator/api/validationserverAPI.php', [
            'val_id' => $validationId,
            'store_id' => $storeId,
            'store_passwd' => $storePassword,
            'v' => 1,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to validate SSLCommerz transaction.');
        }

        return (array) $response->json();
    }

    public function markSubscriptionAsPaid(
        Subscription $subscription,
        string $gateway,
        string $currency,
        string $transactionId,
        float $amount,
        array $gatewayPayload = []
    ): Payment {
        $subscription->loadMissing('plan');

        $transactionType = $this->resolveTransactionTypeFromPayload($gatewayPayload);
        if (! in_array($transactionType, ['new', 'renew'], true)) {
            $transactionType = $subscription->payments()->exists() ? 'renew' : 'new';
        }

        $normalizedTransactionId = trim($transactionId) !== '' ? $transactionId : uniqid('gateway-', true);
        $existingPayment = Payment::query()->where('transaction_id', $normalizedTransactionId)->first();
        if ($existingPayment) {
            return $existingPayment;
        }

        $targetSubscription = $transactionType === 'renew'
            ? $this->resolveRenewalTargetSubscription($subscription)
            : $subscription;

        if ($transactionType === 'renew') {
            $this->applyRenewalCycle($targetSubscription);
        } else {
            $this->applyNewCycle($targetSubscription);
        }

        if (
            $targetSubscription->id !== $subscription->id
            && $subscription->status === 'pending'
            && ! $subscription->payments()->exists()
        ) {
            $subscription->delete();
        }

        $paymentPayload = [
            'amount' => $amount,
            'method' => $gateway,
            'gateway' => $gateway,
            'currency' => strtoupper($currency),
            'transaction_id' => $normalizedTransactionId,
            'gateway_payload' => $gatewayPayload,
            'status' => 'completed',
        ];

        if ($this->paymentSupportsTransactionType()) {
            $paymentPayload['transaction_type'] = $transactionType;
        }

        $payment = $targetSubscription->payments()->create($paymentPayload);

        if ($transactionType === 'renew') {
            $targetSubscription->loadMissing(['user', 'plan']);
            $targetSubscription->user?->notify(new MembershipRenewed($targetSubscription, $payment));
        }

        return $payment;
    }

    protected function getSslCommerzBaseUrl(): string
    {
        $sandbox = (bool) config('sslcommerz.sandbox', true);
        $baseUrl = $sandbox
            ? (string) config('sslcommerz.sandbox_base_url', 'https://sandbox.sslcommerz.com')
            : (string) config('sslcommerz.live_base_url', 'https://securepay.sslcommerz.com');

        return rtrim($baseUrl, '/');
    }

    protected function resolveTransactionTypeFromPayload(array $gatewayPayload): ?string
    {
        $action = strtolower((string) ($gatewayPayload['metadata']['action']
            ?? $gatewayPayload['value_c']
            ?? $gatewayPayload['action']
            ?? ''));

        return match ($action) {
            'renew' => 'renew',
            'switch', 'register' => 'new',
            default => null,
        };
    }

    protected function resolveRenewalTargetSubscription(Subscription $subscription): Subscription
    {
        $renewalTarget = Subscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('plan_id', $subscription->plan_id)
            ->where('id', '!=', $subscription->id)
            ->whereIn('status', ['active', 'expired'])
            ->latest('end_date')
            ->first();

        return $renewalTarget ?? $subscription;
    }

    protected function applyNewCycle(Subscription $subscription): void
    {
        $subscription->forceFill([
            'status' => 'active',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays((int) ($subscription->plan?->duration_days ?? 30))->toDateString(),
        ])->save();
    }

    protected function applyRenewalCycle(Subscription $subscription): void
    {
        $today = now()->startOfDay();
        $currentEndDate = $subscription->end_date;

        if (is_string($currentEndDate) && trim($currentEndDate) !== '') {
            $currentEndDate = Carbon::parse($currentEndDate)->startOfDay();
        } elseif ($currentEndDate instanceof \DateTimeInterface) {
            $currentEndDate = Carbon::instance($currentEndDate)->startOfDay();
        } else {
            $currentEndDate = null;
        }

        $renewalBaseDate = $currentEndDate && $currentEndDate->greaterThanOrEqualTo($today)
            ? $currentEndDate
            : Carbon::instance($today);
        $renewedEndDate = $renewalBaseDate
            ->copy()
            ->addDays((int) ($subscription->plan?->duration_days ?? 30))
            ->toDateString();

        $subscription->forceFill([
            'status' => 'active',
            'end_date' => $renewedEndDate,
        ])->save();
    }

    private function paymentSupportsTransactionType(): bool
    {
        if ($this->supportsPaymentTransactionType === null) {
            $this->supportsPaymentTransactionType = Schema::hasColumn('payments', 'transaction_type');
        }

        return $this->supportsPaymentTransactionType;
    }
}
