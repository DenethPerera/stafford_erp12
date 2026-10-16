<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCertificateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'factory_id' => 'required|exists:factories,id',
            'standard_id' => [
                'required',
                'exists:standards,id',
                Rule::unique('certificates')->where(fn ($query) => $query->where('factory_id', $this->factory_id))
            ],
            'certificate_number' => 'nullable|string|max:255|unique:certificates,certificate_number',
            'issue_date' => 'required|date',
            'expiry_date' => 'required|date|after:issue_date',
            'status' => 'required|in:valid,expiring_soon,expired,under_renewal',
            'document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
            'notes' => 'nullable|string',
        ];
    }
}
