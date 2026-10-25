<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreSubscriptionRequest;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;

class SubscriptionController extends Controller
{
    public function __construct(protected SubscriptionService $subscriptionService) {}

    public function store(StoreSubscriptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $hasActiveSamePlan = $user->activeSubscriptions()
            ->where('plan_id', $data['plan_id'])
            ->exists();
        $status = $data['payment_method'] === 'cash' || $hasActiveSamePlan ? 'pending' : 'active';

        $subscription = $this->subscriptionService->create([
            'user_id' => $user->id,
            'plan_id' => $data['plan_id'],
            'status' => $status,
            'payment_method' => $data['payment_method'],
        ]);

        if ($subscription->status === 'pending') {
            $subscription->loadMissing('plan');
            $subscription->payments()->create([
                'amount' => $subscription->plan?->price ?? 0,
                'method' => $data['payment_method'],
                'status' => $data['payment_method'] === 'cash' ? 'pending' : 'completed',
                'transaction_type' => $data['action'] === 'renew' ? 'renew' : 'new',
            ]);
        }

        if ($data['payment_method'] === 'cash') {
            return back()->with('success', 'Plan request submitted. Cash payment is pending admin approval.');
        }

        if ($subscription->status === 'pending') {
            return back()->with('success', 'Plan renewal submitted. It will become active after your current cycle.');
        }

        return back()->with('success', 'Plan activated successfully.');
    }
}
