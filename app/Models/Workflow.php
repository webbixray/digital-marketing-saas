<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use App\Enums\WorkflowStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workflow extends Model
{
    use HasFactory, SoftDeletes, HasAgency;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'trigger_type',
        'trigger_config',
        'actions',
        'conditions',
        'nodes',
        'connections',
        'last_executed_at',
        'webhook_url',
    ];

    // Internal/operational fields - never set via mass assignment
    protected $guarded = [
        'status',
        'execution_count',
        'error_message',
        'is_system',
        'webhook_secret',
    ];

    protected $casts = [
        'trigger_config' => 'array',
        'actions' => 'array',
        'conditions' => 'array',
        'nodes' => 'array',
        'connections' => 'array',
        'execution_count' => 'integer',
        'last_executed_at' => 'datetime',
        'is_system' => 'boolean',
    ];

    public function getStatusEnum(): WorkflowStatus
    {
        return new WorkflowStatus($this->status);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(WorkflowExecution::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(WorkflowVersion::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(WorkflowWebhookLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', WorkflowStatus::ACTIVE->value);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', WorkflowStatus::DRAFT->value);
    }

    public function scopeByType($query, string $triggerType)
    {
        return $query->where('trigger_type', $triggerType);
    }

    public function createVersion(?string $changeNotes = null, ?int $userId = null): WorkflowVersion
    {
        $lastVersion = $this->versions()->orderBy('version_number', 'desc')->first();
        $versionNumber = $lastVersion ? $lastVersion->version_number + 1 : 1;

        return $this->versions()->create([
            'version_number' => $versionNumber,
            'name' => $this->name,
            'trigger_type' => $this->trigger_type,
            'trigger_config' => $this->trigger_config,
            'actions' => $this->actions,
            'conditions' => $this->conditions,
            'change_notes' => $changeNotes,
            'created_by' => $userId,
        ]);
    }

    public function restoreFromVersion(WorkflowVersion $version): void
    {
        $this->update([
            'name' => $version->name,
            'trigger_type' => $version->trigger_type,
            'trigger_config' => $version->trigger_config,
            'actions' => $version->actions,
            'conditions' => $version->conditions,
        ]);
    }

    public function generateWebhookSecret(): void
    {
        $this->update([
            'webhook_secret' => bin2hex(random_bytes(32)),
        ]);
    }

    public function getWebhookUrlAttribute($value): ?string
    {
        if (! $this->webhook_secret) {
            return null;
        }

        return url("/api/workflows/{$this->id}/webhook/{$this->webhook_secret}");
    }

    public const TRIGGER_TYPES = [
        'new_post' => 'New Post Created',
        'post_published' => 'Post Published',
        'post_failed' => 'Post Failed',
        'comment_received' => 'Comment Received',
        'mention_received' => 'Mention Received',
        'message_received' => 'Direct Message Received',
        'schedule' => 'Scheduled Time',
        'cron' => 'Cron Schedule',
        'webhook' => 'Webhook',
    ];

    public const ACTION_TYPES = [
        'send_notification' => 'Send Notification',
        'auto_reply' => 'Auto Reply',
        'create_post' => 'Create Post',
        'schedule_post' => 'Schedule Post',
        'ai_generate' => 'AI Generate Content',
        'ai_reply' => 'AI Reply',
        'tag_client' => 'Tag Client',
        'update_campaign' => 'Update Campaign',
        'send_email' => 'Send Email',
        'webhook' => 'Webhook Call',
        'sleep' => 'Wait / Delay',
    ];
}
