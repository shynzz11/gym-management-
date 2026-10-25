<?php

use App\Enums\UserType;
use App\Models\Facility;
use App\Models\Plan;
use App\Models\PlanAccessType;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);

    $this->superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);
});

test('plan index includes facilities for each plan details view', function () {
    Facility::query()->create([
        'name' => 'Spin Room',
        'description' => 'Cycling zone',
    ]);

    Facility::query()->create([
        'name' => 'Cold Plunge',
        'description' => 'Recovery pool',
    ]);

    Plan::query()->create([
        'name' => 'Performance Plus',
        'price' => 89.99,
        'duration_days' => 30,
        'description' => 'Advanced training',
        'facilities' => ['Spin Room', 'Cold Plunge'],
        'is_active' => true,
        'is_default' => false,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Plans/Index')
            ->where('plans', fn ($plans): bool => collect($plans)->contains(function (array $plan): bool {
                return ($plan['name'] ?? null) === 'Performance Plus'
                    && ($plan['facilities'] ?? []) === ['Spin Room', 'Cold Plunge'];
            }))
            ->where('facilities', fn ($facilities): bool => collect($facilities)->contains(function (array $facility): bool {
                return ($facility['name'] ?? null) === 'Spin Room';
            }) && collect($facilities)->contains(function (array $facility): bool {
                return ($facility['name'] ?? null) === 'Cold Plunge';
            }))
        );
});

test('plan store uses selected access type times without manual time inputs', function () {
    Facility::query()->firstOrCreate(['name' => 'Gym Access']);
    Facility::query()->firstOrCreate(['name' => 'Locker']);
    Facility::query()->firstOrCreate(['name' => 'Steam Bath']);

    $morningAccessType = PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning-access',
        'description' => 'Morning only access window',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.plans.store'), [
            'name' => 'Morning Starter',
            'price' => '39.99',
            'duration_days' => 30,
            'description' => 'Morning training plan',
            'facilities' => [' Gym Access ', 'Locker', 'locker', '', 'Steam Bath'],
            'is_active' => true,
            'is_default' => false,
            'access_type' => $morningAccessType->slug,
        ]);

    $response->assertRedirect(route('admin.plans.index'));

    $plan = Plan::query()->where('name', 'Morning Starter')->firstOrFail();

    expect($plan->access_type)->toBe($morningAccessType->slug)
        ->and($plan->start_time)->toBe('06:00:00')
        ->and($plan->end_time)->toBe('12:00:00')
        ->and($plan->facilities)->toBe(['Gym Access', 'Locker', 'Steam Bath']);
});

test('plan update syncs times from selected access type and ignores manual times', function () {
    Facility::query()->firstOrCreate(['name' => 'Personal Trainer']);
    Facility::query()->firstOrCreate(['name' => 'Sauna']);
    Facility::query()->firstOrCreate(['name' => 'Group Classes']);

    $morningAccessType = PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning-access',
        'description' => 'Morning only access window',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $eveningAccessType = PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening-access',
        'description' => 'Evening only access window',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $plan = Plan::query()->create([
        'name' => 'Any Plan',
        'price' => 29.99,
        'duration_days' => 30,
        'description' => 'Initial plan',
        'access_type' => $morningAccessType->slug,
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
    ]);

    $response = $this->actingAs($this->superAdmin, 'admin')
        ->put(route('admin.plans.update', $plan), [
            'name' => 'Evening Plan',
            'price' => '44.99',
            'duration_days' => 45,
            'description' => 'Updated plan',
            'facilities' => ['Personal Trainer', 'Sauna', '', 'sauna', 'Group Classes'],
            'is_active' => true,
            'is_default' => false,
            'access_type' => $eveningAccessType->slug,
            'start_time' => '01:00',
            'end_time' => '02:00',
        ]);

    $response->assertRedirect(route('admin.plans.index'));

    $plan->refresh();

    expect($plan->name)->toBe('Evening Plan')
        ->and($plan->access_type)->toBe($eveningAccessType->slug)
        ->and($plan->start_time)->toBe('16:00:00')
        ->and($plan->end_time)->toBe('22:00:00')
        ->and($plan->facilities)->toBe(['Personal Trainer', 'Sauna', 'Group Classes']);
});

test('plan store keeps custom facilities added from modal', function () {
    Facility::query()->firstOrCreate(['name' => 'Gym Access']);

    $anytimeAccessType = PlanAccessType::query()->create([
        'name' => 'Anytime Access',
        'slug' => 'anytime-access',
        'description' => 'Always available',
        'is_anytime' => true,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.plans.store'), [
            'name' => 'Custom Facility Plan',
            'price' => '59.99',
            'duration_days' => 30,
            'description' => 'Plan with custom facility',
            'facilities' => ['Gym Access', 'Ice Bath Zone'],
            'is_active' => true,
            'is_default' => false,
            'access_type' => $anytimeAccessType->slug,
        ]);

    $response->assertRedirect(route('admin.plans.index'));

    $plan = Plan::query()->where('name', 'Custom Facility Plan')->firstOrFail();

    expect($plan->facilities)
        ->toBe(['Gym Access', 'Ice Bath Zone']);
});
