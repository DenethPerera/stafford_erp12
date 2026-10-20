<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $table = 'purchase_order_items';

    protected $fillable = [
        'purchase_order_id',
        'pack_no',
        'upc_refno',
        'color_code',
        'color_desc',
        'size_desc',
        'base_quantity',
        'wastage_percentage',
        'wastage_qty',
        'quantity',
        'unit_price',
        'total_amount',
    ];

    protected $casts = [
        'base_quantity'      => 'integer',
        'wastage_percentage' => 'decimal:2',
        'wastage_qty'        => 'integer',
        'quantity'           => 'integer',
        'unit_price'         => 'decimal:2',
        'total_amount'       => 'decimal:2',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }
}
