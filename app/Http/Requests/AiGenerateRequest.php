<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AiGenerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'prompt' => 'required|string|max:2000',
            'content_type' => 'required|in:post,caption,hashtag,headline,email,ad_copy',
            'model' => 'nullable|string|max:100',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:50|max:8000',
        ];
    }
}
