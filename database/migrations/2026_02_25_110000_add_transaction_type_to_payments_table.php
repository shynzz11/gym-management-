<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('transaction_type', 20)->default('new')->after('status');
            $table->index('transaction_type');
        });

        DB::table('payments')
            ->select('subscription_id')
            ->distinct()
            ->pluck('subscription_id')
            ->each(function (int $subscriptionId): void {
                DB::table('payments')
                    ->where('subscription_id', $subscriptionId)
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->pluck('id')
                    ->values()
                    ->each(function (int $paymentId, int $index): void {
                        DB::table('payments')
                            ->where('id', $paymentId)
                            ->update([
                                'transaction_type' => $index === 0 ? 'new' : 'renew',
                            ]);
                    });
            });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['transaction_type']);
            $table->dropColumn('transaction_type');
        });
    }
};
