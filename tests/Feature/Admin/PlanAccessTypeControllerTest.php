<?php

use App\Enums\UserType;
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

    Plan::query()->delete();
    PlanAccessType::query()->delete();
});

test('access types index uses plan access type terminology', function () {
    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.access-types.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Plans/AccessTypes/Index')
            ->has('accessTypes.data')
            ->where('accessTypes.current_page', 1)
            ->where('accessTypes.per_page', 10)
            ->where('totalAccessTypes', 0)
            ->where('filters.search', '')
        );
});

test('access types index supports search', function () {
    PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning-access',
        'description' => 'Morning schedule',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening-access',
        'description' => 'Evening schedule',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.access-types.index', ['search' => 'Morning']))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('filters.search', 'Morning')
            ->where('accessTypes.total', 1)
            ->where('accessTypes.data.0.slug', 'morning-access')
        );
});

test('access types index shows latest records first', function () {
    $olderAccessType = PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning-access',
        'description' => 'Morning schedule',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $newerAccessType = PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening-access',
        'description' => 'Evening schedule',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => false,
    ]);

    PlanAccessType::query()->whereKey($olderAccessType->id)->update([
        'created_at' => now()->subDay(),
    ]);

    PlanAccessType::query()->whereKey($newerAccessType->id)->update([
        'created_at' => now(),
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.access-types.index'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('accessTypes.data.0.slug', 'evening-access')
            ->where('accessTypes.data.1.slug', 'morning-access')
        );
});

test('access types index supports pagination', function () {
    foreach (range(1, 12) as $i) {
        PlanAccessType::query()->create([
            'name' => "Type {$i}",
            'slug' => "type-{$i}",
            'description' => "Schedule {$i}",
            'is_anytime' => true,
            'is_active' => true,
        ]);
    }

    $this->actingAs($this->superAdmin, 'admin')
        ->get(route('admin.plans.access-types.index', ['page' => 2]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('accessTypes.current_page', 2)
            ->where('accessTypes.per_page', 10)
            ->where('accessTypes.total', 12)
        );
});

test('cannot delete the last remaining access type', function () {
    $accessType = PlanAccessType::query()->create([
        'name' => 'Anytime Access',
        'slug' => 'anytime',
        'description' => '24/7 access',
        'is_anytime' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.access-types.destroy', $accessType->id))
        ->assertRedirect()
        ->assertSessionHas('error', 'At least one access type must remain. Create another access type before deleting this one.');

    $this->assertDatabaseHas('plan_access_types', [
        'id' => $accessType->id,
    ]);
});

test('cannot delete access type assigned to one or more plans', function () {
    $assignedAccessType = PlanAccessType::query()->create([
        'name' => 'Anytime Access',
        'slug' => 'anytime',
        'description' => '24/7 access',
        'is_anytime' => true,
        'is_active' => true,
    ]);

    PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening',
        'description' => 'Evening schedule',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    Plan::query()->create([
        'name' => 'Starter',
        'price' => 29.99,
        'duration_days' => 30,
        'description' => 'Starter plan',
        'is_active' => true,
        'is_default' => false,
        'access_type' => $assignedAccessType->slug,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.access-types.destroy', $assignedAccessType->id))
        ->assertRedirect()
        ->assertSessionHas('error', 'Cannot delete this access type while it is assigned to one or more plans. Reassign those plans first.');

    $this->assertDatabaseHas('plan_access_types', [
        'id' => $assignedAccessType->id,
    ]);
});

test('can delete an unassigned access type when another type remains', function () {
    PlanAccessType::query()->create([
        'name' => 'Anytime Access',
        'slug' => 'anytime',
        'description' => '24/7 access',
        'is_anytime' => true,
        'is_active' => true,
    ]);

    $deletableAccessType = PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening',
        'description' => 'Evening schedule',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->delete(route('admin.plans.access-types.destroy', $deletableAccessType->id))
        ->assertRedirect()
        ->assertSessionHas('success', 'Access type deleted successfully.');

    $this->assertDatabaseMissing('plan_access_types', [
        'id' => $deletableAccessType->id,
    ]);
});

test('cannot deactivate the only active access type', function () {
    $accessType = PlanAccessType::query()->create([
        'name' => 'Anytime Access',
        'slug' => 'anytime',
        'description' => '24/7 access',
        'is_anytime' => true,
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.plans.access-types.toggle-active', $accessType->id))
        ->assertRedirect()
        ->assertSessionHas('error', 'At least one active access type is required.');

    $this->assertDatabaseHas('plan_access_types', [
        'id' => $accessType->id,
        'is_active' => true,
    ]);
});

test('can toggle access type status when another active type exists', function () {
    $targetType = PlanAccessType::query()->create([
        'name' => 'Morning Access',
        'slug' => 'morning',
        'description' => 'Morning schedule',
        'start_time' => '06:00:00',
        'end_time' => '12:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    PlanAccessType::query()->create([
        'name' => 'Evening Access',
        'slug' => 'evening',
        'description' => 'Evening schedule',
        'start_time' => '16:00:00',
        'end_time' => '22:00:00',
        'is_anytime' => false,
        'is_active' => true,
    ]);

    $this->actingAs($this->superAdmin, 'admin')
        ->patch(route('admin.plans.access-types.toggle-active', $targetType->id))
        ->assertRedirect()
        ->assertSessionHas('success', 'Access type deactivated.');

    $this->assertDatabaseHas('plan_access_types', [
        'id' => $targetType->id,
        'is_active' => false,
    ]);
});
