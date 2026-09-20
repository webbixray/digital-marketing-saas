<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'title',
        'phone',
        'last_active_at',
        'notes',
        'referral_code',
        'referred_by',
        'credits',
        'referral_count',
        'first_paid_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_active_at' => 'datetime',
        'is_active' => 'boolean',
        'is_approved' => 'boolean',
        'two_factor_enabled' => 'boolean',
        'credits' => 'decimal:2',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isOwner(): bool
    {
        return $this->role === 'owner';
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['owner', 'admin', 'manager']);
    }

    public function hasPermissionTo($permission): bool
    {
        // Owner and admin bypass all
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }

        return $this->checkPermissionAccess($permission);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        if ($this->isOwner() || $this->isAdmin()) {
            return true;
        }

        return $this->checkAnyPermissionAccess($permissions);
    }

    /**
     * Check permission using Spatie's trait logic.
     */
    private function checkPermissionAccess($permission): bool
    {
        // Use the Spatie trait's getAllPermissions to check
        $permissions = $this->getAllPermissions();
        $permissionClass = $this->getPermissionClass();

        if (is_string($permission)) {
            $permission = $permissions->firstWhere('name', $permission);
        } elseif (is_int($permission)) {
            $permission = $permissions->firstWhere('id', $permission);
        }

        return (bool) $permission;
    }

    /**
     * Check if any permission matches.
     */
    private function checkAnyPermissionAccess(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->checkPermissionAccess($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessAgency(Agency $agency): bool
    {
        if ($this->agency_id === $agency->id) {
            return true;
        }

        // Owner can access all agencies (if any)
        if ($this->isOwner()) {
            return true;
        }

        return false;
    }
}
