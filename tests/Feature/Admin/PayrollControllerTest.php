<?php

use App\Constants\Permissions;
use App\Enums\UserType;
use App\Models\PayrollPeriod;
use App\Models\PayrollStaff;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(ValidateCsrfToken::class);
    $this->admin = User::factory()->create([
        'type' => UserType::ADMIN,
        'status' => 'active',
    ]);
    $payrollPermission = Permission::query()->firstOrCreate([
        'name' => Permissions::MANAGE_PAYROLL,
    ]);
    $payrollRole = Role::query()->create([
        'name' => 'Payroll Administrator',
        'description' => 'Payroll test access',
    ]);
    $payrollRole->permissions()->attach($payrollPermission);
    $this->admin->roles()->attach($payrollRole);
});

afterEach(function () {
    Carbon::setTestNow();
});

test('admin can open payroll and view monthly salary periods', function () {
    $staff = PayrollStaff::query()->create([
        'name' => 'Trainer One',
        'joining_date' => now()->subMonths(2)->toDateString(),
        'current_salary' => 25000,
        'due_day' => 10,
        'first_pay_period' => now()->subMonth()->startOfMonth()->toDateString(),
        'generation_start_period' => now()->subMonth()->startOfMonth()->toDateString(),
        'is_active' => true,
    ]);

    $this->actingAs($this->admin, 'admin')
        ->get(route('admin.payroll.index', ['month' => now()->format('Y-m')]))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Payroll/Index')
            ->where('filters.month', now()->format('Y-m'))
            ->has('periods', 1)
            ->where('periods.0.staff.id', $staff->id)
            ->where('periods.0.status', 'due')
            ->where('summary.due_count', 1)
        );
});

