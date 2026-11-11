<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Forwarder extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_name',
        'contact_person',
        'email',
        'phone',
        'cargo_security_certificate',
        'container_inspection_checklist',
        'consolidation_file',
    ];

    public function documentFiles()
    {
        return $this->hasMany(ForwarderDocument::class);
    }
}
