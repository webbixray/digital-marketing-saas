<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'price',
        'interval',
        'users',
        'social_accounts',
        'posts_per_month',
        'campaigns',
        'clients',
        'ai_requests_per_month',
        'ai_generations_per_month',
        'landing_pages',
        'forms',
        'features',
        'description',
        'stripe_price_id',
        'is_active',
        'is_visible',
        'is_default',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'features' => 'array',
        'is_active' => 'boolean',
        'is_visible' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'plan_features');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->where('is_visible', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function isUnlimited(string $feature): bool
    {
        $unlimitedFeatures = [
            'users',
            'social_accounts',
            'posts_per_month',
            'campaigns',
            'clients',
            'ai_requests_per_month',
            'ai_generations_per_month',
            'landing_pages',
            'forms',
        ];

        if (! in_array($feature, $unlimitedFeatures)) {
            return false;
        }

        return $this->$feature === -1;
    }

    public function hasFeature(string $featureCode): bool
    {
        $features = $this->features ?? [];

        return in_array($featureCode, $features);
    }
}
