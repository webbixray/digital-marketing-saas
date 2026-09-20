<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Report extends Model
{
    use HasAgency, HasFactory;

    protected $with = ['user', 'agency'];

    protected $fillable = [
        'agency_id', 'user_id', 'name', 'type', 'filters', 'columns',
        'format', 'schedule', 'status', 'file_path', 'last_generated_at',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'last_generated_at' => 'datetime',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function getDownloadUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/'.$this->file_path) : null;
    }
}
