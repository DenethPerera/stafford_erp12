<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStandardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('standard') ? $this->route('standard')->id : null;
        return [
            'name' => 'required|string|max:255|unique:standards,name,' . $id,
            'code' => 'required|string|max:255|unique:standards,code,' . $id,
            'description' => 'nullable|string',
            'validity_period_months' => 'required|integer|min:1',
        ];
    }
}
