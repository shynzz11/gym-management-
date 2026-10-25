<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_staff', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->date('joining_date');
            $table->decimal('current_salary', 12, 2);
            $table->unsignedTinyInteger('due_day');
            $table->date('first_pay_period');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'first_pay_period']);
        });

        Schema::create('payroll_periods', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_staff_id')->constrained('payroll_staff')->cascadeOnDelete();
            $table->date('period_start');
            $table->date('due_date');
            $table->decimal('salary_amount', 12, 2);
            $table->string('status')->default('due');
            $table->decimal('actual_paid_amount', 12, 2)->nullable();
            $table->dateTime('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payroll_staff_id', 'period_start']);
            $table->index(['period_start', 'status']);
        });

        Schema::create('payroll_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('payroll_period_id')->constrained('payroll_periods')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->decimal('amount', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['payroll_period_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_events');
        Schema::dropIfExists('payroll_periods');
        Schema::dropIfExists('payroll_staff');
    }
};
