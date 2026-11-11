<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProdShipment extends Model
{
    protected $connection = 'erav';

    protected $table = 'prodshipment_list';

    // The legacy table uses prep_time instead of Laravel's timestamps.
    public $timestamps = false;

    public function items()
    {
        return $this->hasMany(EravProdShipmentItem::class, 'prodshipment_list_id');
    }
}
