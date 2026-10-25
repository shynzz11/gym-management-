<?php

namespace App\Console\Commands;

use App\Services\PayrollService;
use Illuminate\Console\Command;

class GeneratePayrollPeriods extends Command
{
    protected $signature = 'payroll:generate-periods';

    protected $description = 'Generate monthly payroll periods through the current month.';

    public function handle(PayrollService $payrollService): int
    {
        $payrollService->generatePeriodsThrough(now());
        $this->info('Monthly payroll periods are up to date.');

        return self::SUCCESS;
    }
}
