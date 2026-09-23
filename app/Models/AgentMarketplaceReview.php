<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentMarketplaceReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'item_id',
        'user_id',
        'agency_id',
        'rating',
        'title',
        'body',
        'is_verified_purchase',
        'helpful_count',
        'status',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'helpful_count' => 'integer',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(AgentMarketplaceItem::class, 'item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByItem($query, int $itemId)
    {
        return $query->where('item_id', $itemId);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeHelpful($query)
    {
        return $query->orderByDesc('helpful_count');
    }
}
