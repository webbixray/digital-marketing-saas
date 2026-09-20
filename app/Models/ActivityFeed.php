<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityFeed extends Model
{
    use HasAgency, HasFactory;



    protected $fillable = [
        'agency_id',
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function getIconAttribute(): string
    {
        return match ($this->action) {
            'post_created' => 'fa-pen-fancy',
            'post_published' => 'fa-share',
            'campaign_created' => 'fa-bullhorn',
            'campaign_sent' => 'fa-paper-plane',
            'client_added' => 'fa-user-plus',
            'workflow_executed' => 'fa-cogs',
            'member_joined' => 'fa-user-check',
            default => 'fa-circle',
        };
    }

    public function getDescriptionAttribute(): string
    {
        $userName = $this->user->name ?? 'Someone';

        return match ($this->action) {
            'post_created' => "{$userName} created a new post",
            'post_published' => "{$UserName} published a post",
            'campaign_created' => "{$userName} created a campaign",
            'campaign_sent' => "{$userName} sent a campaign",
            'client_added' => "{$userName} added a new client",
            'workflow_executed' => "{$userName} executed a workflow",
            'member_joined' => "{$userName} joined the team",
            default => "{$userName} performed an action",
        };
    }
}
