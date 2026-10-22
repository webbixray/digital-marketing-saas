<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'version_number',
        'name',
        'trigger_type',
        'trigger_config',
        'actions',
        'conditions',
        'nodes',
        'connections',
        'change_notes',
        'created_by',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'actions' => 'array',
        'conditions' => 'array',
        'nodes' => 'array',
        'connections' => 'array',
        'version_number' => 'integer',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
