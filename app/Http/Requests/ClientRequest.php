<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email,'.($this->client?->id ?? 'NULL'),
            'phone' => 'nullable|string|max:50',
            'company' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'nullable|in:active,inactive,lead',
        ];
    }
}
