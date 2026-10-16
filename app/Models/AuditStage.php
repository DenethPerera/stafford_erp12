<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditStage extends Model
{
    use HasFactory;

    protected $fillable = [
        'renewal_process_id', 'standard_stage_id', 'scheduled_date', 'completed_date', 'result', 'auditor_name', 'remarks'
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'completed_date' => 'date',
    ];

    public const RESULT_PENDING = 'pending';
    public const RESULT_PASSED = 'passed';
    public const RESULT_FAILED = 'failed';
    public const RESULT_NEEDS_CORRECTIVE_ACTION = 'needs_corrective_action';

    public function renewalProcess()
    {
        return $this->belongsTo(RenewalProcess::class);
    }

    public function standardStage()
    {
        return $this->belongsTo(StandardStage::class);
    }
}
