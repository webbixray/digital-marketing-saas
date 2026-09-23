<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PredictionResult extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    protected $fillable = [
        'agency_id',
        'prediction_model_id',
        'target_type',
        'target_id',
        'predicted_value',
        'confidence',
        'features_used',
        'actual_value',
        'error',
    ];

    protected $casts = [
        'predicted_value' => 'decimal:4',
        'confidence' => 'decimal:4',
        'features_used' => 'array',
        'actual_value' => 'decimal:4',
        'error' => 'decimal:4',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function predictionModel(): BelongsTo
    {
        return $this->belongsTo(PredictionModel::class);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeAccurate($query, float $threshold = 0.8)
    {
        return $query->where('confidence', '>=', $threshold);
    }
}
