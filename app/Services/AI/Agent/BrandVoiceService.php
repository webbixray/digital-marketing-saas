<?php

namespace App\Services\AI\Agent;

use App\Models\Agency;
use App\Models\BrandVoiceProfile;

class BrandVoiceService
{
    /**
     * Get the brand voice profile for an agency.
     *
     * @return array<string, mixed>
     */
    public function getProfile(Agency $agency): array
    {
        $profile = BrandVoiceProfile::where('agency_id', $agency->id)
            ->where('is_default', true)
            ->first();

        if (! $profile) {
            $profile = BrandVoiceProfile::where('agency_id', $agency->id)->first();
        }

        if (! $profile) {
            return $this->getDefaultProfile();
        }

        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'description' => $profile->description,
            'tone' => $profile->tone ?? null,
            'vocabulary' => $profile->vocabulary ?? null,
            'style_rules' => $profile->style_rules ?? [],
            'examples' => $profile->examples ?? [],
            'platform' => $profile->platform ?? null,
        ];
    }

    /**
     * Get the default brand voice profile.
     *
     * @return array<string, mixed>
     */
    private function getDefaultProfile(): array
    {
        return [
            'id' => null,
            'name' => 'Default',
            'description' => 'Professional and friendly tone',
            'tone' => 'professional',
            'vocabulary' => [],
            'style_rules' => [],
            'examples' => [],
            'platform' => null,
        ];
    }
}
