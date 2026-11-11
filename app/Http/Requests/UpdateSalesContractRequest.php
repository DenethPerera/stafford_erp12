<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSalesContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        return [
            'porder_date' => ['required', 'date_format:Y-m-d'],
            'order_conf_refno' => ['required', 'string', 'max:25'],
            'ship_date' => ['nullable', 'date_format:Y-m-d'],
            'indc_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:ship_date'],
            'supplier_refno' => ['nullable', 'string', 'max:15'],
            'sales_rep_id' => ['nullable', 'string', 'max:15'],
            'item_dev_season' => ['nullable', 'string', 'max:50'],
            'ship_term' => ['nullable', 'string', 'max:50'],
            'fob_code' => ['nullable', 'string', 'max:20'],
            'ship_method' => ['nullable', 'string', 'max:20'],
            'customer_addr' => ['required', 'string', 'max:200'],
            'delivery_addr' => ['required', 'string', 'max:200'],
            'delivery_address_id' => ['nullable', 'integer'],
        ];
    }
}
