<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollPeriod extends BaseModel
{
    protected $fillable = [
        'payroll_staff_id',
        'period_start',
        'due_date',
        'salary_amount',
        'status',
        'actual_paid_amount',
        'paid_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'due_date' => 'date',
            'salary_amount' => 'decimal:2',
            'actual_paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(PayrollStaff::class, 'payroll_staff_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(PayrollEvent::class);
    }
}
