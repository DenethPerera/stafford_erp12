<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFactoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:factories,name',
            'location' => 'nullable|string|max:255',
            'registration_no' => 'nullable|string|max:255|unique:factories,registration_no',
            'contact_person' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'status' => 'required|in:active,inactive',
        ];
    }
}
