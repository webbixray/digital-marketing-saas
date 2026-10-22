<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiAuditLog extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'user_id',
        'action',
        'model_used',
        'input_hash',
        'output_hash',
        'bias_score',
        'toxicity_score',
        'compliance_status',
        'flagged_reason',
        'metadata',
    ];

    protected $casts = [
        'bias_score' => 'float',
        'toxicity_score' => 'float',
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

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeFlagged($query)
    {
        return $query->where('compliance_status', 'fail')
            ->orWhere('compliance_status', 'warn');
    }

    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    public function scopeByComplianceStatus($query, string $status)
    {
        return $query->where('compliance_status', $status);
    }

    public function scopeForToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function getIsFlaggedAttribute(): bool
    {
        return in_array($this->compliance_status, ['fail', 'warn']);
    }

    public function getComplianceStatusColorAttribute(): string
    {
        return match ($this->compliance_status) {
            'pass' => 'success',
            'fail' => 'danger',
            'warn' => 'warning',
            default => 'secondary',
        };
    }
}
