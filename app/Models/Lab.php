<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lab extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'lab_code',
        'email',
        'contact_person',
        'phone',
        'address_line1',
        'address_line2',
        'is_active',
    ];

    public function getLabcodeAttribute()
    {
        return $this->attributes['lab_code'] ?? null;
    }

    public function setLabcodeAttribute($value)
    {
        $this->attributes['lab_code'] = $value;
    }
}
