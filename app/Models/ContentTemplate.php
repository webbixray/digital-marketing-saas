<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentTemplate extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'platform',
        'type',
        'template_content',
        'variables',
        'hashtags',
        'mentions',
        'usage_count',
        'status',
    ];

    protected $casts = [
        'variables' => 'array',
        'hashtags' => 'array',
        'mentions' => 'array',
        'usage_count' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public const PLATFORMS = [
        'twitter' => 'Twitter',
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'linkedin' => 'LinkedIn',
        'tiktok' => 'TikTok',
        'pinterest' => 'Pinterest',
    ];

    public const TYPES = [
        'post' => 'Post',
        'story' => 'Story',
        'reel' => 'Reel',
        'pin' => 'Pin',
        'article' => 'Article',
    ];

    public function render(array $variables = []): string
    {
        $content = $this->template_content ?? '';
        foreach ($variables as $key => $value) {
            $content = str_replace("{{$key}}", $value, $content);
        }

        return $content;
    }
}
