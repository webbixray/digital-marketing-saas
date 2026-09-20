<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialListening extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'keyword',
        'platform',
        'is_active',
        'last_checked_at',
        'match_count',
        'sentiment_positive',
        'sentiment_negative',
        'sentiment_neutral',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_checked_at' => 'datetime',
        'match_count' => 'integer',
        'sentiment_positive' => 'integer',
        'sentiment_negative' => 'integer',
        'sentiment_neutral' => 'integer',
    ];

    /**
     * Get the agency that owns this keyword.
     */
    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    /**
     * Scope to only include active keywords.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Get total mentions (sum of all sentiments).
     */
    public function getTotalMentionsAttribute(): int
    {
        return $this->sentiment_positive + $this->sentiment_negative + $this->sentiment_neutral;
    }

    /**
     * Get sentiment score as percentage (-100 to +100).
     */
    public function getSentimentScoreAttribute(): float
    {
        $total = $this->total_mentions;
        if ($total === 0) {
            return 0;
        }

        return round((($this->sentiment_positive - $this->sentiment_negative) / $total) * 100, 2);
    }
}
