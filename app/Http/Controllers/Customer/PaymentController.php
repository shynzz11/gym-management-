<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StartCheckoutRequest;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\PaymentGatewayService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class PaymentController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected PaymentGatewayService $paymentGatewayService,
    ) {}

    public function checkout(StartCheckoutRequest $request): Response
    {
        $user = $request->user();
        $data = $request->validated();
        $planId = (int) $data['plan_id'];
        $selectedPlan = Plan::query()
            ->select(['id', 'price', 'is_offer', 'offer_price', 'offer_ends_at'])
            ->findOrFail($planId);
        $isFreePlan = (float) $selectedPlan->current_price <= 0;
        $requestedAction = (string) $data['action'];
        $hasClaimedFreePlan = $user->subscriptions()
            ->whereHas('plan', function ($query): void {
                $query->where('price', '<=', 0);
            })
            ->exists();
        $hasPlanHistory = $user->subscriptions()
            ->where('plan_id', $planId)
            ->whereIn('status', ['active', 'expired'])
            ->exists();

        if ($isFreePlan && $requestedAction === 'renew') {
            return back()->withErrors([
                'payment_gateway' => 'Free plans cannot be renewed.',
            ]);
        }

        if ($isFreePlan && $hasClaimedFreePlan) {
            return back()->withErrors([
                'payment_gateway' => 'You can only claim one free plan per account.',
            ]);
        }

        $effectiveAction = $hasPlanHistory ? 'renew' : $requestedAction;
        $renewalSubscription = null;

        if ($effectiveAction === 'renew') {
            if ($this->hasPendingRenewalPayment((int) $user->id, $planId)) {
                return back()->withErrors([
                    'payment_gateway' => 'A renewal payment is already pending for this plan. Please wait for approval or cancellation before renewing again.',
                ]);
            }

            $renewalSubscription = $this->resolveRenewalSubscription((int) $user->id, $planId);
            if (! $renewalSubscription) {
                return back()->withErrors([
                    'payment_gateway' => 'No previous subscription cycle was found for renewal.',
                ]);
            }
        }

        $gateway = $isFreePlan
            ? 'cash'
            : ($data['payment_gateway']
                ?? $this->paymentGatewayService->resolveGatewayBySystemCurrency());

        if ($isFreePlan) {
            $this->subscriptionService->create([
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => 'active',
                'payment_method' => 'cash',
            ]);

            return to_route('customer.dashboard')->with('success', 'Free plan activated successfully.');
        }

        $subscription = $renewalSubscription
            ? $renewalSubscription->loadMissing('plan')
            : $this->subscriptionService->create([
                'user_id' => $user->id,
                'plan_id' => $planId,
                'status' => 'pending',
                'payment_method' => $gateway,
            ]);

        if ($gateway === 'cash') {
            $subscription->loadMissing('plan');

            $paymentPayload = [
                'amount' => $subscription->plan?->current_price ?? 0,
                'method' => 'cash',
                'status' => 'pending',
            ];

            if (Schema::hasColumn('payments', 'transaction_type')) {
                $paymentPayload['transaction_type'] = $effectiveAction === 'renew' ? 'renew' : 'new';
            }

            $subscription->payments()->create($paymentPayload);

            return to_route('customer.dashboard')->with('success', 'Plan request submitted. Cash payment is pending admin approval.');
        }

        try {
            $checkoutUrl = $this->paymentGatewayService->createCheckoutUrl(
                $subscription->loadMissing('plan'),
                $user->loadMissing('customerProfile'),
                $gateway,
                $effectiveAction,
            );
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'payment' => $exception instanceof RuntimeException
                    ? $exception->getMessage()
                    : 'Unable to initialize payment checkout at this moment. Please try again.',
            ]);
        }

        return Inertia::location($checkoutUrl);
    }

    public function stripeSuccess(Request $request, Subscription $subscription): RedirectResponse
    {
        $sessionId = (string) $request->query('session_id', '');
        if ($sessionId === '') {
            return to_route('customer.dashboard')->with('error', 'Missing Stripe checkout session id.');
        }

        try {
            $session = $this->paymentGatewayService->verifyStripeCheckoutSession($sessionId);
            if (($session['payment_status'] ?? null) !== 'paid') {
                throw new RuntimeException('Stripe payment is not completed.');
            }

            if ((int) ($session['client_reference_id'] ?? 0) !== (int) $subscription->id) {
                throw new RuntimeException('Stripe session does not match the target subscription.');
            }

            $metadata = (array) ($session['metadata'] ?? []);
            $metadataUserId = (int) ($metadata['user_id'] ?? 0);
            if ($metadataUserId <= 0 || $metadataUserId !== (int) $subscription->user_id) {
                throw new RuntimeException('Stripe session does not belong to this user.');
            }

            $this->paymentGatewayService->markSubscriptionAsPaid(
                $subscription->loadMissing('plan'),
                'stripe',
                strtoupper((string) ($session['currency'] ?? 'USD')),
                (string) ($session['payment_intent'] ?? $session['id'] ?? ''),
                ((int) ($session['amount_total'] ?? 0)) / 100,
                $session,
            );

            $this->loginCustomerIfMissing($metadataUserId);
        } catch (\Throwable $exception) {
            report($exception);

            return to_route('customer.dashboard')->with('error', 'Stripe payment verification failed.');
        }

        return to_route('customer.dashboard')->with('success', 'Payment completed and membership activated.');
    }

    public function stripeCancel(Request $request, Subscription $subscription): RedirectResponse
    {
        $isAuthenticatedOwner = Auth::check() && (int) Auth::id() === (int) $subscription->user_id;
        $hasValidCancelToken = $this->hasValidStripeCancelToken($request, $subscription);

        if ($isAuthenticatedOwner || $hasValidCancelToken) {
            $this->cancelPendingCheckout(
                $subscription,
                'stripe',
                'Stripe checkout was cancelled by customer.',
            );

            $this->loginCustomerIfMissing((int) $subscription->user_id);
        }

        return to_route('customer.dashboard')->with('warning', 'Stripe checkout was cancelled.');
    }

    public function sslSuccess(Request $request): RedirectResponse
    {
        $subscription = $this->resolveSslCallbackSubscription($request);

        if (! $subscription) {
            return to_route('customer.dashboard')->with('error', 'Subscription not found for SSLCommerz callback.');
        }

        if (! $this->hasValidSslCallbackToken($request, $subscription)) {
            return to_route('customer.dashboard')->with('error', 'Invalid SSLCommerz callback token.');
        }

        $validationId = (string) $request->input('val_id', '');
        if ($validationId === '') {
            return to_route('customer.dashboard')->with('error', 'Missing SSLCommerz validation id.');
        }

        try {
            $validationData = $this->paymentGatewayService->verifySslTransaction($validationId);
            $validationStatus = strtoupper((string) ($validationData['status'] ?? ''));

            if (! in_array($validationStatus, ['VALID', 'VALIDATED'], true)) {
                throw new RuntimeException('SSLCommerz transaction validation failed.');
            }

            $this->paymentGatewayService->markSubscriptionAsPaid(
                $subscription,
                'sslcommerz',
                strtoupper((string) ($validationData['currency'] ?? 'INR')),
                (string) ($validationData['tran_id'] ?? $request->input('tran_id', '')),
                (float) ($validationData['amount'] ?? ($subscription->plan?->price ?? 0)),
                $validationData,
            );

            $this->loginCustomerIfMissing((int) $subscription->user_id);
        } catch (\Throwable $exception) {
            report($exception);

            return to_route('customer.dashboard')->with('error', 'SSLCommerz payment verification failed.');
        }

        return to_route('customer.dashboard')->with('success', 'Payment completed and membership activated.');
    }

    public function sslFail(Request $request): RedirectResponse
    {
        $subscription = $this->resolveSslCallbackSubscription($request);

        if ($subscription && $this->hasValidSslCallbackToken($request, $subscription)) {
            $this->cancelPendingCheckout(
                $subscription,
                'sslcommerz',
                'SSLCommerz payment failed.',
            );

            $this->loginCustomerIfMissing((int) $subscription->user_id);
        }

        return to_route('customer.dashboard')->with('error', 'SSLCommerz reported payment failure.');
    }

    public function sslCancel(Request $request): RedirectResponse
    {
        $subscription = $this->resolveSslCallbackSubscription($request);

        if ($subscription && $this->hasValidSslCallbackToken($request, $subscription)) {
            $this->cancelPendingCheckout(
                $subscription,
                'sslcommerz',
                'SSLCommerz checkout was cancelled by customer.',
            );

            $this->loginCustomerIfMissing((int) $subscription->user_id);
        }

        return to_route('customer.dashboard')->with('warning', 'SSLCommerz checkout was cancelled.');
    }

    public function stripeWebhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature', '');
        $secret = (string) config('services.stripe.webhook.secret', config('cashier.webhook.secret', ''));

        if ($secret === '') {
            return response()->json(['message' => 'Stripe webhook secret is not configured.'], 400);
        }

        if (! $this->hasValidStripeSignature($payload, $signature, $secret)) {
            return response()->json(['message' => 'Invalid webhook signature.'], 400);
        }

        $event = $request->json()->all();
        if (($event['type'] ?? null) !== 'checkout.session.completed') {
            return response()->json(['received' => true]);
        }

        $session = (array) ($event['data']['object'] ?? []);
        if (($session['payment_status'] ?? null) !== 'paid') {
            return response()->json(['received' => true]);
        }

        $subscriptionId = (int) ($session['metadata']['subscription_id'] ?? $session['client_reference_id'] ?? 0);
        $subscription = Subscription::query()->with('plan')->find($subscriptionId);

        if (! $subscription) {
            return response()->json(['received' => true]);
        }

        $this->paymentGatewayService->markSubscriptionAsPaid(
            $subscription,
            'stripe',
            strtoupper((string) ($session['currency'] ?? 'USD')),
            (string) ($session['payment_intent'] ?? $session['id'] ?? ''),
            ((int) ($session['amount_total'] ?? 0)) / 100,
            $session,
        );

        return response()->json(['received' => true]);
    }

    protected function hasPendingRenewalPayment(int $userId, int $planId): bool
    {
        $pendingRenewalQuery = Payment::query()
            ->where('status', 'pending')
            ->whereHas('subscription', function ($query) use ($userId, $planId): void {
                $query
                    ->where('user_id', $userId)
                    ->where('plan_id', $planId);
            });

        if (Schema::hasColumn('payments', 'transaction_type')) {
            $pendingRenewalQuery->where('transaction_type', 'renew');
        }

        return $pendingRenewalQuery->exists();
    }

    protected function resolveRenewalSubscription(int $userId, int $planId): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->whereIn('status', ['active', 'expired'])
            ->latest('end_date')
            ->first();
    }

    protected function hasValidStripeSignature(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '') {
            return false;
        }

        $segments = collect(explode(',', $signature));
        $timestamp = (string) $segments
            ->first(fn (string $segment) => str_starts_with($segment, 't='));
        $timestamp = str_replace('t=', '', $timestamp);

        if ($timestamp === '') {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $providedSignatures = $segments
            ->filter(fn (string $segment) => str_starts_with($segment, 'v1='))
            ->map(fn (string $segment) => str_replace('v1=', '', $segment))
            ->values();

        return $providedSignatures->contains(fn (string $provided) => hash_equals($expectedSignature, $provided));
    }

    private function resolveSslCallbackSubscription(Request $request): ?Subscription
    {
        $subscriptionId = (int) $request->input('value_a', $request->query('value_a', 0));

        if ($subscriptionId <= 0) {
            return null;
        }

        return Subscription::query()
            ->with('plan')
            ->find($subscriptionId);
    }

    private function hasValidSslCallbackToken(Request $request, Subscription $subscription): bool
    {
        $token = trim((string) $request->input('value_d', $request->query('value_d', '')));

        if ($token === '') {
            return false;
        }

        $expectedToken = $this->paymentGatewayService->buildCheckoutReturnToken(
            'ssl',
            (int) $subscription->id,
            (int) $subscription->user_id,
        );

        return hash_equals($expectedToken, $token);
    }

    private function hasValidStripeCancelToken(Request $request, Subscription $subscription): bool
    {
        $token = trim((string) $request->query('cancel_token', ''));

        if ($token === '') {
            return false;
        }

        $expectedToken = $this->paymentGatewayService->buildCheckoutReturnToken(
            'stripe-cancel',
            (int) $subscription->id,
            (int) $subscription->user_id,
        );

        return hash_equals($expectedToken, $token);
    }

    private function cancelPendingCheckout(Subscription $subscription, string $gateway, string $reason): void
    {
        $subscription->payments()
            ->where('status', 'pending')
            ->where(function ($query) use ($gateway): void {
                $query
                    ->where('method', $gateway)
                    ->orWhere('gateway', $gateway);
            })
            ->update(
                array_filter([
                    'status' => 'canceled',
                    'denial_reason' => Schema::hasColumn('payments', 'denial_reason') ? $reason : null,
                ], fn (mixed $value): bool => $value !== null),
            );

        if (strtolower((string) $subscription->status) === 'pending') {
            $subscription->delete();
        }
    }

    private function loginCustomerIfMissing(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        if (Auth::check()) {
            return;
        }

        Auth::guard('web')->loginUsingId($userId);
    }
}
