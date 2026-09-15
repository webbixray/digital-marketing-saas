<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use App\Enums\CampaignStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes, HasAgency;

    protected $fillable = [
        'agency_id',
        'client_id',
        'name',
        'slug',
        'type',
        'description',
        'objective',
        'target_audience',
        'start_date',
        'end_date',
        'tags',
        'cover_image',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'tags' => 'array',
        'posts_count' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'clicks_count' => 'integer',
        'estimated_roi' => 'decimal:2',
        'engagement_rate' => 'integer',
    ];

    public function getStatusEnum()
    {
        return new CampaignStatus($this->status);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(SocialPost::class, 'campaign_post');
    }

    public function scopeActive($query)
    {
        return $query->where('status', CampaignStatus::ACTIVE->value);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', CampaignStatus::DRAFT->value);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public const CAMPAIGN_TYPES = [
        'general' => 'General',
        'product_launch' => 'Product Launch',
        'seasonal' => 'Seasonal',
        'awareness' => 'Awareness',
        'consideration' => 'Consideration',
        'conversion' => 'Conversion',
        'retention' => 'Retention',
    ];
}
