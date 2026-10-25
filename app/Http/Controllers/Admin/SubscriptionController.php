<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserType;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(protected \App\Services\SubscriptionService $subscriptionService) {}

    public function index(Request $request): Response
    {
        \Illuminate\Support\Facades\Gate::authorize('manage_subscriptions');
        $search = trim((string) $request->string('search'));

        $customers = User::query()
            ->select('id', 'name', 'email')
            ->where('type', UserType::CUSTOMER)
            ->orderBy('name')
            ->get();

        $preselectedCustomerId = $request->integer('assign_customer_id');
        $shouldOpenAssignModal = $request->boolean('open_assign_modal');
        $isValidPreselectedCustomer = $customers->contains(function (User $customer) use ($preselectedCustomerId): bool {
            return $customer->id === $preselectedCustomerId;
        });

        return inertia('Admin/Subscriptions/Index', [
            'subscriptions' => $this->subscriptionService->getAll($search),
            'activeSubscriptionAssignments' => $this->subscriptionService->getActiveSubscriptionAssignments(),
            'users' => $customers,
            'plans' => Plan::query()
                ->select('id', 'name', 'price', 'duration_days', 'is_offer', 'offer_price', 'offer_ends_at')
                ->orderBy('name')
                ->get(),
            'filters' => [
                'search' => $search,
            ],
            'preselectedCustomerId' => $isValidPreselectedCustomer ? $preselectedCustomerId : null,
            'openAssignModal' => $shouldOpenAssignModal && $isValidPreselectedCustomer,
        ]);
    }

    public function store(\App\Http\Requests\Subscription\StoreSubscriptionRequest $request): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('manage_subscriptions');

        $validated = $request->validated();
        $status = $validated['status'] ?? 'active';
        $shouldRenewExistingPlan = $status === 'active'
            && Subscription::query()
                ->where('user_id', $validated['user_id'])
                ->where('plan_id', $validated['plan_id'])
                ->where('status', 'active')
                ->whereDate('end_date', '>=', now()->toDateString())
                ->exists();

        $subscription = $this->subscriptionService->create($validated);

        if ($subscription->scheduled_activation) {
            return back()->with('success', 'Subscription scheduled to activate on its start date.');
        }

        return back()->with(
            'success',
            $shouldRenewExistingPlan ? 'Subscription renewed successfully.' : 'Subscription created successfully.',
        );
    }

    public function updateStatus(
        \App\Http\Requests\Admin\UpdateSubscriptionStatusRequest $request,
        \App\Models\Subscription $subscription
    ): RedirectResponse {
        \Illuminate\Support\Facades\Gate::authorize('manage_subscriptions');

        $subscription = $this->subscriptionService->updateStatus($subscription, $request->validated()['status']);

        if ($subscription->scheduled_activation) {
            return back()->with('success', 'Subscription approved and scheduled to activate on its start date.');
        }

        return back()->with('success', 'Subscription status updated successfully.');
    }

    public function destroy(Subscription $subscription): RedirectResponse
    {
        \Illuminate\Support\Facades\Gate::authorize('manage_subscriptions');

        if ($subscription->status === 'pending') {
            $subscription->delete();

            return back()->with('success', 'Subscription denied and removed.');
        }

        return back()->with('error', 'Only pending subscriptions can be denied.');
    }
}
