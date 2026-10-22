<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowWebhookLog extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'workflow_id',
        'agency_id',
        'event_type',
        'payload',
        'ip_address',
        'status',
        'response',
        'processed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function scopeReceived($query)
    {
        return $query->where('status', 'received');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function markAsProcessed(?string $response = null): void
    {
        $this->update([
            'status' => 'processed',
            'response' => $response,
            'processed_at' => now(),
        ]);
    }
}
