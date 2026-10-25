<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'end_date']);
            $table->index(['user_id', 'plan_id', 'status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('subscriptions_user_id_status_end_date_index');
            $table->dropIndex('subscriptions_user_id_plan_id_status_end_date_index');
        });
    }
};
