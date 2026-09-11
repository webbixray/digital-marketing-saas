<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'platform',
        'platform_account_id',
        'platform_username',
        'platform_display_name',
        'platform_account_type',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'token_type',
        'scope',
        'scope_str',
        'metadata',
        'is_active',
        'is_verified',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'token_expires_at' => 'datetime',
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
    ];

    protected $appends = ['platform_name', 'connection_name'];

    public function getPlatformNameAttribute(): string
    {
        return ucfirst($this->platform);
    }

    public function getConnectionNameAttribute(): string
    {
        return "social-{$this->platform}-{$this->id}";
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function inboxMessages(): HasMany
    {
        return $this->hasMany(InboxMessage::class);
    }

    public function isExpired(): bool
    {
        if (! $this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    public function hasExpiredToken(): bool
    {
        return $this->isExpired();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public const SUPPORTED_PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'twitter' => 'Twitter / X',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'pinterest' => 'Pinterest',
    ];
}
