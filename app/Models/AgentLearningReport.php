<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentLearningReport extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'agent_name',
        'improvement_type',
        'description',
        'changes',
        'status',
        'applied_at',
        'created_at',
    ];

    protected $casts = [
        'changes' => 'array',
        'applied_at' => 'datetime',
    ];

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApplied($query)
    {
        return $query->where('status', 'applied');
    }

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }
}
