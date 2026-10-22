<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create_posts');
    }

    public function rules(): array
    {
        return [
            'social_account_id' => 'required|exists:social_accounts,id',
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'media.*' => 'file|mimes:jpg,jpeg,png,gif,mp4|max:10240',
            'scheduled_at' => 'nullable|date|after:now',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ];
    }
}
