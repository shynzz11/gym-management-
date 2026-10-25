<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $plans = [
            [
                'name' => 'Elite Anytime',
                'price' => 59.99,
                'duration_days' => 30,
                'description' => 'Unlimited 24/7 access to all facilities and classes.',
                'is_active' => true,
                'is_default' => true,
                'access_type' => 'anytime',
            ],
            [
                'name' => 'Early Bird',
                'price' => 29.99,
                'duration_days' => 30,
                'description' => 'Special access for morning enthusiasts (6 AM - 12 PM).',
                'is_active' => true,
                'is_default' => false,
                'access_type' => 'morning',
            ],
            [
                'name' => 'Twilight Power',
                'price' => 34.99,
                'duration_days' => 30,
                'description' => 'Perfect for the evening crowd (4 PM - 10 PM).',
                'is_active' => true,
                'is_default' => false,
                'access_type' => 'evening',
            ],
            [
                'name' => 'Internal Staff',
                'price' => 0.00,
                'duration_days' => 365,
                'description' => 'Full access for staff members and administrators.',
                'is_active' => true,
                'is_default' => false,
                'access_type' => 'admin',
            ],
        ];

        foreach ($plans as $plan) {
            \App\Models\Plan::create($plan);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Plan::whereIn('name', ['Elite Anytime', 'Early Bird', 'Twilight Power', 'Internal Staff'])->delete();
    }
};
