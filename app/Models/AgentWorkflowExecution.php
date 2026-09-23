<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentWorkflowExecution extends Model
{
    use HasFactory;

    public $timestamps = true;
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'execution_id',
        'workflow_name',
        'workflow_template_id',
        'agency_id',
        'user_id',
        'status',
        'input_data',
        'output_data',
        'steps_total',
        'steps_completed',
        'error_message',
        'started_at',
        'completed_at',
        'duration_ms',
    ];

    protected $casts = [
        'input_data' => 'array',
        'output_data' => 'array',
        'steps_total' => 'integer',
        'steps_completed' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'duration_ms' => 'integer',
    ];

    public function workflowTemplate(): BelongsTo
    {
        return $this->belongsTo(WorkflowTemplate::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
}
