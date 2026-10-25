<?php

namespace App\Models;

class PlanAccessType extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'start_time',
        'end_time',
        'is_anytime',
        'is_active',
    ];

    protected $casts = [
        'is_anytime' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function plans(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Plan::class, 'access_type', 'slug');
    }
}
