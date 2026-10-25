<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendMembershipReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'gymos:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $targetDate = now()->addDays(3)->toDateString();

        $subscriptions = \App\Models\Subscription::active()
            ->with(['user', 'plan'])
            ->whereDate('end_date', $targetDate)
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->info('No memberships expiring in 3 days.');

            return;
        }

        $this->info("Found {$subscriptions->count()} memberships expiring in 3 days. Sending notifications...");

        foreach ($subscriptions as $subscription) {
            $subscription->user->notify(new \App\Notifications\MembershipExpiringSoon($subscription));
            $this->info("Notification sent to: {$subscription->user->email}");
        }

        $this->info('All notifications sent.');
    }
}
