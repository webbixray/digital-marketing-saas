<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BulkSchedule extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
        'filename',
        'total_rows',
        'successful_rows',
        'failed_rows',
        'processed_rows',
        'errors',
        'row_results',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'row_results' => 'array',
        'total_rows' => 'integer',
        'successful_rows' => 'integer',
        'failed_rows' => 'integer',
        'processed_rows' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows === 0) {
            return 0;
        }

        return round(($this->successful_rows / $this->total_rows) * 100, 2);
    }
}
