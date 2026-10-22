<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SocialAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'platform' => $this->platform,
            'platform_account_id' => $this->platform_account_id,
            'platform_username' => $this->platform_username,
            'platform_display_name' => $this->platform_display_name,
            'platform_account_type' => $this->platform_account_type,
            'is_active' => $this->is_active,
            'is_verified' => $this->is_verified,
            'token_expires_at' => $this->token_expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
