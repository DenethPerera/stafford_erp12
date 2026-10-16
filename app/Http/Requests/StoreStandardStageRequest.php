<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStandardStageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'standard_id' => ['required', 'exists:standards,id'],
            'name' => [
                'required', 
                'string', 
                'max:255',
                Rule::unique('standard_stages')->where(fn ($query) => $query->where('standard_id', $this->standard_id))
            ],
            'order_sequence' => [
                'required', 
                'integer', 
                'min:1',
                Rule::unique('standard_stages')->where(fn ($query) => $query->where('standard_id', $this->standard_id))
            ],
            'description' => ['nullable', 'string'],
        ];
    }
}
