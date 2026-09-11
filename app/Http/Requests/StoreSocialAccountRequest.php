<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSocialAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,pinterest',
            'platform_account_id' => 'required|string|max:255',
            'platform_username' => 'required|string|max:255',
            'platform_display_name' => 'nullable|string|max:255',
            'platform_account_type' => 'nullable|in:personal,business,company,page',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'token_expires_at' => 'nullable|date',
            'metadata' => 'nullable|array',
        ];
    }
}
