<?php

namespace App\Models;

class Facility extends BaseModel
{
    protected $fillable = [
        'name',
        'description',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }
}
