<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookLog extends Model
{
    use HasFactory;

    public $timestamps = true;

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'webhook_id',
        'event',
        'status_code',
        'payload',
        'response',
        'error_message',
        'response_time_ms',
        'is_success',
    ];

    protected $casts = [
        'payload' => 'array',
        'status_code' => 'integer',
        'response_time_ms' => 'integer',
        'is_success' => 'boolean',
    ];

    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }
}
