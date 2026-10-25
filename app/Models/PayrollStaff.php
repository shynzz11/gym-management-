<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollStaff extends BaseModel
{
    protected $table = 'payroll_staff';

    protected $fillable = [
        'name',
        'joining_date',
        'current_salary',
        'due_day',
        'first_pay_period',
        'generation_start_period',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'joining_date' => 'date',
            'current_salary' => 'decimal:2',
            'first_pay_period' => 'date',
            'generation_start_period' => 'date',
            'is_active' => 'boolean',
        ];
    }

    public function periods(): HasMany
    {
        return $this->hasMany(PayrollPeriod::class);
    }
}
