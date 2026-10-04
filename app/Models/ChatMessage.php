<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read User $user
 * @property-read ChatMessage $replyTo
 * @property-read Collection<ChatReaction> $reactions
 */
class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'user_id',
        'content',
        'type',
        'file_url',
        'file_name',
        'file_type',
        'file_size',
        'reply_to_id',
        'is_edited',
        'is_deleted',
    ];

    protected $casts = [
        'is_edited' => 'boolean',
        'is_deleted' => 'boolean',
        'file_size' => 'integer',
    ];

    public function channel()
    {
        return $this->belongsTo(ChatChannel::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyTo(): BelongsTo
    {
        return $this->belongsTo(ChatMessage::class, 'reply_to_id');
    }

    /**
     * @return HasMany<ChatReaction>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(ChatReaction::class, 'message_id');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeInChannel($query, int $channelId)
    {
        return $query->where('channel_id', $channelId);
    }

    public function getReplyToAttribute()
    {
        return $this->replyTo()->first();
    }

    public function getReactionsSummaryAttribute()
    {
        return $this->reactions()
            ->selectRaw('emoji, COUNT(*) as count')
            ->groupBy('emoji')
            ->pluck('count', 'emoji')
            ->toArray();
    }
}
