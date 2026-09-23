<?php

namespace App\Services\Billing;

use App\Models\Agency;
use App\Models\Reseller;
use App\Models\WhiteLabelDomain;
use App\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\Log;

class WhiteLabelService
{
    public function verifyDomain(int $domainId): bool
    {
        $domain = WhiteLabelDomain::findOrFail($domainId);

        if ($domain->is_verified) {
            return true;
        }

        $txtRecords = dns_get_record($domain->domain, DNS_TXT);
        $expectedToken = $domain->verification_token;

        foreach ($txtRecords as $record) {
            if (isset($record['txt']) && str_contains($record['txt'], $expectedToken)) {
                $domain->markVerified();

                Log::info('Domain verified', [
                    'domain_id' => $domainId,
                    'domain' => $domain->domain,
                ]);

                return true;
            }
        }

        return false;
    }

    public function checkDomainVerification(string $domain): bool
    {
        $record = WhiteLabelDomain::where('domain', $domain)->first();

        return $record && $record->is_verified;
    }

    public function getWhiteLabelSettings(int $agencyId): ?WhiteLabelSetting
    {
        return WhiteLabelSetting::where('agency_id', $agencyId)->first();
    }

    public function updateWhiteLabelSettings(int $agencyId, array $data): WhiteLabelSetting
    {
        $setting = WhiteLabelSetting::updateOrCreate(
            ['agency_id' => $agencyId],
            array_intersect_key($data, array_flip([
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
                'custom_domain',
            ]))
        );

        Log::info('White-label settings updated', ['agency_id' => $agencyId]);

        return $setting;
    }

    public function isWhiteLabelActive(int $agencyId): bool
    {
        return WhiteLabelSetting::where('agency_id', $agencyId)
            ->where('enabled', true)
            ->exists();
    }

    public function getWhiteLabelDomain(int $agencyId): ?WhiteLabelDomain
    {
        $setting = WhiteLabelSetting::where('agency_id', $agencyId)
            ->where('enabled', true)
            ->whereNotNull('custom_domain')
            ->first();

        if (! $setting) {
            return null;
        }

        return WhiteLabelDomain::where('domain', $setting->custom_domain)
            ->where('is_verified', true)
            ->first();
    }

    public function getResellerForDomain(string $domain): ?Reseller
    {
        $whiteLabelDomain = WhiteLabelDomain::where('domain', $domain)
            ->where('is_verified', true)
            ->where('status', WhiteLabelDomain::STATUS_ACTIVE)
            ->first();

        if ($whiteLabelDomain) {
            return $whiteLabelDomain->reseller;
        }

        return Reseller::where('domain', $domain)
            ->where('is_active', true)
            ->first();
    }
}
