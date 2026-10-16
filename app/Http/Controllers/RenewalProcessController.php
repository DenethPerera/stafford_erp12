<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\RenewalProcess;
use Illuminate\Http\Request;

class RenewalProcessController extends Controller
{
    public function store(Request $request, Certificate $certificate)
    {
        if (!in_array($certificate->status, [Certificate::STATUS_EXPIRING_SOON, Certificate::STATUS_EXPIRED])) {
            return back()->with('error', 'Certificate is not eligible for renewal.');
        }

        if ($certificate->renewalProcesses()->where('status', RenewalProcess::STATUS_IN_PROGRESS)->exists()) {
            return back()->with('error', 'A renewal process is already active for this certificate.');
        }

        $standardStages = $certificate->standard->standardStages()->orderBy('order_sequence')->get();
        if ($standardStages->isEmpty()) {
            return back()->with('error', 'No standard stages defined for this certification. Please configure standard stages first.');
        }

        $renewal = $certificate->renewalProcesses()->create([
            'current_standard_stage_id' => $standardStages->first()->id,
            'status' => RenewalProcess::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);

        foreach ($standardStages as $stage) {
            $renewal->auditStages()->create([
                'standard_stage_id' => $stage->id,
            ]);
        }

        $certificate->update(['status' => Certificate::STATUS_UNDER_RENEWAL]);

        return redirect()->route('certificates.show', $certificate)->with('success', 'Renewal process started.');
    }

    public function updateStage(Request $request, RenewalProcess $renewal)
    {
        $request->validate([
            'audit_stage_id' => 'required|exists:audit_stages,id',
            'result' => 'required|in:passed,failed,needs_corrective_action',
            'auditor_name' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $stage = $renewal->auditStages()->with('standardStage')->findOrFail($request->audit_stage_id);

        if ($renewal->status !== RenewalProcess::STATUS_IN_PROGRESS) {
            return back()->with('error', 'This renewal process is no longer active.');
        }

        if ((int) $renewal->current_standard_stage_id !== (int) $stage->standard_stage_id) {
            return back()->with('error', 'Please complete the current stage before moving to the next one.');
        }

        if ($stage->result === 'passed') {
            return back()->with('error', 'This stage has already been completed.');
        }
        
        $stage->update([
            'result' => $request->result,
            'auditor_name' => $request->auditor_name,
            'remarks' => $request->remarks,
            'completed_date' => now(),
        ]);

        if ($request->result === 'failed') {
            $renewal->certificate->update([
                'status' => $this->resolveCertificateStatus($renewal->certificate),
            ]);

            $renewal->update(['status' => RenewalProcess::STATUS_REJECTED, 'completed_at' => now()]);
            return back()->with('error', 'Stage failed. Renewal process rejected.');
        }

        if ($request->result === 'passed') {
            // Find the next stage in the sequence
            $nextStandardStage = $renewal->certificate->standard->standardStages()
                ->where('order_sequence', '>', $stage->standardStage->order_sequence)
                ->orderBy('order_sequence')
                ->first();

            if ($nextStandardStage) {
                $renewal->update(['current_standard_stage_id' => $nextStandardStage->id]);
            } else {
                // Complete renewal
                $months = $renewal->certificate->standard->validity_period_months;
                $newExpiry = now()->addMonths($months);

                $renewal->update([
                    'current_standard_stage_id' => null,
                    'status' => RenewalProcess::STATUS_COMPLETED,
                    'completed_at' => now(),
                    'new_expiry_date' => $newExpiry,
                ]);

                $renewal->certificate->update([
                    'expiry_date' => $newExpiry,
                    'status' => Certificate::STATUS_VALID,
                ]);

                return back()->with('success', 'Renewal process completed successfully. Certificate renewed.');
            }
        }

        return back()->with('success', 'Stage updated.');
    }

    private function resolveCertificateStatus(Certificate $certificate): string
    {
        $today = now()->startOfDay();
        $expiryDate = $certificate->expiry_date->copy()->startOfDay();

        if ($expiryDate->lte($today)) {
            return Certificate::STATUS_EXPIRED;
        }

        if ($expiryDate->lte($today->copy()->addDays(90))) {
            return Certificate::STATUS_EXPIRING_SOON;
        }

        return Certificate::STATUS_VALID;
    }
}
