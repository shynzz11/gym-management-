<?php

use App\Enums\UserType;
use App\Models\Facility;
use App\Models\Plan;
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

test('facilities index renders for admin gym management', function () {
    Facility::query()->firstOrCreate(['name' => 'Steam Bath']);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.facilities.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Plans/Facilities/Index')
            ->has('facilities.data')
            ->where('facilities.current_page', 1)
            ->where('facilities.per_page', 10)
            ->where('totalFacilities', Facility::query()->count())
            ->where('filters.search', '')
        );
});

test('facilities index supports search', function () {
    Facility::query()->firstOrCreate(['name' => 'Zen Recovery Suite']);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.facilities.index', ['search' => 'Zen Recovery Suite']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.search', 'Zen Recovery Suite')
            ->where('facilities.data.0.name', 'Zen Recovery Suite')
        );
});

test('facilities index shows latest records first', function () {
    $olderFacility = Facility::query()->create([
        'name' => 'Steam Bath',
    ]);

    $newerFacility = Facility::query()->create([
        'name' => 'Cold Plunge',
    ]);

    Facility::query()->whereKey($olderFacility->id)->update([
        'created_at' => now()->subDay(),
    ]);

    Facility::query()->whereKey($newerFacility->id)->update([
        'created_at' => now(),
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.facilities.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('facilities.data', function ($facilities): bool {
                $facilityRows = collect($facilities)->map(function ($facility): array {
                    return (array) $facility;
                })->all();
                $facilityNames = array_column($facilityRows, 'name');
                $coldPlungeIndex = array_search('Cold Plunge', $facilityNames, true);
                $steamBathIndex = array_search('Steam Bath', $facilityNames, true);

                return is_int($coldPlungeIndex)
                    && is_int($steamBathIndex)
                    && $coldPlungeIndex < $steamBathIndex;
            })
        );
});

test('facilities index supports pagination', function () {
    Facility::factory()->count(14)->create();

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.facilities.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('facilities.current_page', 2)
            ->where('facilities.per_page', 10)
        );
});

test('can create facility', function () {
    $this->actingAs($this->superAdmin, 'admin')
        ->post(route('admin.plans.facilities.store'), [
            'name' => 'Cold Plunge',
            'description' => 'Recovery zone',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Facility created successfully.');

    $this->assertDatabaseHas('facilities', [
        'name' => 'Cold Plunge',
        'description' => 'Recovery zone',
    ]);
});

test('can update facility and sync renamed facility in plans', function () {
    $facility = Facility::query()->firstOrCreate(
        ['name' => 'Locker'],
        ['description' => 'Storage locker'],
    );

    $plan = Plan::query()->create([
        'name' => 'Starter',
        'price' => 20,
        'duration_days' => 30,
        'description' => 'Starter plan',
        'facilities' => ['Locker', 'Shower'],
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->put(route('admin.plans.facilities.update', $facility), [
            'name' => 'Premium Locker',
            'description' => 'Upgraded storage locker',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Facility updated successfully.');

    $plan->refresh();

    expect($plan->facilities)->toContain('Premium Locker')
        ->and($plan->facilities)->not->toContain('Locker');

    $this->assertDatabaseHas('facilities', [
        'id' => $facility->id,
        'name' => 'Premium Locker',
    ]);
});

test('cannot update default facility', function () {
    $defaultFacility = Facility::query()
        ->where('name', 'All Gym Access')
        ->firstOrFail();

    $this->actingAs($this->superAdmin, 'admin')
        ->put(route('admin.plans.facilities.update', $defaultFacility), [
            'name' => 'All Premium Gym Access',
            'description' => 'Updated details',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', 'Default facility cannot be edited.');

    $this->assertDatabaseHas('facilities', [
        'id' => $defaultFacility->id,
        'name' => 'All Gym Access',
    ]);
});

test('cannot delete facility assigned to one or more plans', function () {
    $facility = Facility::query()->firstOrCreate([
        'name' => 'Sauna',
    ]);

    Plan::query()->create([
        'name' => 'Pro',
        'price' => 40,
        'duration_days' => 30,
        'description' => 'Pro plan',
        'facilities' => ['Sauna'],
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.facilities.destroy', $facility))
        ->assertRedirect()
        ->assertSessionHas('error', 'Cannot delete this facility while it is assigned to one or more plans.');

    $this->assertDatabaseHas('facilities', [
        'id' => $facility->id,
    ]);
});

test('cannot delete default facility', function () {
    $defaultFacility = Facility::query()
        ->where('name', 'All Gym Access')
        ->firstOrFail();

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.facilities.destroy', $defaultFacility))
        ->assertRedirect()
        ->assertSessionHas('error', 'Default facility cannot be deleted.');

    $this->assertDatabaseHas('facilities', [
        'id' => $defaultFacility->id,
    ]);
});

test('can delete facility when not assigned to plans', function () {
    $facility = Facility::query()->create([
        'name' => 'Spin Room',
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.facilities.destroy', $facility))
        ->assertRedirect()
        ->assertSessionHas('success', 'Facility deleted successfully.');

    $this->assertDatabaseMissing('facilities', [
        'id' => $facility->id,
    ]);
});
