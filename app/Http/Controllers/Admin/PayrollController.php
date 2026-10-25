<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Permissions;
use App\Http\Controllers\Controller;
use App\Models\PayrollPeriod;
use App\Models\PayrollStaff;
use App\Services\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(Request $request): Response
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = Carbon::createFromFormat('Y-m', $validated['month'] ?? now()->format('Y-m'))->startOfMonth();

        $this->payrollService->generatePeriodsForMonth($month);

        $periods = PayrollPeriod::query()
            ->with([
                'staff:id,name,joining_date,current_salary,due_day,is_active',
                'events' => fn ($query) => $query->with('admin:id,name')->latest('occurred_at'),
            ])
            ->whereDate('period_start', $month->toDateString())
            ->whereHas('staff')
            ->orderBy('due_date')
            ->orderBy('payroll_staff_id')
            ->get();

        return inertia('Admin/Payroll/Index', [
            'periods' => $periods,
            'staffMembers' => PayrollStaff::query()->orderBy('name')->get(),
            'filters' => ['month' => $month->format('Y-m')],
            'summary' => [
                'staff_count' => $periods->count(),
                'paid_count' => $periods->where('status', 'paid')->count(),
                'due_count' => $periods->where('status', 'due')->count(),
                'due_total' => $periods->where('status', 'due')->sum('salary_amount'),
                'paid_total' => $periods->where('status', 'paid')->sum('actual_paid_amount'),
            ],
        ]);
    }

    public function storeStaff(Request $request): RedirectResponse
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'joining_date' => ['required', 'date_format:Y-m-d'],
            'current_salary' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'due_day' => ['required', 'integer', 'between:1,31'],
            'first_pay_period' => ['required', 'date_format:Y-m'],
        ]);

        $firstPayPeriod = Carbon::createFromFormat('Y-m', $validated['first_pay_period'])->startOfMonth();
        if (Carbon::parse($validated['joining_date'])->startOfMonth()->greaterThan($firstPayPeriod)) {
            throw ValidationException::withMessages([
                'first_pay_period' => 'The first payroll period cannot be before the staff member joins.',
            ]);
        }

        $staff = PayrollStaff::query()->create([
            ...$validated,
            'first_pay_period' => $firstPayPeriod->toDateString(),
            'generation_start_period' => $firstPayPeriod->toDateString(),
        ]);

        $this->payrollService->generatePeriodsThrough(now());

        return back()->with('success', "{$staff->name} was added to payroll.");
    }

    public function updateStaff(Request $request, PayrollStaff $payrollStaff): RedirectResponse
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'joining_date' => ['required', 'date_format:Y-m-d'],
            'current_salary' => ['required', 'numeric', 'min:0.01', 'max:9999999999.99'],
            'due_day' => ['required', 'integer', 'between:1,31'],
        ]);

        if (Carbon::parse($validated['joining_date'])->startOfMonth()->greaterThan($payrollStaff->first_pay_period)) {
            throw ValidationException::withMessages([
                'joining_date' => 'Joining date cannot be after the first payroll period.',
            ]);
        }

        $payrollStaff->update($validated);
        $this->payrollService->updateFuturePeriods($payrollStaff->refresh());

        return back()->with('success', 'Payroll staff details updated.');
    }

    public function updateStaffStatus(Request $request, PayrollStaff $payrollStaff): RedirectResponse
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $wasActive = $payrollStaff->is_active;
        $payrollStaff->update(['is_active' => $validated['is_active']]);

        if ($payrollStaff->is_active && ! $wasActive) {
            $payrollStaff->update([
                'generation_start_period' => now()->startOfMonth()->toDateString(),
            ]);
            $this->payrollService->generatePeriodsThrough(now());
        }

        return back()->with(
            'success',
            match (true) {
                $payrollStaff->is_active && ! $wasActive => 'Payroll staff member reactivated.',
                $payrollStaff->is_active => 'Payroll staff member is already active.',
                default => 'Payroll staff member deactivated; payroll history was retained.',
            },
        );
    }

    public function markPaid(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->payrollService->markPaid(
            $payrollPeriod,
            (int) $request->user('admin')->id,
            (float) $validated['amount'],
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Salary marked as paid.');
    }

    public function markDue(Request $request, PayrollPeriod $payrollPeriod): RedirectResponse
    {
        Gate::authorize(Permissions::MANAGE_PAYROLL);

        $validated = $request->validate([
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $this->payrollService->markDue(
            $payrollPeriod,
            (int) $request->user('admin')->id,
            $validated['notes'] ?? null,
        );

        return back()->with('success', 'Payment correction recorded; salary is now due.');
    }
}
