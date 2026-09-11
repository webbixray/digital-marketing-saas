<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbTestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'ab_test_id',
        'variant',
        'event',
        'ip_address',
        'user_agent',
    ];

    public function abTest(): BelongsTo
    {
        return $this->belongsTo(AbTest::class);
    }
}
