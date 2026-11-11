<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesContractPackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:2000'],
            'items.*' => ['required', 'array:id,master_packqty,case_packqty'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.master_packqty' => ['required', 'integer', 'min:0', 'max:2147483647'],
            'items.*.case_packqty' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ];
    }
}
