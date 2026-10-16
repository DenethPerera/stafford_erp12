<?php

namespace App\Models\Erav;

use Illuminate\Database\Eloquent\Model;

class EravProductStyle extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodstyle_list';
    public $timestamps = false;
    protected $guarded = [];

     public function costs()
    {
        return $this->hasMany(EravProductStyleCost::class, 'prodstyle_list_id', 'id')->where('qrycancel', 0);
    }
}
