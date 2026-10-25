<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Plan;
use App\Services\PaymentGatewayService;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected PaymentGatewayService $paymentGatewayService) {}

    public function index(): Response
    {
        $user = auth()->user();

        $activeSubscriptions = $user->activeSubscriptions()
            ->with(['plan'])
            ->orderBy('end_date')
            ->get();
        $subscribedPlanIds = $user->subscriptions()
            ->whereIn('status', ['active', 'expired'])
            ->select('plan_id')
            ->distinct()
            ->pluck('plan_id')
            ->map(fn ($planId) => (int) $planId)
            ->values()
            ->all();
        $payments = Payment::query()
            ->whereHas('subscription', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('subscription.plan')
            ->latest()
            ->take(5)
            ->get();
        $pendingRenewPlanIds = Payment::query()
            ->where('status', 'pending')
            ->when(Schema::hasColumn('payments', 'transaction_type'), function ($query): void {
                $query->where('transaction_type', 'renew');
            })
            ->whereHas('subscription', function ($query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->whereNull('deleted_at');
            })
            ->with('subscription:id,plan_id')
            ->get()
            ->pluck('subscription.plan_id')
            ->map(fn ($planId) => (int) $planId)
            ->filter(fn (int $planId): bool => $planId > 0)
            ->unique()
            ->values()
            ->all();

        return Inertia::render('Customer/Pages/Dashboard', [
            'activeSubscriptions' => $activeSubscriptions,
            'subscribedPlanIds' => $subscribedPlanIds,
            'pendingRenewPlanIds' => $pendingRenewPlanIds,
            'availablePlans' => Plan::query()
                ->where('is_active', true)
                ->select(['id', 'name', 'price', 'duration_days', 'description', 'access_type', 'start_time', 'end_time', 'facilities', 'is_offer', 'offer_price', 'offer_ends_at'])
                ->orderBy('name')
                ->get(),
            'paymentOptions' => $this->paymentGatewayService->getCustomerPaymentOptions(),
            'payments' => $payments,
        ]);
    }
}
