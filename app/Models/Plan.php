<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;

class Plan extends BaseModel
{
    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'description',
        'facilities',
        'is_active',
        'is_default',
        'access_type',
        'start_time',
        'end_time',
        'is_offer',
        'offer_price',
        'offer_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'facilities' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'is_offer' => 'boolean',
            'offer_price' => 'decimal:2',
            'offer_ends_at' => 'datetime',
        ];
    }

    /**
     * Scope: Active plans only.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Currently valid offers.
     */
    public function scopeOffers($query)
    {
        return $query->where('is_offer', true)
            ->where('offer_ends_at', '>', now());
    }

    /**
     * Get the plan's current effective price.
     */
    protected function currentPrice(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->is_offer && $this->offer_ends_at?->isFuture()) {
                    return $this->offer_price;
                }

                return $this->price;
            }
        );
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Whether this plan has any associated subscriptions (blocks deletion).
     */
    protected function hasSubscribers(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->subscriptions()->exists(),
        );
    }
}
