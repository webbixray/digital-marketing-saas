<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DataDeletionRequest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'reason', 'status', 'scheduled_at', 'completed_at'];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to only include pending requests whose scheduled deletion date has passed.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '<', now());
    }
}
