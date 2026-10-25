<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserType;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, LogsActivity, Notifiable;

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->isSuperAdmin()) {
                throw new \Exception('Cannot delete Super Admin.');
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'brand_id',
        'profile_image',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
        ];
    }

    // -- Relations --

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function customerProfile(): HasOne
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function brand(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function activityLogs(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    // -- Helpers --

    public function isSuperAdmin(): bool
    {
        return $this->type === UserType::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return $this->type === UserType::ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::CUSTOMER;
    }

    public function hasRole(string $roleName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (! $this->isAdmin()) {
            return false;
        }

        return $this->roles->contains('name', $roleName);
    }

    public function hasPermission(string $permissionName): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        if (! $this->isAdmin()) {
            return false;
        }

        // Direct query to check permission
        return \Illuminate\Support\Facades\DB::table('user_roles')
            ->join('role_permissions', 'user_roles.role_id', '=', 'role_permissions.role_id')
            ->join('permissions', 'role_permissions.permission_id', '=', 'permissions.id')
            ->where('user_roles.user_id', $this->id)
            ->where('permissions.name', $permissionName)
            ->exists();
    }

    public function isActive(): bool
    {
        return $this->hasAccess();
    }

    public function activeSubscription(): ?Subscription
    {
        return $this->activeSubscriptions()
            ->latest('end_date')
            ->first();
    }

    public function activeSubscriptions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->subscriptions()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', now()->toDateString());
    }

    public function hasAccess(): bool
    {
        return $this->activeSubscriptions()->exists();
    }

    protected function isMembershipActive(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn () => $this->isActive(),
        );
    }
}
