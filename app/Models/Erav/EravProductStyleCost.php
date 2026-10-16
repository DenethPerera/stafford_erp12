<?php

namespace App\Models\Erav;

use App\Models\Supplier;
use Illuminate\Database\Eloquent\Model;

class EravProductStyleCost extends Model
{
    protected $connection = 'erav';
    protected $table = 'prodstyle_cost';
    public $timestamps = false;
    protected $guarded = [];

    public function productStyle()
    {
        return $this->belongsTo(EravProductStyle::class, 'prodstyle_list_id', 'id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplierno', 'supplierno');
    }
}
