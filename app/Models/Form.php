<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Form extends Model
{
    use HasFactory, SoftDeletes, HasAgency;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'fields',
        'success_message',
        'redirect_url',
        'background_color',
        'text_color',
        'is_published',
        'submissions_count',
        'published_at',
    ];

    protected $casts = [
        'fields' => 'array',
        'is_published' => 'boolean',
        'submissions_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeDraft($query)
    {
        return $query->where('is_published', false);
    }

    public function incrementSubmissions(): void
    {
        $this->increment('submissions_count');
    }
}
