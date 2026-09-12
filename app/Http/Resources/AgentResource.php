<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AgentResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->display_name,
            'description' => $this->description,
            'category' => $this->category,
            'status' => $this->status,
            'last_execution' => $this->last_execution?->toISO8601String(),
            'total_executions' => $this->total_executions,
            'success_rate' => $this->success_rate,
            'created_at' => $this->created_at?->toISO8601String(),
            'updated_at' => $this->updated_at?->toISO8601String(),
        ];
    }
}
