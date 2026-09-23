<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class CreditTransaction extends Model
{
    public $timestamps = true;

    protected $fillable = [
        'agency_id',
        'user_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'integer',
        'balance_after' => 'integer',
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

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('type', 'purchase');
    }

    public function scopeUsage(Builder $query): Builder
    {
        return $query->where('type', 'usage');
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopePositive(Builder $query): Builder
    {
        return $query->where('amount', '>', 0);
    }

    public function scopeNegative(Builder $query): Builder
    {
        return $query->where('amount', '<', 0);
    }
}
