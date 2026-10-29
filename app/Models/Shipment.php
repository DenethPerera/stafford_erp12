<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
       protected $fillable = [
        'country_of_origin',
        'buyer_id',
        'shipment_id',
        'shipment_id_mode',
        'purchase_order_id',
        'shipment_type',
        'factory_ids',
    ];

    protected $casts = [
        'purchase_order_id' => 'array',
        'factory_ids' => 'array',
    ];
}
