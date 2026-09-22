<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnalyticsEvent extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'event_type',
        'event_data',
        'platform',
        'social_post_id',
        'user_id',
        'metadata',
    ];

    protected $casts = [
        'event_data' => 'array',
        'metadata' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }
}
