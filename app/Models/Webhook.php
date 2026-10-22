<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Webhook extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'url',
        'secret',
        'events',
        'content_type',
        'is_active',
        'total_calls',
        'failed_calls',
        'last_triggered_at',
    ];

    protected $casts = [
        'events' => 'array',
        'is_active' => 'boolean',
        'total_calls' => 'integer',
        'failed_calls' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public static array $availableEvents = [
        'post.published' => 'Post Published',
        'post.failed' => 'Post Failed',
        'campaign.created' => 'Campaign Created',
        'campaign.completed' => 'Campaign Completed',
        'invoice.paid' => 'Invoice Paid',
        'client.created' => 'Client Created',
        'ai.generated' => 'AI Content Generated',
    ];
}
