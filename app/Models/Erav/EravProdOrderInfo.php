<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProdOrderInfo extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodorder_info';
    public $timestamps = false;
    protected $guarded = [];

    public function prodOrder()
    {
        return $this->belongsTo(EravProdOrder::class, 'prodorder_id', 'prodorder_id');
    }
}
