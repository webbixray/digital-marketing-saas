<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiContentLog extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'provider',
        'model',
        'action',
        'content_type',
        'prompt',
        'response',
        'total_tokens',
        'prompt_tokens',
        'completion_tokens',
        'cost_usd',
        'status',
        'error_message',
    ];

    protected $casts = [
        'total_tokens' => 'integer',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'cost_usd' => 'decimal:4',
        'prompt' => 'array',
        'response' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeByProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForMonth($query, int $year, int $month)
    {
        return $query->whereYear('created_at', $year)
            ->whereMonth('created_at', $month);
    }

    public function getTotalCostAttribute(): float
    {
        return (float) $this->cost_usd;
    }

    /**
     * Get the response as a string (handles array cast).
     */
    public function getResponseTextAttribute(): string
    {
        if (is_array($this->response)) {
            return implode("\n", $this->response);
        }

        return (string) $this->response;
    }

    /**
     * Get the prompt as a string (handles array cast).
     */
    public function getPromptTextAttribute(): string
    {
        if (is_array($this->prompt)) {
            return implode("\n", $this->prompt);
        }

        return (string) $this->prompt;
    }

    /**
     * Get a color badge class for content type.
     */
    public function getContentTypeColorAttribute(): string
    {
        return match ($this->content_type) {
            'post' => 'primary',
            'caption' => 'info',
            'hashtag' => 'success',
            'headline' => 'warning',
            'email' => 'danger',
            'ad_copy' => 'secondary',
            'landing_page' => 'primary',
            'blog' => 'info',
            default => 'secondary',
        };
    }

    /**
     * Get a human-readable content type label.
     */
    public function getContentTypeLabelAttribute(): string
    {
        return match ($this->content_type) {
            'post' => 'Social Post',
            'caption' => 'Caption',
            'hashtag' => 'Hashtags',
            'headline' => 'Headline',
            'email' => 'Email Copy',
            'ad_copy' => 'Ad Copy',
            'landing_page' => 'Landing Page',
            'blog' => 'Blog Outline',
            default => ucfirst($this->content_type ?? 'post'),
        };
    }
}
