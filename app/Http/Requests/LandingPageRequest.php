<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandingPageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'title' => 'nullable|string|max:255',
            'headline' => 'nullable|string|max:500',
            'content' => 'nullable|string',
            'cta_text' => 'nullable|string|max:100',
            'cta_url' => 'nullable|url',
            'background_color' => 'nullable|string|max:7',
            'text_color' => 'nullable|string|max:7',
            'button_color' => 'nullable|string|max:7',
        ];
    }
}
