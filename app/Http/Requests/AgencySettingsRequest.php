<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgencySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && ($this->user()->isOwner() || $this->user()->isAdmin());
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:agencies,email,'.$this->user()->agency_id,
            'timezone' => 'nullable|string|max:50',
            'currency' => 'nullable|string|max:3',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'website' => 'nullable|url',
            'description' => 'nullable|string',
        ];
    }
}
