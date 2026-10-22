<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'overview' => $this->when(isset($this->overview), $this->overview),
            'social' => $this->when(isset($this->social), $this->social),
            'email' => $this->when(isset($this->email), $this->email),
            'financial' => $this->when(isset($this->financial), $this->financial),
            'ai' => $this->when(isset($this->ai), $this->ai),
        ];
    }
}
