<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiModelVersion extends Model
{
    use HasFactory;

    protected $table = 'ai_model_versions';

    protected $fillable = [
        'agency_id',
        'name',
        'base_model',
        'version',
        'status',
        'training_data_hash',
        'metrics',
        'file_path',
        'is_active',
        'trained_at',
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_active' => 'boolean',
        'trained_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function trainingJobs(): HasMany
    {
        return $this->hasMany(AiTrainingJob::class, 'model_version_id');
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }

    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}

