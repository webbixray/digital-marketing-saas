<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutonomousOptimizationLog extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'campaign_id',
        'agency_id',
        'changes_applied',
        'pending_approval',
        'predicted_improvement',
        'actual_improvement',
        'metadata',
    ];

    protected $casts = [
        'changes_applied' => 'integer',
        'pending_approval' => 'integer',
        'predicted_improvement' => 'float',
        'actual_improvement' => 'float',
        'metadata' => 'array',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
