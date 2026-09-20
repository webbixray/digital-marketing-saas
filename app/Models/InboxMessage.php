<?php

namespace App\Models;

use App\Enums\InboxMessageStatus;
use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class InboxMessage extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $with = ['socialAccount', 'triage'];

    protected $fillable = [
        'agency_id',
        'social_account_id',
        'platform',
        'message_id',
        'message_type',
        'author_id',
        'author_name',
        'author_username',
        'author_avatar',
        'content',
        'parent_id',
        'post_id',
        'status',
        'metadata',
        'received_at',
        'read_at',
        'replied_at',
        'replied_content',
        'replied_by_user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'received_at' => 'datetime',
        'read_at' => 'datetime',
        'replied_at' => 'datetime',
        'replied_by_user_id' => 'integer',
    ];

    public function getStatusEnum()
    {
        return new InboxMessageStatus($this->status);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function triage(): HasOne
    {
        return $this->hasOne(InboxTriage::class);
    }

    public function replier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'replied_by_user_id');
    }

    public function scopeUnread($query)
    {
        return $query->where('status', InboxMessageStatus::UNREAD->value);
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('message_type', $type);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('received_at', today());
    }

    public static function markRead(InboxMessage $message): void
    {
        $message->update([
            'status' => InboxMessageStatus::READ->value,
            'read_at' => now(),
        ]);
    }

    public static function markReplied(InboxMessage $message, string $content, ?User $user = null): void
    {
        $message->update([
            'status' => InboxMessageStatus::REPLIED->value,
            'replied_content' => $content,
            'replied_at' => now(),
            'replied_by_user_id' => $user?->id,
        ]);
    }

    public const MESSAGE_TYPES = [
        'comment' => 'Comment',
        'mention' => 'Mention',
        'direct_message' => 'Direct Message',
        'reply' => 'Reply',
    ];

    public const PLATFORMS = [
        'facebook' => 'Facebook',
        'instagram' => 'Instagram',
        'twitter' => 'Twitter / X',
        'linkedin' => 'LinkedIn',
    ];
}
