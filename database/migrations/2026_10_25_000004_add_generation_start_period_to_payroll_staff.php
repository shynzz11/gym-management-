<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payroll_staff', 'generation_start_period')) {
            Schema::table('payroll_staff', function (Blueprint $table): void {
                $table->date('generation_start_period')->nullable();
            });
        }

        DB::table('payroll_staff')
            ->whereNull('generation_start_period')
            ->update(['generation_start_period' => DB::raw('first_pay_period')]);
    }

    public function down(): void
    {
        Schema::table('payroll_staff', function (Blueprint $table): void {
            $table->dropColumn('generation_start_period');
        });
    }
};
