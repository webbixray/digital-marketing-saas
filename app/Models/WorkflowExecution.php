<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkflowExecution extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'workflow_id',
        'status',
        'trigger_data',
        'input_data',
        'output_data',
        'action_results',
        'error_message',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'input_data' => 'array',
        'output_data' => 'array',
        'action_results' => 'array',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WorkflowLog::class);
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';
}
