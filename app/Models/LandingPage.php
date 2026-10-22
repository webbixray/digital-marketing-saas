<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LandingPage extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'title',
        'headline',
        'content',
        'cta_text',
        'cta_url',
        'background_color',
        'text_color',
        'button_color',
        'button_text_color',
        'is_published',
        'published_at',
    ];

    // Counter/computed fields - never set via mass assignment
    protected $guarded = [
        'views_count',
        'clicks_count',
        'conversions_count',
        'conversion_rate',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'clicks_count' => 'integer',
        'conversions_count' => 'integer',
        'conversion_rate' => 'decimal:2',
        'published_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function recordClick(): void
    {
        $this->increment('clicks_count');
        if ($this->views_count > 0) {
            $this->conversion_rate = round(($this->clicks_count / $this->views_count) * 100, 2);
            $this->save();
        }
    }

    public function recordConversion(): void
    {
        $this->increment('conversions_count');
    }
}
