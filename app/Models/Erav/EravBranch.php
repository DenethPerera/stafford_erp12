<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravBranch extends Model
{
    protected $connection = 'erav';
    protected $table = 'mbranchlist';
    public $timestamps = false;
    protected $guarded = [];

    public function prodOrders()
    {
        return $this->hasMany(EravProdOrder::class, 'mbranchlist_id');
    }
}
