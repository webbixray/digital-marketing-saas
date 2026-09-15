<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhiteLabelSetting extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'custom_domain',
        'brand_name',
        'brand_color',
        'logo_url',
        'favicon_url',
        'from_name',
        'from_email',
        'custom_css',
        'email_signature',
        'hide_powered_by',
        'enabled',
    ];

    protected $casts = [
        'hide_powered_by' => 'boolean',
        'enabled' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->brand_name ?? config('app.name');
    }

    public function getDisplayLogoAttribute(): string
    {
        return $this->logo_url ?? asset('images/default-logo.png');
    }

    public function getDisplayColorAttribute(): string
    {
        return $this->brand_color ?? '#007bff';
    }

    public function getDisplayFromEmailAttribute(): string
    {
        return $this->from_email ?? config('mail.from.address');
    }

    public function getDisplayFromNameAttribute(): string
    {
        return $this->from_name ?? config('mail.from.name');
    }
}
