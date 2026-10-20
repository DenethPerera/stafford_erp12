<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $table = 'mrmsupplier';
    protected $primaryKey = 'supplierno';
    public $timestamps = false;
    protected $guarded = [];


    /**
     * Purchase orders issued to this supplier.
     */
    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class, 'supplierno', 'supplierno');
    }
}
