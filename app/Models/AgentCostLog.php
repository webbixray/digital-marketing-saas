<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentCostLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'agent_name',
        'task_type',
        'cost_usd',
        'tokens_used',
        'executed_at',
    ];

    protected $casts = [
        'cost_usd' => 'decimal:6',
        'tokens_used' => 'integer',
        'executed_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByAgent(Builder $query, string $agentName): Builder
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeByTaskType(Builder $query, string $taskType): Builder
    {
        return $query->where('task_type', $taskType);
    }

    public function scopeInDateRange(Builder $query, string $start, string $end): Builder
    {
        return $query->whereBetween('executed_at', [$start, $end]);
    }

    public function scopeCurrentMonth(Builder $query): Builder
    {
        return $query->whereYear('executed_at', now()->year)
            ->whereMonth('executed_at', now()->month);
    }
}
