<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Reseller extends Model
{
    use HasFactory;

    public const COMMISSION_TYPE_PERCENTAGE = 'percentage';

    public const COMMISSION_TYPE_FIXED = 'fixed';

    public const BILLING_TYPE_MONTHLY = 'monthly';

    public const BILLING_TYPE_REVENUE_SHARE = 'revenue_share';

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'domain',
        'logo_url',
        'primary_color',
        'is_active',
        'commission_rate',
        'commission_type',
        'billing_type',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'commission_rate' => 'decimal:2',
        'settings' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(ResellerCommission::class);
    }

    public function domains(): HasMany
    {
        return $this->hasMany(WhiteLabelDomain::class);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public static function generateSlug(string $name): string
    {
        $slug = Str::slug($name);
        $count = static::where('slug', 'LIKE', $slug . '%')->count();

        return $count > 0 ? "{$slug}-" . ($count + 1) : $slug;
    }
}
