<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\MembershipRenewed;
use App\Services\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateScheduledSubscriptions extends Command
{
    protected $signature = 'membership:activate-scheduled';

    protected $description = 'Activate approved memberships whose scheduled start date has arrived.';

    public function handle(NotificationService $notificationService): int
    {
        $activatedCount = 0;

        Subscription::query()
            ->where('status', 'pending')
            ->where('scheduled_activation', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->with(['user', 'plan'])
            ->chunkById(100, function ($subscriptions) use (&$activatedCount, $notificationService): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, &$activatedCount, $notificationService): void {
                        $subscription->refresh();

                        if (
                            $subscription->status !== 'pending'
                            || ! $subscription->scheduled_activation
                            || $subscription->start_date->isAfter(now()->startOfDay())
                        ) {
                            return;
                        }

                        $subscription->update([
                            'status' => 'active',
                            'scheduled_activation' => false,
                        ]);

                        $this->sendRenewalConfirmation($subscription);
                        $notificationService->success(
                            $subscription->user_id,
                            'Subscription Activated',
                            "Your subscription to the {$subscription->plan->name} plan is now active.",
                            ['type' => 'subscription', 'id' => $subscription->id],
                            '/customer/dashboard'
                        );

                        $activatedCount++;
                    });
                }
            });

        $this->info("Activated {$activatedCount} scheduled subscription(s).");

        return self::SUCCESS;
    }

    private function sendRenewalConfirmation(Subscription $subscription): void
    {
        $renewalPayment = $subscription->payments()
            ->where('status', 'completed')
            ->where('transaction_type', 'renew')
            ->latest()
            ->first();

        if ($renewalPayment && $subscription->user) {
            $subscription->user->notify(new MembershipRenewed($subscription, $renewalPayment));
        }
    }
}
