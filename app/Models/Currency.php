<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Currency extends Model
{
    protected $table = 'tbl_currency';

    protected $primaryKey = 'idtbl_currency';

    public $timestamps = false;
}
