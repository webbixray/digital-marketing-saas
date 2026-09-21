<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentGenome extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'platform',
        'optimal_length',
        'best_hashtags',
        'best_times',
        'content_themes',
        'tone_patterns',
        'media_types',
        'cta_patterns',
        'engagement_prediction',
        'accuracy_score',
        'last_updated_at',
        'data_points_count',
        'genome_data',
    ];

    protected $casts = [
        'best_hashtags' => 'array',
        'best_times' => 'array',
        'content_themes' => 'array',
        'tone_patterns' => 'array',
        'media_types' => 'array',
        'cta_patterns' => 'array',
        'engagement_prediction' => 'array',
        'genome_data' => 'array',
        'last_updated_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeHighAccuracy($query, float $minScore = 0.75)
    {
        return $query->where('accuracy_score', '>=', $minScore);
    }

    public function scopeRecentlyUpdated($query, int $days = 30)
    {
        return $query->where('last_updated_at', '>=', now()->subDays($days));
    }
}
