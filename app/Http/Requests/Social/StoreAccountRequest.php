<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,pinterest',
            'access_token' => 'required|string',
            'refresh_token' => 'nullable|string',
            'platform_account_id' => 'nullable|string',
            'platform_username' => 'nullable|string',
            'platform_display_name' => 'nullable|string',
        ];
    }
}
