<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    protected $table = 'tbl_country';

    protected $primaryKey = 'idtbl_country';

    public $timestamps = false;

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currencyid', 'idtbl_currency');
    }

    // Uncomment if tbl_country is in the ERAV database:
    // protected $connection = 'erav';
}
