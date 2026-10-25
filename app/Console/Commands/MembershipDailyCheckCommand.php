<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\MembershipExpiringSoon;
use Illuminate\Console\Command;

class MembershipDailyCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:daily-check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send 3-day membership expiry warning emails and notifications.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $subscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereDate('end_date', $targetDate)
            ->with(['user', 'plan'])
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No active memberships expiring in 3 days.');

            return self::SUCCESS;
        }

        $sentCount = 0;

        foreach ($subscriptions as $subscription) {
            $user = $subscription->user;

            if (! $user) {
                continue;
            }

            $alreadyNotified = $user->notifications()
                ->where('type', MembershipExpiringSoon::class)
                ->where('data->subscription_id', $subscription->id)
                ->where('data->expiry_date', $subscription->end_date->format('Y-m-d'))
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $user->notify(new MembershipExpiringSoon($subscription));
            $sentCount++;
        }

        $this->info("Daily membership check complete. Sent {$sentCount} warning notification(s).");

        return self::SUCCESS;
    }
}
