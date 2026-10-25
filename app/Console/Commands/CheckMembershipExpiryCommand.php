<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\MembershipExpired;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckMembershipExpiryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'membership:check-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for expired memberships and update their status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->whereDate('end_date', '<', now())
            ->with('user')
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No newly expired memberships found.');

            return;
        }

        $this->info("Found {$expiredSubscriptions->count()} memberships to expire. Processing...");

        foreach ($expiredSubscriptions as $subscription) {
            $user = $subscription->user;

            // Update status
            $subscription->update(['status' => 'expired']);

            // Log activity manually (since auth()->id() is null in console)
            $subscription->logActivity('expired', "Membership manually transitioned to expired status for user: {$user->email}");

            // Check for duplicate 'Expired' notifications
            // We search standard Laravel notifications for this specific subscription and type
            $alreadyNotified = $user->notifications()
                ->where('type', MembershipExpired::class)
                ->where('data->subscription_id', $subscription->id)
                ->exists();

            if (! $alreadyNotified) {
                $user->notify(new MembershipExpired($subscription));
                $this->info("Expired notification sent to: {$user->email}");
            } else {
                $this->warn("Duplicate notification skipped for: {$user->email}");
            }
        }

        $this->info('Membership expiry check completed.');
        Log::info("Membership expiry check completed. Processed {$expiredSubscriptions->count()} subscriptions.");
    }
}
