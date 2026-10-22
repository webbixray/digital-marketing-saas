<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    use HasFactory;

    protected $table = 'search_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'agency_id',
        'query',
        'type',
        'results_count',
        'clicked_result',
        'created_at',
    ];

    protected $casts = [
        'results_count' => 'integer',
        'clicked_result' => 'array',
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

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeForType($query, $type)
    {
        return $query->where('type', $type);
    }
}
