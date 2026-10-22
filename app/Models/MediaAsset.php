<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MediaAsset extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
        'name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'width',
        'height',
        'alt_text',
        'folder',
        'folder_id',
        'tags',
        'is_public',
        'usage_count',
        'last_used_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_public' => 'boolean',
        'file_size' => 'integer',
        'usage_count' => 'integer',
        'last_used_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(MediaFolder::class);
    }

    public function scopeImages($query)
    {
        return $query->where('file_type', 'image');
    }

    public function scopeVideos($query)
    {
        return $query->where('file_type', 'video');
    }

    public function scopeDocuments($query)
    {
        return $query->where('file_type', 'document');
    }

    public function scopeInFolder($query, string $folder)
    {
        return $query->where('folder', $folder);
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = $this->file_size;
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        }

        return $bytes.' B';
    }

    public function getThumbnailUrlAttribute(): string
    {
        return asset('storage/'.$this->file_path);
    }
}
