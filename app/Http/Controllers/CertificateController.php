<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Factory;
use App\Models\RenewalProcess;
use App\Models\Standard;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateRequest;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function create(Request $request)
    {
        $factory = Factory::findOrFail($request->factory_id);
        $standards = Standard::all();
        return view('certificates.create', compact('factory', 'standards'));
    }

    public function store(StoreCertificateRequest $request)
    {
        $data = $request->validated();
        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('certificates', 'public');
        }
        
        $certificate = Certificate::create($data);
        return redirect()->route('factories.show', $certificate->factory_id)->with('success', 'Certificate added successfully.');
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['factory', 'standard', 'renewalProcesses.auditStages']);

        // Auto-sync: for any in-progress renewal, add audit_stage rows for
        // StandardStages that were added AFTER the renewal was started.
        $allStandardStages = $certificate->standard->standardStages()->orderBy('order_sequence')->get();

        foreach ($certificate->renewalProcesses as $renewal) {
            if ($renewal->status !== RenewalProcess::STATUS_IN_PROGRESS) {
                continue;
            }

            $existingStageIds = $renewal->auditStages->pluck('standard_stage_id')->all();

            foreach ($allStandardStages as $standardStage) {
                if (!in_array($standardStage->id, $existingStageIds)) {
                    $renewal->auditStages()->create([
                        'standard_stage_id' => $standardStage->id,
                    ]);
                }
            }
        }

        // Reload to include any newly created audit stages
        $certificate->load(['factory', 'standard', 'renewalProcesses.auditStages.standardStage']);

        return view('certificates.show', compact('certificate'));
    }

    public function edit(Certificate $certificate)
    {
        $standards = Standard::all();
        return view('certificates.edit', compact('certificate', 'standards'));
    }

    public function update(UpdateCertificateRequest $request, Certificate $certificate)
    {
        $data = $request->validated();
        if ($request->hasFile('document')) {
            $data['document_path'] = $request->file('document')->store('certificates', 'public');
        }

        $certificate->update($data);
        return redirect()->route('certificates.show', $certificate)->with('success', 'Certificate updated successfully.');
    }

    public function destroy(Certificate $certificate)
    {
        $factoryId = $certificate->factory_id;
        $certificate->delete();
        return redirect()->route('factories.show', $factoryId)->with('success', 'Certificate deleted successfully.');
    }
}
