<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class MeteredUsage extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'agency_id',
        'metric',
        'quantity',
        'unit_price',
        'total_price',
        'recorded_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:8',
        'total_price' => 'decimal:8',
        'recorded_at' => 'datetime',
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

    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereBetween('recorded_at', [now()->startOfMonth(), now()->endOfMonth()]);
    }

    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->whereNull('recorded_at');
    }
}
