<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailCampaign extends Model
{
    use HasFactory, SoftDeletes, HasAgency;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'type',
        'status',
        'content',
        'subject',
        'from_name',
        'from_email',
        'reply_to',
        'tags',
        'recipients_count',
        'sent_count',
        'opened_count',
        'clicked_count',
        'bounced_count',
        'unsubscribed_count',
        'open_rate',
        'click_rate',
        'bounce_rate',
        'scheduled_at',
        'sent_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'recipients_count' => 'integer',
        'sent_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'bounced_count' => 'integer',
        'unsubscribed_count' => 'integer',
        'open_rate' => 'decimal:2',
        'click_rate' => 'decimal:2',
        'bounce_rate' => 'decimal:2',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']);
    }

    public function isSendable(): bool
    {
        return in_array($this->status, ['draft', 'scheduled']) && $this->recipients_count > 0;
    }

    /**
     * Get the unsubscribe hash for a recipient.
     * Uses HMAC to prevent guessing.
     */
    public static function getUnsubscribeHash(int $recipientId, int $campaignId): string
    {
        return hash_hmac('sha256', $recipientId.':'.$campaignId, config('app.key'));
    }

    /**
     * Verify an unsubscribe hash is valid.
     */
    public static function verifyUnsubscribeHash(int $recipientId, int $campaignId, string $hash): bool
    {
        return hash_equals(self::getUnsubscribeHash($recipientId, $campaignId), $hash);
    }

    public const STATUSES = [
        'draft' => 'Draft',
        'scheduled' => 'Scheduled',
        'sending' => 'Sending',
        'sent' => 'Sent',
        'failed' => 'Failed',
    ];

    public const TYPES = [
        'newsletter' => 'Newsletter',
        'promotional' => 'Promotional',
        'transactional' => 'Transactional',
    ];
}
