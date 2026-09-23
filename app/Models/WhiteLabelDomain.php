<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelDomain extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_PENDING = 'pending';

    public const SSL_STATUS_ACTIVE = 'active';

    public const SSL_STATUS_PENDING = 'pending';

    public const SSL_STATUS_FAILED = 'failed';

    protected $fillable = [
        'reseller_id',
        'domain',
        'is_verified',
        'verification_token',
        'ssl_status',
        'status',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
    ];

    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    public function scopeByReseller($query, int $resellerId)
    {
        return $query->where('reseller_id', $resellerId);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function generateVerificationToken(): string
    {
        $token = 'dms-verify=' . md5($this->domain . config('app.key'));
        $this->update(['verification_token' => $token]);

        return $token;
    }

    public function markVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'status' => self::STATUS_ACTIVE,
        ]);
    }
}
