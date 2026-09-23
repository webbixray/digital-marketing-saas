<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTrainingDataset extends Model
{
    use HasFactory;

    protected $table = 'ai_training_datasets';

    protected $fillable = [
        'agency_id',
        'name',
        'description',
        'file_path',
        'file_hash',
        'row_count',
        'column_count',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'row_count' => 'integer',
        'column_count' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeReady($query)
    {
        return $query->where('status', 'ready');
    }
}

