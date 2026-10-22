<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'social_account_id' => 'required|exists:social_accounts,id',
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ];
    }
}
