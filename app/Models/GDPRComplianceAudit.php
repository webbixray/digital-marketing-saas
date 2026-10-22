<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class GDPRComplianceAudit extends Model
{
    use HasFactory;

    protected $table = 'gdpr_compliance_audits';

    protected $fillable = [
        'agency_id',
        'user_id',
        'action',
        'category',
        'subject_type',
        'subject_id',
        'metadata',
        'ip_address',
        'user_agent',
        'severity',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
