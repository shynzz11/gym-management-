<?php

use App\Models\Plan;
use Inertia\Testing\AssertableInertia;

test('home page shows only active plans', function () {
    Plan::factory()->create([
        'name' => 'Inactive Plan',
        'is_active' => false,
    ]);

    Plan::factory()->create([
        'name' => 'Active Plan',
        'is_active' => true,
    ]);

    $this->get(route('home'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Guest/LandingPage')
            ->where('plans', function ($plans): bool {
                $planRows = collect($plans)->map(fn ($plan): array => (array) $plan)->all();
                $planNames = array_column($planRows, 'name');

                return in_array('Active Plan', $planNames, true)
                    && ! in_array('Inactive Plan', $planNames, true);
            })
        );
});