test('monthly period generation recognizes existing sqlite dates stored with time components', function () {
    $month = now()->startOfMonth();
    $staff = PayrollStaff::query()->create([
        'name' => 'Trainer Existing Period',
        'joining_date' => $month->toDateString(),
        'current_salary' => 10000,
        'due_day' => 26,
        'first_pay_period' => $month->toDateString(),
        'generation_start_period' => $month->toDateString(),
        'is_active' => true,
    ]);

    DB::table('payroll_periods')->insert([
        'payroll_staff_id' => $staff->id,
        'period_start' => $month->toDateString().' 00:00:00',
        'due_date' => $month->copy()->day(26)->toDateString().' 00:00:00',
        'salary_amount' => '10000.00',
        'status' => 'due',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    app(PayrollService::class)->generatePeriodsForMonth($month);

    expect($staff->periods()->whereDate('period_start', $month->toDateString())->count())->toBe(1);
});

test('admin can create staff and monthly salary periods with month-end due-date adjustment', function () {
    $this->actingAs($this->admin, 'admin')
        ->post(route('admin.payroll.staff.store'), [
            'name' => 'Morgan Trainer',
            'joining_date' => '2026-02-01',
            'current_salary' => '32000.00',
            'due_day' => 31,
            'first_pay_period' => '2026-02',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Morgan Trainer was added to payroll.');

    $staff = PayrollStaff::query()->where('name', 'Morgan Trainer')->firstOrFail();

    $firstPeriod = $staff->periods()->whereDate('period_start', '2026-02-01')->firstOrFail();
    expect($firstPeriod->due_date->toDateString())->toBe('2026-02-28');
    expect($firstPeriod->salary_amount)->toBe('32000.00');
    expect($firstPeriod->status)->toBe('due');

    expect($staff->first_pay_period->toDateString())->toBe('2026-02-01');
});

test('marking salary paid and reverting it preserves payment and correction history', function () {
    $staff = PayrollStaff::query()->create([
        'name' => 'Trainer Two',
        'joining_date' => now()->startOfMonth()->toDateString(),
        'current_salary' => 30000,
        'due_day' => 5,
        'first_pay_period' => now()->startOfMonth()->toDateString(),
        'generation_start_period' => now()->startOfMonth()->toDateString(),
        'is_active' => true,
    ]);
    $period = PayrollPeriod::query()->create([
        'payroll_staff_id' => $staff->id,
        'period_start' => now()->startOfMonth()->toDateString(),
        'due_date' => now()->startOfMonth()->addDays(4)->toDateString(),
        'salary_amount' => 30000,
        'status' => 'due',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->patch(route('admin.payroll.periods.paid', $period), [
            'amount' => '32500.00',
            'notes' => 'Bonus included',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Salary marked as paid.');

    $this->assertDatabaseHas('payroll_periods', [
        'id' => $period->id,
        'status' => 'paid',
        'actual_paid_amount' => '32500.00',
        'notes' => 'Bonus included',
    ]);
    $this->assertDatabaseHas('payroll_events', [
        'payroll_period_id' => $period->id,
        'admin_user_id' => $this->admin->id,
        'action' => 'paid',
        'amount' => '32500.00',
        'notes' => 'Bonus included',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->patch(route('admin.payroll.periods.due', $period), [
            'notes' => 'Payment entry was recorded in error',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payment correction recorded; salary is now due.');

    $this->assertDatabaseHas('payroll_periods', [
        'id' => $period->id,
        'status' => 'due',
        'actual_paid_amount' => null,
        'paid_at' => null,
        'notes' => null,
    ]);
    $this->assertDatabaseHas('payroll_events', [
        'payroll_period_id' => $period->id,
        'action' => 'reverted',
        'amount' => '32500.00',
        'notes' => 'Payment entry was recorded in error',
    ]);
    expect($period->events()->count())->toBe(2);
});

test('salary changes update future unpaid periods but preserve current period snapshot', function () {
    $staff = PayrollStaff::query()->create([
        'name' => 'Trainer Three',
        'joining_date' => now()->subMonth()->toDateString(),
        'current_salary' => 30000,
        'due_day' => 5,
        'first_pay_period' => now()->subMonth()->startOfMonth()->toDateString(),
        'generation_start_period' => now()->subMonth()->startOfMonth()->toDateString(),
        'is_active' => true,
    ]);
    app(PayrollService::class)->generatePeriodsThrough(now());
    $currentPeriod = $staff->periods()->whereDate('period_start', now()->startOfMonth())->firstOrFail();
    $futureMonth = now()->startOfMonth()->addMonthNoOverflow();
    $futurePeriod = $staff->periods()->create([
        'period_start' => $futureMonth->toDateString(),
        'due_date' => $futureMonth->copy()->day(5)->toDateString(),
        'salary_amount' => 30000,
        'status' => 'due',
    ]);

    $this->actingAs($this->admin, 'admin')
        ->put(route('admin.payroll.staff.update', $staff), [
            'name' => $staff->name,
            'joining_date' => $staff->joining_date->toDateString(),
            'current_salary' => '35000.00',
            'due_day' => 12,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payroll staff details updated.');

    expect($currentPeriod->refresh()->salary_amount)->toBe('30000.00');
    expect($futurePeriod->refresh()->salary_amount)->toBe('35000.00');
    expect($futurePeriod->due_date->toDateString())->toBe($futureMonth->copy()->day(12)->toDateString());
});

test('deactivating staff retains history and reactivation does not backfill inactive months', function () {
    Carbon::setTestNow('2026-08-25 12:00:00');
    $staff = PayrollStaff::query()->create([
        'name' => 'Trainer Four',
        'joining_date' => now()->startOfMonth()->toDateString(),
        'current_salary' => 28000,
        'due_day' => 15,
        'first_pay_period' => now()->startOfMonth()->toDateString(),
        'generation_start_period' => now()->startOfMonth()->toDateString(),
        'is_active' => true,
    ]);
    app(PayrollService::class)->generatePeriodsThrough(now());
    $periodCount = $staff->periods()->count();

    $this->actingAs($this->admin, 'admin')
        ->patch(route('admin.payroll.staff.status', $staff), [
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payroll staff member deactivated; payroll history was retained.');

    Carbon::setTestNow('2026-10-25 12:00:00');
    $this->actingAs($this->admin, 'admin')
        ->patch(route('admin.payroll.staff.status', $staff), [
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Payroll staff member reactivated.');

    expect($staff->refresh()->is_active)->toBeTrue();
    expect($staff->periods()->count())->toBe($periodCount + 1);
    expect($staff->periods()->whereDate('period_start', '2026-09-01')->exists())->toBeFalse();
    expect($staff->periods()->whereDate('period_start', '2026-10-01')->exists())->toBeTrue();
});

test('users without payroll permission cannot access payroll even if they are admins', function () {
    $manager = User::factory()->create([
        'type' => UserType::ADMIN,
    ]);
    $manager->roles()->attach(Role::query()->create([
        'name' => 'Manager Without Payroll',
        'description' => 'No payroll access',
    ]));

    $this->actingAs($manager, 'admin')
        ->get(route('admin.payroll.index'))
        ->assertForbidden();
});

test('finance role can be granted payroll without unrelated permissions', function () {
    $finance = User::factory()->create([
        'type' => UserType::ADMIN,
    ]);
    $financeRole = app(\App\Services\RoleService::class)->create([
        'name' => 'Finance',
        'description' => 'Payroll-only finance access',
        'permissions' => [Permissions::MANAGE_PAYROLL],
    ]);
    $finance->roles()->attach($financeRole);

    $this->actingAs($finance, 'admin')
        ->get(route('admin.payroll.index'))
        ->assertSuccessful();

    expect($finance->hasPermission(Permissions::MANAGE_PAYROLL))->toBeTrue()
        ->and($finance->hasPermission(Permissions::DASHBOARD_VIEW))->toBeFalse();
});

test('role editor exposes staff payroll as a selectable permission', function () {
    $superAdmin = User::factory()->create([
        'type' => UserType::SUPER_ADMIN,
        'status' => 'active',
    ]);

    $this->actingAs($superAdmin, 'admin')
        ->get(route('admin.roles.create'))
        ->assertSuccessful()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('Admin/Roles/Create')
            ->where('permissions.gym_section.sub_modules.manage_payroll.route', 'admin.payroll.index')
            ->where('permissions.gym_section.sub_modules.manage_payroll.label', 'Staff Payroll')
        );
});

test('customers cannot access the admin payroll page', function () {
    $customer = User::factory()->create([
        'type' => UserType::CUSTOMER,
    ]);

    $this->actingAs($customer, 'admin')
        ->get(route('admin.payroll.index'))
        ->assertForbidden();
});
