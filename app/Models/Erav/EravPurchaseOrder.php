<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravPurchaseOrder extends Model
{
    protected $connection = 'erav';
    protected $table = 'porder_pdf_list';
    public $timestamps = false;
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(EravPurchaseOrderItem::class, 'porder_pdf_list_id');
    }

    public function prodOrders()
    {
        return $this->hasMany(EravProdOrder::class, 'porder_pdf_list_id');
    }
}
