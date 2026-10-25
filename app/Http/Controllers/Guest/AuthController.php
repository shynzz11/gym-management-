<?php

namespace App\Http\Controllers\Guest;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        protected \App\Services\UserService $userService,
        protected \App\Services\SubscriptionService $subscriptionService,
        protected \App\Services\PlanService $planService,
        protected \App\Services\PaymentGatewayService $paymentGatewayService,
    ) {}

    public function showLogin(): Response
    {
        return Inertia::render('Guest/Pages/Login');
    }

    public function storeLogin(\App\Http\Requests\Auth\LoginRequest $request): \Illuminate\Http\RedirectResponse
    {
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $request->session()->regenerate();

            /** @var \App\Models\User $user */
            $user = Auth::user();
            if ($user->isAdmin() || $user->isSuperAdmin()) {
                Auth::logout();

                return redirect()->back()->with('error', __('Admins must login via the Admin Portal.'));
            }

            if ($intended = redirect()->getIntendedUrl()) {
                if (! str_starts_with($intended, url('/customer'))) {
                    $request->session()->forget('url.intended');

                    return redirect()->route('customer.dashboard');
                }
            }

            return redirect()->intended(route('customer.dashboard'));
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function showRegister(): Response
    {
        return Inertia::render('Guest/Pages/Register', [
            'plans' => $this->planService->getActivePlansForRegistration(),
            'paymentOptions' => $this->paymentGatewayService->getCustomerPaymentOptions(),
        ]);
    }

    public function storeRegister(\App\Http\Requests\Auth\RegisterRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validated();
        $selectedPlan = Plan::query()
            ->select(['id', 'price', 'is_offer', 'offer_price', 'offer_ends_at'])
            ->findOrFail((int) $validated['plan_id']);
        $isFreePlan = (float) $selectedPlan->current_price <= 0;

        $validated['type'] = UserType::CUSTOMER;

        $user = $this->userService->create($validated);

        $selectedGateway = $isFreePlan
            ? 'cash'
            : ($validated['payment_gateway']
                ?? $this->paymentGatewayService->resolveGatewayBySystemCurrency());

        $subscription = $this->subscriptionService->create([
            'user_id' => $user->id,
            'plan_id' => $validated['plan_id'],
            'status' => $isFreePlan ? 'active' : 'pending',
            'payment_method' => $selectedGateway,
        ]);

        Auth::login($user);

        if ($isFreePlan) {
            return redirect()->route('customer.registration.success')
                ->with('success', 'Registration completed. Your free plan is active now.');
        }

        if ($selectedGateway === 'cash') {
            $subscription->loadMissing('plan');

            $paymentPayload = [
                'amount' => $subscription->plan?->current_price ?? 0,
                'method' => 'cash',
                'status' => 'pending',
            ];

            if (Schema::hasColumn('payments', 'transaction_type')) {
                $paymentPayload['transaction_type'] = 'new';
            }

            $subscription->payments()->create($paymentPayload);

            return redirect()->route('customer.registration.success')
                ->with('warning', 'Registration completed. Cash payment is pending admin approval.');
        }

        try {
            $checkoutUrl = $this->paymentGatewayService->createCheckoutUrl(
                $subscription->loadMissing('plan'),
                $user->loadMissing('customerProfile'),
                $selectedGateway,
                'register',
            );

            return Inertia::location($checkoutUrl);
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->route('customer.registration.success')
                ->with('warning', 'Registration completed. Please complete payment from your dashboard.');
        }
    }

    public function destroy(Request $request): \Illuminate\Http\RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
