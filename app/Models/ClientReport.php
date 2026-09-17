<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ClientReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'client_id',
        'title',
        'slug',
        'report_data',
        'period',
        'start_date',
        'end_date',
        'status',
        'access_token',
        'published_at',
    ];

    protected $casts = [
        'report_data' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            $report->access_token = Str::random(32);
            $report->slug = Str::slug($report->title).'-'.Str::random(6);
        });
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function getPublicUrlAttribute(): string
    {
        return route('public.client-report', [
            'slug' => $this->slug,
            'token' => $this->access_token,
        ]);
    }

    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }
}
