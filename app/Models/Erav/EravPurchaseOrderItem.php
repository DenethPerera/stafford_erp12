<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravPurchaseOrderItem extends Model
{
    protected $connection = 'erav';
    protected $table = 'porder_pdf_info';
    public $timestamps = false;
    protected $guarded = [];

    public function purchaseOrder()
    {
        return $this->belongsTo(EravPurchaseOrder::class, 'porder_pdf_list_id');
    }
}
