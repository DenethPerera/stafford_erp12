<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Erav\EravProdOrder;
use App\Models\Erav\EravBranch;

class PurchaseOrder extends Model
{
    protected $table = 'purchase_orders';

    protected $fillable = [
        'po_number',
        'prodorder_id',
        'mbranchlist_id',
        'supplierno',
        'order_date',
        'delivery_date',
        'season',
        'base_total_qty',
        'wastage_percentage',
        'total_qty',
        'total_amount',
        'currency',
        'payment_terms',
        'shipping_method',
        'fob_code',
        'delivery_address',
        'customer_name',
        'status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'order_date'         => 'date',
        'delivery_date'      => 'date',
        'base_total_qty'     => 'integer',
        'wastage_percentage' => 'decimal:2',
        'total_qty'          => 'integer',
        'total_amount'       => 'decimal:2',
    ];

    public function prodOrder()
    {
        return $this->belongsTo(EravProdOrder::class, 'prodorder_id', 'prodorder_id');
    }

    public function branch()
    {
        return $this->belongsTo(EravBranch::class, 'mbranchlist_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierno', 'supplierno');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id');
    }
}
