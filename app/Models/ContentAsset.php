<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentAsset extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'type',
        'content',
        'media_url',
        'file_path',
        'file_size',
        'mime_type',
        'tags',
        'metadata',
        'usage_count',
        'is_public',
        'status',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'usage_count' => 'integer',
        'is_public' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public const ASSET_TYPES = [
        'text' => 'Text',
        'image' => 'Image',
        'video' => 'Video',
        'audio' => 'Audio',
        'document' => 'Document',
        'link' => 'Link',
    ];
}
