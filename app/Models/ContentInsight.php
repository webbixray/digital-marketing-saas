<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentInsight extends Model
{
    protected $fillable = [
        'social_post_id',
        'insight_date',
        'impressions',
        'reach',
        'engagement',
        'clicks',
        'saves',
        'shares',
        'engagement_rate',
        'demographics',
        'top_locations',
        'top_devices',
    ];

    protected $casts = [
        'insight_date' => 'date',
        'impressions' => 'integer',
        'reach' => 'integer',
        'engagement' => 'integer',
        'clicks' => 'integer',
        'saves' => 'integer',
        'shares' => 'integer',
        'engagement_rate' => 'decimal:2',
        'demographics' => 'array',
        'top_locations' => 'array',
        'top_devices' => 'array',
    ];

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('insight_date', [$startDate, $endDate]);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('insight_date', today());
    }
}
