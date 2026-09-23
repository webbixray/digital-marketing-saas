<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class UsageQuota extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'agency_id',
        'metric',
        'limit',
        'used',
        'period',
        'reset_at',
    ];

    protected $casts = [
        'limit' => 'integer',
        'used' => 'integer',
        'reset_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByMetric(Builder $query, string $metric): Builder
    {
        return $query->where('metric', $metric);
    }

    public function scopeExceeded(Builder $query): Builder
    {
        return $query->whereColumn('used', '>', 'limit');
    }
}
