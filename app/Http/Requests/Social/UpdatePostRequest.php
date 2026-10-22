<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ];
    }
}
