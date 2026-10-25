<?php

namespace App\Models;

class Payment extends BaseModel
{
    protected $fillable = [
        'subscription_id',
        'amount',
        'method',
        'gateway',
        'currency',
        'transaction_id',
        'gateway_payload',
        'status',
        'denial_reason',
        'transaction_type',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'gateway_payload' => 'array',
        ];
    }

    public function subscription(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Subscription::class)->withTrashed();
    }
}
