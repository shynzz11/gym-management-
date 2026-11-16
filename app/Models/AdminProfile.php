<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminProfile extends BaseModel
{
    /** @use HasFactory<\Database\Factories\AdminProfileFactory> */
    protected $fillable = [
        'user_id',
        'employee_id',
        'department',
        'designation',
        'phone',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
