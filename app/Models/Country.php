<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'tbl_country';

    public $timestamps = false;

    // Uncomment if tbl_country is in the ERAV database:
    // protected $connection = 'erav';
}
