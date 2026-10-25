<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Seed Access Types
        $types = [
            [
                'name' => 'Anytime Access',
                'slug' => 'anytime',
                'description' => '24/7 unlimited access to facility',
                'is_anytime' => true,
            ],
            [
                'name' => 'Morning Only',
                'slug' => 'morning',
                'description' => 'Restricted to 6 AM - 12 PM',
                'start_time' => '06:00',
                'end_time' => '12:00',
            ],
            [
                'name' => 'Evening Only',
                'slug' => 'evening',
                'description' => 'Restricted to 4 PM - 10 PM',
                'start_time' => '16:00',
                'end_time' => '22:00',
            ],
            [
                'name' => 'Off-Peak Hours',
                'slug' => 'off_peak',
                'description' => 'Restricted to 10 AM - 4 PM',
                'start_time' => '10:00',
                'end_time' => '16:00',
            ],
            [
                'name' => 'Admin / Staff',
                'slug' => 'admin',
                'description' => 'Unlimited access for internal personnel',
                'is_anytime' => true,
            ],
        ];

        foreach ($types as $type) {
            \App\Models\PlanAccessType::updateOrCreate(['slug' => $type['slug']], $type);
        }

        // 2. Seed the requested Basic Plan
        \App\Models\Plan::updateOrCreate(
            ['name' => 'Starter Plan'],
            [
                'price' => 10.00,
                'duration_days' => 30,
                'description' => 'Simple entry-level plan with anytime access.',
                'is_active' => true,
                'is_default' => false,
                'access_type' => 'anytime',
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \App\Models\Plan::where('name', 'Starter Plan')->delete();
        \App\Models\PlanAccessType::query()->delete();
    }
};
