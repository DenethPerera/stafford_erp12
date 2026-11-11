<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesContractPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:2000'],
            'items.*' => ['required', 'array:id,revised_unit_price'],
            'items.*.id' => ['required', 'integer', 'distinct'],
            'items.*.revised_unit_price' => ['required', 'numeric', 'min:0', 'max:999999.9999', 'decimal:0,4'],
        ];
    }
}
