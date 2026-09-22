<?php

namespace App\Models;

use App\Enums\PostStatus;
use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPost extends Model
{
    use HasAgency, HasFactory, SoftDeletes;



    protected $fillable = [
        'agency_id',
        'social_account_id',
        'platform',
        'content',
        'media',
        'links',
        'hashtags',
        'mentions',
        'tags',
        'status',
        'scheduled_at',
        'published_at',
        'failed_at',
        'error_message',
        'retry_count',
        'platform_response',
        'external_post_id',
        'views_count',
        'likes_count',
        'comments_count',
        'shares_count',
        'clicks_count',
        'engagement_rate',
        'metrics',
        'quality_score',
        'is_pinned',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'client_id',
        'calendar_slot',
    ];

    protected $casts = [
        'media' => 'array',
        'links' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'tags' => 'array',
        'metrics' => 'array',
        'approved_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'published_at' => 'datetime',
        'failed_at' => 'datetime',
        'retry_count' => 'integer',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'comments_count' => 'integer',
        'shares_count' => 'integer',
        'clicks_count' => 'integer',
        'engagement_rate' => 'float',
        'quality_score' => 'integer',
        'is_pinned' => 'boolean',
        'calendar_slot' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function campaigns(): BelongsToMany
    {
        return $this->belongsToMany(Campaign::class, 'campaign_post');
    }

    public function insights(): HasMany
    {
        return $this->hasMany(ContentInsight::class);
    }

    public function isScheduled(): bool
    {
        return $this->status === PostStatus::SCHEDULED->value && $this->scheduled_at;
    }

    public function isPublished(): bool
    {
        return $this->status === PostStatus::PUBLISHED->value;
    }

    public function isFailed(): bool
    {
        return $this->status === PostStatus::FAILED->value;
    }

    public function scopePublished($query)
    {
        return $query->where('status', PostStatus::PUBLISHED->value);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', PostStatus::SCHEDULED->value)
            ->whereNotNull('scheduled_at');
    }

    public function scopeDrafts($query)
    {
        return $query->where('status', PostStatus::DRAFT->value);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('published_at', today());
    }

    public function generateHashtagsAttribute(): array
    {
        return $this->hashtags ?? [];
    }

    public function calculateEngagementRate(): ?float
    {
        $impressions = $this->metrics['impressions'] ?? 0;
        $engagement = $this->likes_count + $this->comments_count + $this->shares_count;

        if ($impressions === 0) {
            return null;
        }

        return round(($engagement / $impressions) * 100, 2);
    }
}
