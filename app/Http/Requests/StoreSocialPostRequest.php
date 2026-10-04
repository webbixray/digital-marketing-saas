<?php

namespace App\Http\Requests;

use App\Models\SocialAccount;
use Illuminate\Foundation\Http\FormRequest;

class StoreSocialPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'social_account_id' => 'required|exists:social_accounts,id',
            'platform' => 'sometimes|string|in:'.implode(',', array_keys(SocialAccount::SUPPORTED_PLATFORMS)),
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'social_account_id.required' => 'The social account id field is required.',
            'social_account_id.exists' => 'The selected social account is invalid.',
            'content.required' => 'The content field is required.',
            'content.max' => 'The content may not be greater than 5000 characters.',
        ];
    }
}
