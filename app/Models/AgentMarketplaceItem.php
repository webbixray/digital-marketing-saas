<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AgentMarketplaceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'category_id',
        'tags',
        'icon',
        'screenshots',
        'demo_url',
        'pricing_type',
        'pricing_config',
        'features',
        'requirements',
        'install_count',
        'rating_avg',
        'rating_count',
        'is_featured',
        'is_approved',
        'status',
        'published_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'screenshots' => 'array',
        'pricing_config' => 'array',
        'features' => 'array',
        'requirements' => 'array',
        'install_count' => 'integer',
        'rating_avg' => 'float',
        'rating_count' => 'integer',
        'is_featured' => 'boolean',
        'is_approved' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AgentMarketplaceCategory::class, 'category_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(AgentMarketplaceReview::class, 'item_id');
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('status', 'approved');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopePopular($query)
    {
        return $query->orderByDesc('install_count')->orderByDesc('rating_avg');
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhereJsonContains('tags', $term);
        });
    }
}
