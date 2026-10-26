<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ActivateScheduledSubscriptions extends Command
{
    protected $signature = 'membership:activate-scheduled';

    protected $description = 'Activate approved memberships whose scheduled start date has arrived.';

    public function handle(): int
    {
        $activatedCount = 0;

        Subscription::query()
            ->where('status', 'pending')
            ->where('scheduled_activation', true)
            ->whereDate('start_date', '<=', now()->toDateString())
            ->chunkById(100, function ($subscriptions) use (&$activatedCount): void {
                foreach ($subscriptions as $subscription) {
                    DB::transaction(function () use ($subscription, &$activatedCount): void {
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

                        $activatedCount++;
                    });
                }
            });

        $this->info("Activated {$activatedCount} scheduled subscription(s).");

        return self::SUCCESS;
    }
}
