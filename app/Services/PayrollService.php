<?php

namespace App\Services;

use App\Models\PayrollPeriod;
use App\Models\PayrollStaff;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function generatePeriodsForMonth(DateTimeInterface $month): void
    {
        $periodStart = Carbon::instance($month)->startOfMonth();

        PayrollStaff::query()
            ->where('is_active', true)
            ->whereDate('generation_start_period', '<=', $periodStart->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($staffMembers) use ($periodStart): void {
                foreach ($staffMembers as $staff) {
                    $this->ensurePeriodExists($staff, $periodStart);
                }
            });
    }

    public function generatePeriodsThrough(DateTimeInterface $month): void
    {
        $targetMonth = Carbon::instance($month)->startOfMonth();

        PayrollStaff::query()
            ->where('is_active', true)
            ->whereDate('generation_start_period', '<=', $targetMonth->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($staffMembers) use ($targetMonth): void {
                foreach ($staffMembers as $staff) {
                    $period = $staff->generation_start_period->copy()->startOfMonth();

                    while ($period->lessThanOrEqualTo($targetMonth)) {
                        $this->ensurePeriodExists($staff, $period);

                        $period = $period->addMonthNoOverflow();
                    }
                }
            });
    }

    public function updateFuturePeriods(PayrollStaff $staff): void
    {
        $nextMonth = now()->startOfMonth()->addMonthNoOverflow();

        $staff->periods()
            ->where('status', 'due')
            ->whereDate('period_start', '>=', $nextMonth->toDateString())
            ->get()
            ->each(function (PayrollPeriod $period) use ($staff): void {
                $period->update([
                    'salary_amount' => $staff->current_salary,
                    'due_date' => $this->dueDateForMonth($period->period_start, $staff->due_day)->toDateString(),
                ]);
            });
    }

    public function markPaid(PayrollPeriod $period, int $adminUserId, float $amount, ?string $notes): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $adminUserId, $amount, $notes): PayrollPeriod {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status === 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'This salary period is already marked as paid.',
                ]);
            }

            $occurredAt = now();
            $period->update([
                'status' => 'paid',
                'actual_paid_amount' => $amount,
                'paid_at' => $occurredAt,
                'notes' => $notes,
            ]);

            $period->events()->create([
                'admin_user_id' => $adminUserId,
                'action' => 'paid',
                'amount' => $amount,
                'notes' => $notes,
                'occurred_at' => $occurredAt,
            ]);

            return $period->refresh();
        });
    }

    public function markDue(PayrollPeriod $period, int $adminUserId, ?string $notes): PayrollPeriod
    {
        return DB::transaction(function () use ($period, $adminUserId, $notes): PayrollPeriod {
            $period = PayrollPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if ($period->status !== 'paid') {
                throw ValidationException::withMessages([
                    'status' => 'Only paid salary periods can be reverted to due.',
                ]);
            }

            $period->events()->create([
                'admin_user_id' => $adminUserId,
                'action' => 'reverted',
                'amount' => $period->actual_paid_amount,
                'notes' => $notes,
                'occurred_at' => now(),
            ]);

            $period->update([
                'status' => 'due',
                'actual_paid_amount' => null,
                'paid_at' => null,
                'notes' => null,
            ]);

            return $period->refresh();
        });
    }

    private function dueDateForMonth(DateTimeInterface $month, int $dueDay): Carbon
    {
        $date = Carbon::instance($month)->startOfMonth();

        return $date->day(min($dueDay, $date->daysInMonth));
    }

    private function ensurePeriodExists(PayrollStaff $staff, DateTimeInterface $periodStart): void
    {
        $periodStart = Carbon::instance($periodStart)->startOfMonth();
        $periodStartDate = $periodStart->toDateString();
        $existingPeriod = PayrollPeriod::query()
            ->where('payroll_staff_id', $staff->id)
            ->whereDate('period_start', $periodStartDate)
            ->exists();

        if ($existingPeriod) {
            return;
        }

        try {
            PayrollPeriod::query()->create([
                'payroll_staff_id' => $staff->id,
                'period_start' => $periodStartDate,
                'due_date' => $this->dueDateForMonth($periodStart, $staff->due_day)->toDateString(),
                'salary_amount' => $staff->current_salary,
                'status' => 'due',
            ]);
        } catch (UniqueConstraintViolationException $exception) {
            $periodWasCreatedConcurrently = PayrollPeriod::query()
                ->where('payroll_staff_id', $staff->id)
                ->whereDate('period_start', $periodStartDate)
                ->exists();

            if (! $periodWasCreatedConcurrently) {
                throw $exception;
            }
        }
    }
}
