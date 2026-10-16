<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Certificate extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['certificate_number', 'status', 'issue_date', 'expiry_date'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'factory_id', 'standard_id', 'certificate_number', 'issue_date', 'expiry_date', 'status', 'document_path', 'notes'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
    ];

    public const STATUS_VALID = 'valid';
    public const STATUS_EXPIRING_SOON = 'expiring_soon';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_UNDER_RENEWAL = 'under_renewal';

    public function factory()
    {
        return $this->belongsTo(Factory::class);
    }

    public function standard()
    {
        return $this->belongsTo(Standard::class);
    }

    public function renewalProcesses()
    {
        return $this->hasMany(RenewalProcess::class);
    }
}
