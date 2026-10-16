<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StaffordQualification extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['po_number','season', 'status', 'buyer', 'style_no', 'product_name', 'factory_name', 'cert_no'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $guarded = ['id']; // Allow mass assignment for all except ID

    protected $casts = [
        'tests' => 'array',
        'additional_identifiers' => 'array',
    ];

    protected static function booted()
    {
        static::addGlobalScope('lab_isolation', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (auth()->check() && auth()->user()->isLab()) {
                $builder->where('lab_id', auth()->user()->lab_id);
            }
        });
    }

    public function lab()
    {
        return $this->belongsTo(Lab::class);
    }
}
