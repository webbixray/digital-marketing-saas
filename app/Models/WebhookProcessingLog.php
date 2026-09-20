<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookProcessingLog extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $fillable = [
        'platform',
        'event_type',
        'webhook_id',
        'signature',
        'signature_valid',
        'payload',
        'status',
        'attempt',
        'max_attempts',
        'error_message',
        'response_time_ms',
        'processed_at',
        'failed_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'signature_valid' => 'boolean',
        'attempt' => 'integer',
        'max_attempts' => 'integer',
        'response_time_ms' => 'integer',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isDeadLetter(): bool
    {
        return $this->status === 'dead_letter';
    }

    public function canRetry(): bool
    {
        return $this->attempt < $this->max_attempts && ! $this->isCompleted() && ! $this->isDeadLetter();
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => 'processing']);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    public function markAsDeadLetter(string $errorMessage): void
    {
        $this->update([
            'status' => 'dead_letter',
            'error_message' => $errorMessage,
            'failed_at' => now(),
        ]);
    }

    public function incrementAttempt(): void
    {
        $this->increment('attempt');
    }
}
