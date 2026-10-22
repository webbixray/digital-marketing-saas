<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PredictionModel extends Model
{
    use HasFactory;

    public const TYPE_CHURN = 'churn';

    public const TYPE_REVENUE = 'revenue';

    public const TYPE_ENGAGEMENT = 'engagement';

    public const TYPE_OPTIMAL_TIME = 'optimal_time';

    protected $fillable = [
        'agency_id',
        'name',
        'type',
        'model_version',
        'accuracy',
        'features',
        'hyperparameters',
        'is_active',
        'trained_at',
        'last_prediction_at',
    ];

    protected $casts = [
        'accuracy' => 'decimal:4',
        'features' => 'array',
        'hyperparameters' => 'array',
        'is_active' => 'boolean',
        'trained_at' => 'datetime',
        'last_prediction_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(PredictionResult::class);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLatest($query)
    {
        return $query->orderByDesc('model_version')->orderByDesc('trained_at');
    }
}
