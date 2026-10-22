<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->method() === 'POST';

        $rules = [
            'name' => 'required|string|max:255',
            'type' => $isCreate ? 'required|in:newsletter,promotional,transactional' : 'nullable|in:newsletter,promotional,transactional',
            'subject' => $isCreate ? 'required|string|max:255' : 'nullable|string|max:255',
            'from_name' => 'nullable|string|max:100',
            'from_email' => 'nullable|email|max:255',
            'reply_to' => 'nullable|email|max:255',
            'content' => 'nullable|string',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'recipients' => 'nullable|array',
            'recipients.*.email' => 'required|email',
            'recipients.*.name' => 'nullable|string|max:100',
            'scheduled_at' => 'nullable|date|after:now',
        ];

        return $rules;
    }
}
