<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimalPostingTime extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'platform',
        'day_of_week',
        'hour',
        'engagement_score',
        'sample_size',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'hour' => 'integer',
        'engagement_score' => 'float',
        'sample_size' => 'integer',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeBest($query)
    {
        return $query->orderByDesc('engagement_score');
    }

    public function scopeForDay($query, int $day)
    {
        return $query->where('day_of_week', $day);
    }

    public function getDayNameAttribute(): string
    {
        return ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$this->day_of_week];
    }

    public function getTimeSlotAttribute(): string
    {
        return sprintf('%02d:00', $this->hour);
    }
}
