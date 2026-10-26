<?php

namespace App\Console\Commands;

use App\Models\Subscription;
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
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('No newly expired memberships found.');

            return;
        }

        $this->info("Found {$expiredSubscriptions->count()} memberships to expire. Processing...");

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);
            $subscription->logActivity(
                'expired',
                "Membership manually transitioned to expired status for subscription: {$subscription->id}",
            );
        }

        $this->info('Membership expiry check completed.');
        Log::info("Membership expiry check completed. Processed {$expiredSubscriptions->count()} subscriptions.");
    }
}
