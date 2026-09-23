<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingJob extends Model
{
    use HasFactory;

    protected $table = 'ai_training_jobs';

    protected $fillable = [
        'agency_id',
        'model_version_id',
        'dataset_id',
        'status',
        'progress',
        'hyperparameters',
        'metrics',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'hyperparameters' => 'array',
        'metrics' => 'array',
        'progress' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function modelVersion(): BelongsTo
    {
        return $this->belongsTo(AiModelVersion::class, 'model_version_id');
    }

    public function dataset(): BelongsTo
    {
        return $this->belongsTo(AiTrainingDataset::class, 'dataset_id');
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeRunning($query)
    {
        return $query->whereIn('status', ['queued', 'running']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}

