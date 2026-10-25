<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plan::query()->updateOrCreate(
            ['name' => 'Basic'],
            [
                'price' => 29.99,
                'duration_days' => 30,
                'description' => 'Entry level fitness plan.',
                'facilities' => [
                    'Gym Access',
                    'Locker',
                    'Shower',
                ],
            ],
        );

        Plan::query()->updateOrCreate(
            ['name' => 'Pro'],
            [
                'price' => 79.99,
                'duration_days' => 90,
                'description' => 'Professional training plan.',
                'facilities' => [
                    'Gym Access',
                    'Personal Trainer',
                    'Locker',
                    'Shower',
                    'Group Classes',
                    'Nutrition Chart',
                ],
            ],
        );

        Plan::query()->updateOrCreate(
            ['name' => 'VIP'],
            [
                'price' => 249.99,
                'duration_days' => 365,
                'description' => 'Full access elite plan.',
                'facilities' => [
                    'Gym Access',
                    'Personal Trainer',
                    'Locker',
                    'Shower',
                    'Group Classes',
                    'Sauna',
                    'Nutrition Chart',
                ],
            ],
        );
    }
}
