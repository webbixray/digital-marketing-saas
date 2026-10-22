<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailCampaignRecipient extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'email_campaign_id',
        'email',
        'name',
        'status',
        'sent_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'unsubscribed_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'email_campaign_id');
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_SENT = 'sent';

    public const STATUS_OPENED = 'opened';

    public const STATUS_CLICKED = 'clicked';

    public const STATUS_BOUNCED = 'bounced';

    public const STATUS_UNSUBSCRIBED = 'unsubscribed';
}
