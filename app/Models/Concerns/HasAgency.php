<?php

namespace App\Models\Concerns;

trait HasAgency
{
    /**
     * Scope a query to only include records for a specific agency.
     */
    public function scopeForAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    /**
     * Scope a query to only include records for the authenticated user's agency.
     */
    public function scopeForCurrentAgency($query)
    {
        return $query->where('agency_id', auth()->user()->agency_id);
    }
}
