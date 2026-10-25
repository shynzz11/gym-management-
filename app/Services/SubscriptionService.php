<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\MembershipRenewed;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    private ?bool $supportsPaymentTransactionType = null;

    public function __construct(protected \App\Services\NotificationService $notificationService) {}

    public function getAll(string $search = '', int $perPage = 10): LengthAwarePaginator
    {
        $normalizedSearch = trim($search);

        $subscriptions = Subscription::query()
            ->with(['user:id,name,email', 'plan:id,name'])
            ->when($normalizedSearch !== '', function (Builder $query) use ($normalizedSearch): void {
                $query->where(function (Builder $subscriptionQuery) use ($normalizedSearch): void {
                    $subscriptionQuery->where('status', 'like', "%{$normalizedSearch}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($normalizedSearch): void {
                            $userQuery->where('name', 'like', "%{$normalizedSearch}%")
                                ->orWhere('email', 'like', "%{$normalizedSearch}%");
                        })
                        ->orWhereHas('plan', function (Builder $planQuery) use ($normalizedSearch): void {
                            $planQuery->where('name', 'like', "%{$normalizedSearch}%");
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $subscriptionCollection = $subscriptions->getCollection();

        if ($subscriptionCollection->isEmpty()) {
            return $subscriptions;
        }

        $userIds = $subscriptionCollection
            ->pluck('user_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        $planIds = $subscriptionCollection
            ->pluck('plan_id')
            ->map(fn (mixed $value): int => (int) $value)
            ->unique()
            ->values();

        $pendingPaymentPairKeys = Payment::query()
            ->join('subscriptions as payment_subscriptions', 'payment_subscriptions.id', '=', 'payments.subscription_id')
            ->where('payments.status', 'pending')
            ->whereIn('payment_subscriptions.user_id', $userIds)
            ->whereIn('payment_subscriptions.plan_id', $planIds)
            ->select([
                'payment_subscriptions.user_id',
                'payment_subscriptions.plan_id',
            ])
            ->distinct()
            ->get()
            ->mapWithKeys(function (Payment $payment): array {
                return [
                    "{$payment->user_id}-{$payment->plan_id}" => true,
                ];
            });

        $subscriptionCollection->each(function (Subscription $subscription) use ($pendingPaymentPairKeys): void {
            $subscription->setAttribute(
                'has_pending_payment_request',
                (bool) ($pendingPaymentPairKeys["{$subscription->user_id}-{$subscription->plan_id}"] ?? false),
            );
        });

        return $subscriptions;
    }

    /**
     * @return array<int, array{user_id: int, plan_id: int}>
     */
    public function getActiveSubscriptionAssignments(): array
    {
        return Subscription::query()
            ->select(['user_id', 'plan_id'])
            ->active()
            ->distinct()
            ->get()
            ->map(function (Subscription $subscription): array {
                return [
                    'user_id' => (int) $subscription->user_id,
                    'plan_id' => (int) $subscription->plan_id,
                ];
            })
            ->values()
            ->all();
    }

    public function create(array $data)
    {
        $plan = Plan::findOrFail($data['plan_id']);
        $paymentMethod = $data['payment_method'] ?? 'manual';
        $status = $data['status'] ?? 'active';
        $hasSelectedStartDate = isset($data['start_date']);
        $startDate = isset($data['start_date'])
            ? Carbon::parse($data['start_date'])->startOfDay()
            : now()->startOfDay();
        $isFutureStart = $startDate->greaterThan(now()->startOfDay());

        if ($status === 'active' && $hasSelectedStartDate) {
            $existingSubscription = Subscription::query()
                ->where('user_id', $data['user_id'])
                ->where('plan_id', $data['plan_id'])
                ->whereIn('status', ['active', 'expired'])
                ->latest('end_date')
                ->first();

            if ($existingSubscription) {
                return $this->renewExistingSubscriptionFromDate(
                    $existingSubscription,
                    $plan,
                    $paymentMethod,
                    $startDate,
                    $isFutureStart,
                );
            }
        } elseif ($status === 'active') {
            $renewableSubscription = $this->findRenewableActiveSubscription(
                (int) $data['user_id'],
                (int) $data['plan_id'],
            );

            if ($renewableSubscription) {
                return $this->renewExistingSubscription($renewableSubscription, $plan, $paymentMethod);
            }
        }

        $data['start_date'] = $startDate->toDateString();
        $data['end_date'] = $startDate->copy()->addDays($plan->duration_days)->toDateString();
        $data['scheduled_activation'] = $isFutureStart && $status === 'active';
        $data['status'] = $data['scheduled_activation'] ? 'pending' : $status;

        $subscription = Subscription::create($data);

        if ($subscription->scheduled_activation) {
            $subscription->payments()->create(
                $this->buildPaymentPayload(
                    amount: $plan->price,
                    method: $paymentMethod,
                    transactionType: 'new',
                ),
            );

            $this->notificationService->info(
                $subscription->user_id,
                'Subscription Scheduled',
                "Your subscription to the {$plan->name} plan is scheduled to start on {$startDate->format('M d, Y')}.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );
        } elseif ($subscription->status === 'active') {
            $subscription->payments()->create(
                $this->buildPaymentPayload(
                    amount: $plan->price,
                    method: $paymentMethod,
                    transactionType: 'new',
                ),
            );

            $this->notificationService->markExpiryNotificationsAsRead($subscription->user_id);
            $this->notificationService->success(
                $subscription->user_id,
                'Subscription Activated',
                "Your subscription to the {$plan->name} plan is now active.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );
        } elseif ($subscription->status === 'pending') {
            $this->notificationService->info(
                $subscription->user_id,
                'Subscription Pending',
                "Your subscription to the {$plan->name} plan is pending approval.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );
        }

        return $subscription;
    }

    private function renewExistingSubscriptionFromDate(
        Subscription $subscription,
        Plan $plan,
        string $paymentMethod,
        Carbon $startDate,
        bool $isFutureStart,
    ): Subscription {
        $subscription->update([
            'start_date' => $startDate->toDateString(),
            'end_date' => $startDate->copy()->addDays($plan->duration_days)->toDateString(),
            'status' => $isFutureStart ? 'pending' : 'active',
            'scheduled_activation' => $isFutureStart,
        ]);

        $payment = $subscription->payments()->create(
            $this->buildPaymentPayload(
                amount: $plan->price,
                method: $paymentMethod,
                transactionType: 'renew',
            ),
        );

        if ($isFutureStart) {
            $this->notificationService->info(
                $subscription->user_id,
                'Membership Renewal Scheduled',
                "Your {$plan->name} membership renewal is scheduled to start on {$startDate->format('M d, Y')}.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );

            return $subscription->refresh();
        }

        $subscription->loadMissing('user', 'plan');
        $subscription->user?->notify(new MembershipRenewed($subscription, $payment));

        $this->notificationService->markExpiryNotificationsAsRead($subscription->user_id);
        $this->notificationService->success(
            $subscription->user_id,
            'Subscription Renewed',
            "Your subscription to the {$plan->name} plan has been successfully renewed.",
            ['type' => 'subscription', 'id' => $subscription->id],
            '/customer/dashboard'
        );

        return $subscription->refresh();
    }

    private function findRenewableActiveSubscription(int $userId, int $planId): ?Subscription
    {
        return Subscription::query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString())
            ->latest('end_date')
            ->first();
    }

    private function renewExistingSubscription(Subscription $subscription, Plan $plan, string $paymentMethod): Subscription
    {
        $today = now()->startOfDay();
        $currentEndDate = $subscription->end_date;
        $renewalBaseDate = $currentEndDate && $currentEndDate->greaterThanOrEqualTo($today)
            ? $currentEndDate->copy()
            : Carbon::instance($today);
        $renewedEndDate = $renewalBaseDate->addDays($plan->duration_days)->toDateString();

        $subscription->update([
            'status' => 'active',
            'end_date' => $renewedEndDate,
        ]);

        $payment = $subscription->payments()->create(
            $this->buildPaymentPayload(
                amount: $plan->price,
                method: $paymentMethod,
                transactionType: 'renew',
            ),
        );

        $subscription->loadMissing('user', 'plan');
        $subscription->user?->notify(new MembershipRenewed($subscription, $payment));

        $this->notificationService->markExpiryNotificationsAsRead($subscription->user_id);
        $this->notificationService->success(
            $subscription->user_id,
            'Subscription Renewed',
            "Your subscription to the {$plan->name} plan has been successfully renewed.",
            ['type' => 'subscription', 'id' => $subscription->id],
            '/customer/dashboard'
        );

        return $subscription->refresh();
    }

    public function updateStatus(Subscription $subscription, string $status): Subscription
    {
        $wasActive = $subscription->status === 'active';

        if ($status === 'active' && $subscription->start_date->isAfter(now()->startOfDay())) {
            $subscription->update([
                'scheduled_activation' => true,
            ]);

            $pendingPayment = $subscription->payments()->where('status', 'pending')->first();
            if ($pendingPayment) {
                $pendingPayment->update(['status' => 'completed']);
            } elseif (! $subscription->payments()->exists()) {
                $subscription->loadMissing('plan');
                $subscription->payments()->create(
                    $this->buildPaymentPayload(
                        amount: $subscription->plan->price,
                        method: 'manual',
                        transactionType: $this->resolveManualTransactionType($subscription),
                    ),
                );
            }

            return $subscription->refresh();
        }

        if ($status === 'active') {
            $this->ensureNoDuplicateActivePlan(
                (int) $subscription->user_id,
                (int) $subscription->plan_id,
                (int) $subscription->id,
            );
        }

        $subscription->update([
            'status' => $status,
            'scheduled_activation' => false,
        ]);

        if ($status === 'active') {
            if ($subscription->end_date->isPast()) {
                $subscription->update([
                    'start_date' => now(),
                    'end_date' => now()->addDays($subscription->plan->duration_days),
                ]);
            }

            $pendingPayment = $subscription->payments()->where('status', 'pending')->first();

            if ($pendingPayment) {
                $pendingPayment->update(['status' => 'completed']);
                $renewalPayment = $pendingPayment->refresh();
            } elseif (! $subscription->payments()->exists()) {
                $transactionType = $this->resolveManualTransactionType($subscription);
                $renewalPayment = $subscription->payments()->create(
                    $this->buildPaymentPayload(
                        amount: $subscription->plan->price,
                        method: 'manual',
                        transactionType: $transactionType,
                    ),
                );
            } elseif ($this->paymentSupportsTransactionType()) {
                $renewalPayment = $subscription->payments()
                    ->where('status', 'completed')
                    ->where('transaction_type', 'renew')
                    ->latest()
                    ->first();
            } else {
                $renewalPayment = null;
            }

            if (! $wasActive && ($renewalPayment?->transaction_type ?? null) === 'renew') {
                $subscription->loadMissing('user', 'plan');
                $subscription->user?->notify(new MembershipRenewed($subscription, $renewalPayment));
            }

            $this->notificationService->markExpiryNotificationsAsRead($subscription->user_id);
            $this->notificationService->success(
                $subscription->user_id,
                'Subscription Approved',
                "Your pending subscription to the {$subscription->plan->name} plan has been approved and is now active.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );
        } elseif ($status === 'expired') {
            $this->notificationService->warning(
                $subscription->user_id,
                'Subscription Expired',
                "Your subscription to the {$subscription->plan->name} plan has expired.",
                ['type' => 'subscription', 'id' => $subscription->id],
                '/customer/dashboard'
            );
        } elseif ($status === 'denied') {
            $this->notificationService->error(
                $subscription->user_id,
                'Subscription Denied',
                "Your pending subscription application or payment for the {$subscription->plan->name} plan was denied.",
                ['type' => 'subscription', 'id' => $subscription->id]
            );
        }

        return $subscription;
    }

    private function ensureNoDuplicateActivePlan(int $userId, int $planId, ?int $ignoreSubscriptionId = null): void
    {
        $duplicateQuery = Subscription::query()
            ->where('user_id', $userId)
            ->where('plan_id', $planId)
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString());

        if ($ignoreSubscriptionId) {
            $duplicateQuery->where('id', '!=', $ignoreSubscriptionId);
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'plan_id' => 'This customer already has this plan active.',
            ]);
        }
    }

    private function resolveManualTransactionType(Subscription $subscription): string
    {
        $hasOtherActivePlanCycle = Subscription::query()
            ->where('user_id', $subscription->user_id)
            ->where('plan_id', $subscription->plan_id)
            ->where('id', '!=', $subscription->id)
            ->whereIn('status', ['active', 'expired'])
            ->exists();

        return $hasOtherActivePlanCycle ? 'renew' : 'new';
    }

    private function buildPaymentPayload(float|int|string $amount, string $method, string $transactionType): array
    {
        $payload = [
            'amount' => $amount,
            'method' => $method,
            'status' => 'completed',
        ];

        if ($this->paymentSupportsTransactionType()) {
            $payload['transaction_type'] = $transactionType;
        }

        return $payload;
    }

    private function paymentSupportsTransactionType(): bool
    {
        if ($this->supportsPaymentTransactionType === null) {
            $this->supportsPaymentTransactionType = Schema::hasColumn('payments', 'transaction_type');
        }

        return $this->supportsPaymentTransactionType;
    }
}
