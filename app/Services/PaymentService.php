<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Notifications\MembershipRenewed;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    private ?bool $supportsPaymentTransactionType = null;

    private ?bool $supportsPaymentDenialReason = null;

    public function getAll(array $filters = []): Collection
    {
        $customerId = isset($filters['customer_id']) ? (int) $filters['customer_id'] : null;
        $planId = isset($filters['plan_id']) ? (int) $filters['plan_id'] : null;
        $method = isset($filters['method']) ? trim((string) $filters['method']) : '';
        $type = isset($filters['type']) ? trim((string) $filters['type']) : '';
        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';

        return Payment::query()
            ->with(['subscription.user:id,name,email', 'subscription.plan:id,name'])
            ->when($customerId, function (Builder $query) use ($customerId): void {
                $query->whereHas('subscription', function (Builder $subscriptionQuery) use ($customerId): void {
                    $subscriptionQuery->where('user_id', $customerId);
                });
            })
            ->when($planId, function (Builder $query) use ($planId): void {
                $query->whereHas('subscription', function (Builder $subscriptionQuery) use ($planId): void {
                    $subscriptionQuery->where('plan_id', $planId);
                });
            })
            ->when($method !== '', function (Builder $query) use ($method): void {
                $query->where('method', $method);
            })
            ->when($status !== '', function (Builder $query) use ($status): void {
                $query->where('status', $status);
            })
            ->when($type !== '' && $this->paymentSupportsTransactionType(), function (Builder $query) use ($type): void {
                $query->where('transaction_type', $type);
            })
            ->latest()
            ->get();
    }

    /**
     * @return array{methods: array<int, string>, types: array<int, string>, supportsTransactionType: bool}
     */
    public function getFilterOptions(): array
    {
        $supportsTransactionType = $this->paymentSupportsTransactionType();

        $methods = Payment::query()
            ->whereNotNull('method')
            ->select('method')
            ->distinct()
            ->orderBy('method')
            ->pluck('method')
            ->filter(fn (mixed $method): bool => is_string($method) && trim($method) !== '')
            ->values()
            ->all();

        $types = [];

        if ($supportsTransactionType) {
            $types = Payment::query()
                ->whereNotNull('transaction_type')
                ->select('transaction_type')
                ->distinct()
                ->orderBy('transaction_type')
                ->pluck('transaction_type')
                ->filter(fn (mixed $type): bool => is_string($type) && trim($type) !== '')
                ->values()
                ->all();
        }

        return [
            'methods' => $methods,
            'types' => $types,
            'supportsTransactionType' => $supportsTransactionType,
        ];
    }

    public function updatePendingStatus(Payment $payment, string $decision, ?string $denialReason = null): Payment
    {
        return DB::transaction(function () use ($payment, $decision, $denialReason): Payment {
            $payment->refresh();
            $payment->loadMissing('subscription.plan');

            if (strtolower((string) $payment->status) !== 'pending') {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be processed.',
                ]);
            }

            if ($decision === 'approve') {
                $this->applyApproval($payment);

                return $payment->refresh();
            }

            $this->applyDeny($payment, $denialReason);

            return $payment->refresh();
        });
    }

    private function applyApproval(Payment $payment): void
    {
        $subscription = $payment->subscription;
        $plan = $subscription?->plan;

        if (! $subscription || ! $plan) {
            throw ValidationException::withMessages([
                'payment' => 'Payment subscription is invalid.',
            ]);
        }

        $transactionType = strtolower((string) ($payment->transaction_type ?? 'new'));

        if ($transactionType === 'renew') {
            $this->approveRenewPayment($payment, $subscription);
        } elseif (strtolower((string) $subscription->status) === 'pending') {
            $this->activateSubscriptionCycle($subscription);
        }

        $payment->forceFill([
            'status' => 'completed',
        ]);

        if ($this->paymentSupportsDenialReason()) {
            $payment->forceFill([
                'denial_reason' => null,
            ]);
        }

        $payment->save();

        if ($transactionType === 'renew') {
            $payment->unsetRelation('subscription');
            $payment->load('subscription.user', 'subscription.plan');
            $payment->subscription?->user?->notify(new MembershipRenewed($payment->subscription, $payment));
        }
    }

    private function applyDeny(Payment $payment, ?string $denialReason): void
    {
        $subscription = $payment->subscription;

        $payment->forceFill([
            'status' => 'canceled',
        ]);

        if ($this->paymentSupportsDenialReason()) {
            $payment->forceFill([
                'denial_reason' => $denialReason ? trim($denialReason) : null,
            ]);
        }

        $payment->save();

        if ($subscription && strtolower((string) $subscription->status) === 'pending') {
            $subscription->delete();

            if ($this->paymentSupportsTransactionType()) {
                $payment->forceFill(['transaction_type' => 'new'])->save();
            }
        }
    }

    private function approveRenewPayment(Payment $payment, Subscription $subscription): void
    {
        if (strtolower((string) $subscription->status) === 'pending') {
            $renewalTarget = Subscription::query()
                ->where('user_id', $subscription->user_id)
                ->where('plan_id', $subscription->plan_id)
                ->where('id', '!=', $subscription->id)
                ->whereIn('status', ['active', 'expired'])
                ->latest('end_date')
                ->first();

            if ($renewalTarget) {
                $renewalTarget->loadMissing('plan');
                $this->extendSubscriptionCycle($renewalTarget);
                $payment->subscription_id = $renewalTarget->id;
                $subscription->update([
                    'status' => 'expired',
                ]);

                return;
            }

            $this->activateSubscriptionCycle($subscription);

            return;
        }

        $this->extendSubscriptionCycle($subscription);
    }

    private function activateSubscriptionCycle(Subscription $subscription): void
    {
        $subscription->loadMissing('plan');
        $durationDays = (int) ($subscription->plan?->duration_days ?? 30);
        $startedAt = now()->toDateString();
        $endsAt = now()->addDays($durationDays)->toDateString();

        $subscription->update([
            'status' => 'active',
            'start_date' => $startedAt,
            'end_date' => $endsAt,
        ]);
    }

    private function extendSubscriptionCycle(Subscription $subscription): void
    {
        $subscription->loadMissing('plan');
        $today = now()->startOfDay();
        $durationDays = (int) ($subscription->plan?->duration_days ?? 30);

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
        $renewedEndDate = $renewalBaseDate->copy()->addDays($durationDays)->toDateString();

        $payload = [
            'status' => 'active',
            'end_date' => $renewedEndDate,
        ];

        if (empty($subscription->start_date)) {
            $payload['start_date'] = now()->toDateString();
        }

        $subscription->update($payload);
    }

    private function paymentSupportsTransactionType(): bool
    {
        if ($this->supportsPaymentTransactionType === null) {
            $this->supportsPaymentTransactionType = Schema::hasColumn('payments', 'transaction_type');
        }

        return $this->supportsPaymentTransactionType;
    }

    private function paymentSupportsDenialReason(): bool
    {
        if ($this->supportsPaymentDenialReason === null) {
            $this->supportsPaymentDenialReason = Schema::hasColumn('payments', 'denial_reason');
        }

        return $this->supportsPaymentDenialReason;
    }
}
