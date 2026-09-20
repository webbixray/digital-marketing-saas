<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Comment extends Model
{
    use HasAgency, HasFactory;



    protected $fillable = [
        'agency_id',
        'user_id',
        'commentable_type',
        'commentable_id',
        'body',
        'mentions',
        'parent_id',
    ];

    protected $casts = [
        'mentions' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function commentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeForCommentable($query, Model $commentable)
    {
        return $query->where('commentable_type', get_class($commentable))
            ->where('commentable_id', $commentable->id);
    }
}
