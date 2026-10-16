<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProductStyle extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodstyle_list';
    public $timestamps = false;
    protected $guarded = [];
}
