<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAgentMarketplaceItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'slug' => 'required|string|max:220|unique:agent_marketplace_items,slug',
            'description' => 'required|string|max:5000',
            'category_id' => 'required|integer|exists:agent_marketplace_categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'icon' => 'nullable|string|max:100',
            'screenshots' => 'nullable|array',
            'screenshots.*' => 'string|max:500',
            'demo_url' => 'nullable|url|max:500',
            'pricing_type' => 'required|in:free,paid,pricing_tiers',
            'pricing_config' => 'nullable|array',
            'features' => 'nullable|array',
            'features.*' => 'string|max:200',
            'requirements' => 'nullable|array',
            'requirements.*' => 'string|max:200',
        ];
    }
}
