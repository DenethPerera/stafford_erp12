<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShipmentDocument extends Model
{
    protected $fillable = [
        'mbranchlist_id',
        'shipment_id',
        'document_type',
        'original_name',
        'file_path',
    ];
}
