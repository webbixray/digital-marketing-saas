<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialCommercePost extends Model
{
    use HasAgency, HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'agency_id',
        'social_post_id',
        'product_id',
        'shop_url',
        'discount_code',
        'utm_params',
        'clicks',
        'conversions',
        'revenue',
        'created_at',
    ];

    protected $casts = [
        'utm_params' => 'array',
        'clicks' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    protected $dates = ['created_at'];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByPost($query, int $postId)
    {
        return $query->where('social_post_id', $postId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    public function getCtrAttribute(): float
    {
        if ($this->clicks === 0) {
            return 0;
        }

        return round(($this->conversions / $this->clicks) * 100, 2);
    }

    public function getAverageOrderValueAttribute(): float
    {
        if ($this->conversions === 0) {
            return 0;
        }

        return round($this->revenue / $this->conversions, 2);
    }
}
