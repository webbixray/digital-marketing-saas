<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:general,product_launch,seasonal,awareness,consideration,conversion,retention',
            'description' => 'nullable|string',
            'objective' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ];
    }
}
