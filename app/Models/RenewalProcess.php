<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RenewalProcess extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_id', 'current_standard_stage_id', 'status', 'started_at', 'completed_at', 'new_expiry_date'
    ];

    protected $casts = [
        'started_at' => 'date',
        'completed_at' => 'date',
        'new_expiry_date' => 'date',
    ];

    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';

    public function certificate()
    {
        return $this->belongsTo(Certificate::class);
    }

    public function auditStages()
    {
        return $this->hasMany(AuditStage::class);
    }
}
