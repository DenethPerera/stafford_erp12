<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravSalesCustomer extends Model
{
    protected $connection = 'erav';

    protected $table = 'salescustomerlist';

    protected $primaryKey = 'customer_id';

    public $timestamps = false;

    protected $guarded = ['*'];
}
