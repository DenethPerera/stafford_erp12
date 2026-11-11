<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProdOrderInfo extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodorder_info';
    protected $primaryKey = 'pack_no';
    public $timestamps = false;
    protected $guarded = [];

    public function prodOrder()
    {
        return $this->belongsTo(EravProdOrder::class, 'prodorder_id', 'prodorder_id');
    }
      public function purchaseOrderItem()
    {
        return $this->belongsTo(EravPurchaseOrderItem::class, 'porder_pdf_info_id');
    }
}
