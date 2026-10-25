<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->string('access_type')->default('anytime')->after('duration_days');
            $table->time('start_time')->nullable()->after('access_type');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('is_offer')->default(false)->after('end_time');
            $table->decimal('offer_price', 10, 2)->nullable()->after('is_offer');
            $table->dateTime('offer_ends_at')->nullable()->after('offer_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn([
                'access_type',
                'start_time',
                'end_time',
                'is_offer',
                'offer_price',
                'offer_ends_at',
            ]);
        });
    }
};
