<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InboxTriage extends Model
{
    public $timestamps = false;

    protected $table = 'inbox_triage';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'inbox_message_id',
        'action',
        'sentiment',
        'category',
        'urgency_score',
        'ai_analysis',
        'suggested_reply',
        'triage_at',
    ];

    protected $casts = [
        'urgency_score' => 'decimal:2',
        'ai_analysis' => 'array',
        'suggested_reply' => 'array',
        'triage_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(InboxMessage::class, 'inbox_message_id');
    }

    public const CATEGORIES = [
        'feedback' => 'Feedback',
        'question' => 'Question',
        'complaint' => 'Complaint',
        'praise' => 'Praise',
        'spam' => 'Spam',
        'inquiry' => 'Inquiry',
    ];

    public const SENTIMENTS = [
        'positive' => 'Positive',
        'negative' => 'Negative',
        'neutral' => 'Neutral',
    ];

    public const ACTIONS = [
        'auto_reply' => 'Auto Reply',
        'auto_triage' => 'Auto Triage',
        'escalate' => 'Escalate',
        'mark_read' => 'Mark Read',
        'mark_important' => 'Mark Important',
        'ignore' => 'Ignore',
    ];
}
