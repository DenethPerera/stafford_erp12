<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStandardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:standards,name',
            'code' => 'required|string|max:255|unique:standards,code',
            'description' => 'nullable|string',
            'validity_period_months' => 'required|integer|min:1',
        ];
    }
}
