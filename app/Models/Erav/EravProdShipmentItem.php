<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProdShipmentItem extends Model
{
    protected $connection = 'erav';

    protected $table = 'prodshipment_info';

    // The legacy preparation fields are assigned in the controller.
    public $timestamps = false;

    public function shipment()
    {
        return $this->belongsTo(EravProdShipment::class, 'prodshipment_list_id');
    }
}
