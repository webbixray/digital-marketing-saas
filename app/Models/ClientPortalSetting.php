<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientPortalSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'agency_id',
        'brand_name',
        'brand_color',
        'logo_url',
        'custom_domain',
        'is_enabled',
        'show_analytics',
        'show_invoices',
        'allow_approvals',
        'show_team_activity',
        'welcome_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'show_analytics' => 'boolean',
        'show_invoices' => 'boolean',
        'allow_approvals' => 'boolean',
        'show_team_activity' => 'boolean',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_active', true);
    }

    public function getLogoUrlAttribute($value)
    {
        return $value ?? $this->agency->logo;
    }
}
