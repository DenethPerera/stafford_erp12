<?php

namespace App\Http\Controllers;

use App\Models\Factory;
use App\Models\Certificate;
use App\Models\RenewalProcess;
use App\Models\StaffordQualification;
use App\Models\TestReportType;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // -------------------------------------------------------------
        // LAB ROLE DASHBOARD LOGIC (Keep Dashboard minimal / blank for now)
        // -------------------------------------------------------------
        if ($user && $user->isLab()) {
            $lab = $user->lab;
            return view('dashboard', compact('lab'));
        }

        // -------------------------------------------------------------
        // SUPER ADMIN DASHBOARD LOGIC
        // -------------------------------------------------------------
        $totalFactories = Factory::count();
        $totalCertificates = Certificate::count();

        $today = now()->startOfDay();
        $warningDate = now()->addDays(90)->startOfDay();

        $expiringSoon = Certificate::with(['factory', 'standard'])
            ->where('status', '!=', Certificate::STATUS_UNDER_RENEWAL)
            ->where('expiry_date', '>', $today)
            ->where('expiry_date', '<=', $warningDate)
            ->get()
            ->each(function (Certificate $certificate) {
                $certificate->setAttribute('status', Certificate::STATUS_EXPIRING_SOON);
            });

        $expired = Certificate::with(['factory', 'standard'])
            ->where('status', '!=', Certificate::STATUS_UNDER_RENEWAL)
            ->where('expiry_date', '<=', $today)
            ->get()
            ->each(function (Certificate $certificate) {
                $certificate->setAttribute('status', Certificate::STATUS_EXPIRED);
            });
        
        $activeRenewalsQuery = RenewalProcess::with(['certificate.factory', 'certificate.standard.standardStages', 'auditStages.standardStage'])
            ->whereNotIn('status', [
                RenewalProcess::STATUS_COMPLETED,
                RenewalProcess::STATUS_REJECTED
            ]);

        $renewalsInProgress = $activeRenewalsQuery->count();
        
        $activeRenewals = $activeRenewalsQuery->get()->map(function ($renewal) {
            $standard = $renewal->certificate->standard ?? null;
            $totalStages = $standard ? $standard->standardStages->count() : 0;
            
            // Fallback for mock standards without stages
            if ($totalStages === 0) {
                $totalStages = 4;
            }
            
            $completedStages = $renewal->auditStages->where('result', \App\Models\AuditStage::RESULT_PASSED)->count();
            $progress = $totalStages > 0 ? (int)(($completedStages / $totalStages) * 100) : 0;
            $progress = min(100, $progress);
            
            $completedStageIds = $renewal->auditStages->where('result', \App\Models\AuditStage::RESULT_PASSED)->pluck('standard_stage_id')->toArray();
            
            $currentStageName = 'Pending';
            if ($standard) {
                $currentStandardStage = $standard->standardStages->sortBy('order_sequence')->first(function($stage) use ($completedStageIds) {
                    return !in_array($stage->id, $completedStageIds);
                });
                
                if ($currentStandardStage) {
                    $currentStageName = $currentStandardStage->name;
                } elseif ($progress === 100) {
                    $currentStageName = 'Completed';
                }
            }

            // We pick up to 4 stages to display in the progress bar
            $stages = $standard ? $standard->standardStages->sortBy('order_sequence')->take(4)->pluck('name')->toArray() : ['Applied', 'Pre-Audit', 'Final', 'Cert'];
            if (empty($stages)) {
                $stages = ['Applied', 'Pre-Audit', 'Final', 'Cert'];
            }
            // Pad array to 4 elements if needed
            $stages = array_pad($stages, 4, '');

            $certNumber = $renewal->certificate->certificate_number ?? ('Cert-'.$renewal->certificate_id);
            $standardName = $renewal->certificate->standard->name ?? '';
            $displayId = $standardName ? "{$certNumber} ({$standardName})" : $certNumber;

            return (object)[
                'id' => $displayId,
                'certificateId' => $renewal->certificate->id ?? null,
                'factory' => $renewal->certificate->factory->name ?? 'Unknown Factory',
                'currentStage' => $currentStageName,
                'progress' => $progress,
                'color' => 'from-brand-teal to-brand-deepteal',
                'stageLabels' => collect($stages)->map(fn($s) => strlen($s) > 8 ? substr($s,0,7).'..' : $s)->toArray()
            ];
        });

        // Alert feed: combined expiring soon and expired
        $alerts = $expiringSoon->concat($expired)->sortBy('expiry_date');

        return view('dashboard', compact(
            'totalFactories',
            'totalCertificates',
            'expiringSoon',
            'expired',
            'renewalsInProgress',
            'activeRenewals',
            'alerts'
        ));
    }
}
