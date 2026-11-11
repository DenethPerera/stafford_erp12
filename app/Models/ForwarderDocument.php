<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForwarderDocument extends Model
{
    protected $fillable = [
        'forwarder_id',
        'shipment_id',
        'cargo_security_certificate',
        'container_inspection_checklist',
        'consolidation_file',
    ];

    public function forwarder()
    {
        return $this->belongsTo(Forwarder::class);
    }
}
