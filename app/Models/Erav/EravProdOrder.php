<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProdOrder extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodorder_list';
    protected $primaryKey = 'prodorder_id';
    public $timestamps = false;
    protected $guarded = [];

    public function purchaseOrder()
    {
        return $this->belongsTo(EravPurchaseOrder::class, 'porder_pdf_list_id');
    }

    public function branch()
    {
        return $this->belongsTo(EravBranch::class, 'mbranchlist_id');
    }

    public function items()
    {
        return $this->hasMany(EravProdOrderInfo::class, 'prodorder_id', 'prodorder_id');
    }
}
