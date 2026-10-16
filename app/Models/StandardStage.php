<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StandardStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'standard_id',
        'name',
        'order_sequence',
        'description',
    ];

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }
}
