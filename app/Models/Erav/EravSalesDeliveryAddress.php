<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravSalesDeliveryAddress extends Model
{
    protected $connection = 'erav';

    protected $table = 'salesdeliaddrlist';

    protected $primaryKey = 'deliaddr_id';

    public $timestamps = false;

    protected $guarded = ['*'];
}
