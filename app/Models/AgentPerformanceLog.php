<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentPerformanceLog extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'agent_name',
        'agent_category',
        'metric_type',
        'metric_value',
        'parameters_used',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'parameters_used' => 'array',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeByMetric($query, string $metricType)
    {
        return $query->where('metric_type', $metricType);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('recorded_at', '>=', now()->subDays($days));
    }
}
