<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes, HasAgency;

    protected $fillable = [
        'agency_id',
        'name',
        'email',
        'phone',
        'company',
        'industry',
        'notes',
        'status',
        'last_contact_at',
        'posts_count',
        'campaigns_count',
    ];

    protected $casts = [
        'last_contact_at' => 'datetime',
        'posts_count' => 'integer',
        'campaigns_count' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(ClientSubscription::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLead($query)
    {
        return $query->where('status', 'lead');
    }
}
