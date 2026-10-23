<?php

namespace App\Http\Controllers;

use App\Models\StaffordQualification;
use App\Models\Erav\EravProductStyle;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class StaffordQualificationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->wantsJson() || $request->ajax()) {
            $query = StaffordQualification::orderBy('created_at', 'desc');
            if ($request->filled('status') && $request->status !== 'All') {
                $query->where('status', $request->status);
            } else {
                // By default, exclude Rejected records from the main collections table
                $query->where('status', '!=', 'Rejected');
            }
            $records = $query->get()->map(function($record) {
                return $this->mapToCamelCase($record);
            });

            // --- GROUP BY: Factory Name + Style No + Season ---
            $groupedRecords = $records->groupBy(function ($item) {
                $factory = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['factoryName'] ?? '')));
                $style   = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['styleNo'] ?? '')));
                $season  = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['season'] ?? '')));
                return $factory . '___' . $style . '___' . $season;
            })->map(function ($group) {
                // Pick the record that has test report attachments if any exist in the group
                $bestRecord = $group->first(function ($item) {
                    if (empty($item['tests']) || !is_array($item['tests'])) return false;
                    foreach ($item['tests'] as $t) {
                        if (!empty($t['testReportFile']) || !empty($t['testReportFileUrl']) || !empty($t['testReportUrl'])) {
                            return true;
                        }
                    }
                    return false;
                }) ?? $group->first(function ($item) {
                    return !empty($item['tests']) && is_array($item['tests']) && count($item['tests']) > 0;
                }) ?? $group->first();

                $allPoNumbers = $group->pluck('poNumber')->filter()->unique()->values()->all();
                $allIds = $group->pluck('id')->values()->all();

                return array_merge($bestRecord, [
                    'allPoNumbers'  => $allPoNumbers,
                    'otherPosCount' => count($allPoNumbers) - 1,
                    'allIds'        => $allIds, 
                    'groupCount'    => $group->count(),
                ]);
            })->values();

            return response()->json($groupedRecords);
        }
        
        $labs = \App\Models\Lab::where('is_active', true)->orderBy('name')->get();
        $testReportTypes = \App\Models\TestReportType::where('is_active', true)->orderBy('name')->get();
        $initialStatus = $request->get('status', 'All');

        try {
            $styles = EravProductStyle::whereNotNull('styleid')
                ->where('styleid', '!=', '')
                ->where('styleid', '!=', '-')
                ->distinct()
                ->orderBy('styleid')
                ->pluck('styleid');
        } catch (\Throwable $e) {
            $styles = collect();
        }

        return view('stafford_certs.index', compact('labs', 'testReportTypes', 'initialStatus', 'styles'));
    }


   public function store(Request $request)
    {
        $validated = $this->validateCamelCase($request);
        $baseData = $this->mapToSnakeCase($validated);

        if (auth()->check() && auth()->user()->isLab() && empty($baseData['lab_id'])) {
            $baseData['lab_id'] = auth()->user()->lab_id;
        }

        // 1. Gather all PO numbers that need this test report
        $poNumbers = [];
        if (!empty($validated['allPoNumbers']) && is_array($validated['allPoNumbers'])) {
            $poNumbers = array_values(array_unique(array_filter(array_map('strval', $validated['allPoNumbers']))));
        }

        // Ensure the main po_number is in the list
        if (!empty($baseData['po_number']) && !in_array((string)$baseData['po_number'], $poNumbers, true)) {
            array_unshift($poNumbers, (string)$baseData['po_number']);
        }

        // Fallback if empty
        if (empty($poNumbers)) {
            $poNumbers = !empty($baseData['po_number']) ? [(string)$baseData['po_number']] : [];
        }

        if (empty($poNumbers)) {
            return response()->json([
                'success' => false,
                'message' => 'PO number is required.'
            ], 422);
        }

        $primaryResult = null;
        $savedCount = 0;

        // 2. Loop through every PO in the group
        foreach ($poNumbers as $poNum) {
            $data = $baseData;
            $data['po_number'] = $poNum;

            // Generate a unique cert number per PO
            $prefix = !empty($data['cert_type']) ? substr($data['cert_type'], 0, 3) : 'GEN';
            $data['cert_no'] = strtoupper($prefix) . '-' . $poNum . '-' . rand(1000, 9999);

            // Assign PO-specific identifiers
            $data['lot_number'] = $poNum;
            if (empty($data['primary_product_id'])) {
            $data['primary_product_id'] = !empty($data['style_no']) ? $data['style_no'] : $poNum;
            }

            if (empty($data['season'])) {
                $prodOrder = \App\Models\Erav\EravProdOrder::where('prodorder_ref', $poNum)
                    ->where('isoffline', 0)
                    ->first();
                if ($prodOrder && !empty($prodOrder->prod_order_season)) {
                    $data['season'] = $prodOrder->prod_order_season;
                } else {
                    $eravPo = \App\Models\Erav\EravPurchaseOrder::with('prodOrders')
                        ->where('pdf_po_refno', $poNum)
                        ->first();

                    if ($eravPo) {
                        $data['season'] = $eravPo->prodOrders->first(fn($p) => !empty($p->prod_order_season))?->prod_order_season ?? '';
                    }
                }
            }


            // Look up existing record by PO number AND Style No (and season if present)
            $existingQuery = StaffordQualification::withoutGlobalScopes()
                ->where('po_number', $poNum);

            if (!empty($data['style_no'])) {
                $existingQuery->where('style_no', $data['style_no']);
            }

            if (!empty($data['season'])) {
                $existingQuery->where(function ($q) use ($data) {
                    $q->where('season', $data['season'])
                      ->orWhereNull('season')
                      ->orWhere('season', '');
                });
            }

            $existing = $existingQuery->latest('id')->first();

            if ($existing) {
                // Guard: If another lab submitted this record, do not overwrite
                if (auth()->check() && auth()->user()->isLab() && !empty($existing->lab_id) && $existing->lab_id != auth()->user()->lab_id) {
                    continue;
                }

                $isAdmin = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasRole('super_admin'));

                // Guard: If synced to CPSC/ERP, skip locked record unless user is admin
                if ($existing->api_sync_status === 'synced' && !$isAdmin) {
                    if (!$primaryResult) $primaryResult = $existing;
                    continue;
                }

                $data = $this->preserveExistingData($data, $existing);
                $existing->update($data);
                $existing->load('lab');

                if (!$primaryResult || $poNum === (string)$baseData['po_number']) {
                    $primaryResult = $existing;
                }
                $savedCount++;
            } else {
                $record = StaffordQualification::create($data);
                $record->load('lab');

                if (!$primaryResult || $poNum === (string)$baseData['po_number']) {
                    $primaryResult = $record;
                }
                $savedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Qualification record(s) saved successfully for {$savedCount} PO(s)!",
            'data'    => $primaryResult ? $this->mapToCamelCase($primaryResult) : null
        ]);
    }

    public function show($id)
    {
        $record = StaffordQualification::findOrFail($id);
        return response()->json($this->mapToCamelCase($record));
    }

    public function bulkAction(Request $request)
    {
        $action = $request->input('action');
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return response()->json(['message' => 'No records selected'], 400);
        }

        if ($action === 'archive') {
            StaffordQualification::whereIn('id', $ids)->delete();
        } elseif ($action === 'complete') {
            StaffordQualification::whereIn('id', $ids)->update(['status' => 'Complete']);
        } elseif ($action === 'incomplete') {
            StaffordQualification::whereIn('id', $ids)->update(['status' => 'Incomplete']);
        } elseif ($action === 'reject') {
            $reason = trim($request->input('reason', ''));
            $hasCols = \Illuminate\Support\Facades\Schema::hasColumn('stafford_qualifications', 'rejection_reason');
            $records = StaffordQualification::whereIn('id', $ids)->get();
            foreach ($records as $rec) {
                $rec->status = 'Rejected';
                if ($hasCols) {
                    $rec->rejection_reason = $reason ?: null;
                    $rec->rejected_at = now();
                    $rec->rejected_by = auth()->id();
                }
                $rec->api_sync_status = 'pending';
                $rec->save();
            }
            return response()->json(['message' => 'Selected record(s) rejected successfully']);
        } elseif ($action === 'sync-cpsc') {
            \App\Jobs\SyncCertificatesToCPSC::dispatch($ids);
            return response()->json(['message' => 'CPSC Sync Job dispatched successfully']);
        }

        return response()->json(['message' => 'Bulk action completed successfully']);
    }

    /**
     * Reject a qualification report and return it to the laboratory.
     */
    public function reject(Request $request, $id)
    {
        $qualification = StaffordQualification::withoutGlobalScopes()->findOrFail($id);
        $reason = trim($request->input('reason', ''));

        // Target all records in the same group (Factory + Style + Season)
        $query = StaffordQualification::withoutGlobalScopes()
            ->where('factory_name', $qualification->factory_name)
            ->where('style_no', $qualification->style_no);

        if (!empty($qualification->season)) {
            $query->where('season', $qualification->season);
        } else {
            $query->where(function ($q) {
                $q->whereNull('season')->orWhere('season', '');
            });
        }

        $records = $query->get();
        if ($records->isEmpty()) {
            $records = collect([$qualification]);
        }

        $hasCols = \Illuminate\Support\Facades\Schema::hasColumn('stafford_qualifications', 'rejection_reason');

        foreach ($records as $rec) {
            $rec->status = 'Rejected';
            if ($hasCols) {
                $rec->rejection_reason = $reason ?: null;
                $rec->rejected_at = now();
                $rec->rejected_by = auth()->id();
            }
            // Unlock sync so laboratory can edit and resubmit
            $rec->api_sync_status = 'pending';
            $rec->save();
        }

        return response()->json([
            'success' => true,
            'message' => "Record rejected successfully (" . $records->count() . " PO(s) returned to laboratory).",
        ]);
    }

    public function update(Request $request, $id)
    {
        $staffordQualification = StaffordQualification::findOrFail($id);

        if (auth()->check() && auth()->user()->isLab() && !empty($staffordQualification->lab_id) && $staffordQualification->lab_id != auth()->user()->lab_id) {
            $staffordQualification->load('lab');
            $labName = $staffordQualification->lab?->name ?? 'another laboratory';
            return response()->json([
                'success' => false,
                'message' => "A test report for this PO has already been submitted by {$labName}. You cannot edit it."
            ], 403);
        }

        $isAdmin = auth()->check() && (auth()->user()->isAdmin() || auth()->user()->hasRole('super_admin'));

        if ($staffordQualification->api_sync_status === 'synced' && !$isAdmin) {
            return response()->json([
                'success' => false,
                'message' => 'This record has already been synced to CPSC/ERP. Edits are locked.'
            ], 403);
        }

        $validated = $this->validateCamelCase($request);
        $data = $this->mapToSnakeCase($validated);

        // If updating a previously rejected task, reset status to Complete and clear rejection reason
        if ($staffordQualification->status === 'Rejected' || empty($data['status']) || $data['status'] === 'Rejected') {
            $data['status'] = 'Complete';
            if (\Illuminate\Support\Facades\Schema::hasColumn('stafford_qualifications', 'rejection_reason')) {
                $data['rejection_reason'] = null;
            }
        }

        $data = $this->preserveExistingData($data, $staffordQualification);

        $staffordQualification->update($data);
        $staffordQualification->load('lab');

        // Also update shared product/testing details across other POs in the same collection
        $groupQuery = StaffordQualification::where('factory_name', $staffordQualification->factory_name)
            ->where('style_no', $staffordQualification->style_no)
            ->where('id', '!=', $staffordQualification->id);

        if (!empty($staffordQualification->season)) {
            $groupQuery->where('season', $staffordQualification->season);
        } else {
            $groupQuery->where(function ($q) {
                $q->whereNull('season')->orWhere('season', '');
            });
        }

        if (!$isAdmin) {
            $groupQuery->where('api_sync_status', '!=', 'synced');
        }

        $sharedData = collect($data)->except(['po_number', 'cert_no', 'lot_number'])->all();
        foreach ($groupQuery->get() as $otherRecord) {
            $otherData = $this->preserveExistingData($sharedData, $otherRecord);
            $otherRecord->update($otherData);
        }

        return response()->json([
            'success' => true,
            'message' => 'Qualification record updated successfully!',
            'data' => $this->mapToCamelCase($staffordQualification)
        ]);
    }

    public function destroy($id)
{
    $staffordQualification = StaffordQualification::findOrFail($id);
    // Find and delete all records belonging to the same group (Factory + Style + Season)
    $query = StaffordQualification::where('factory_name', $staffordQualification->factory_name)
        ->where('style_no', $staffordQualification->style_no);
    if (!empty($staffordQualification->season)) {
        $query->where('season', $staffordQualification->season);
    } else {
        $query->where(function ($q) {
            $q->whereNull('season')->orWhere('season', '');
        });
    }
    $deletedCount = $query->delete();
    return response()->json([
        'success' => true,
        'message' => "Collection archived successfully ({$deletedCount} PO records deleted)!"
    ]);
}


    public function exportPdf($id)
    {
        $record = StaffordQualification::findOrFail($id);
        
        $pdf = Pdf::loadView('stafford_certs.pdf', compact('record'));
        
        // Match the filename format from screenshots
        $filename = ($record->importer_name ?? 'Importer') . '-' . date('m-d-Y') . '.pdf';
        
        return $pdf->download($filename);
    }

    public function exportBulkPdf(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return back()->with('error', 'No records selected.');
        }

        $records = StaffordQualification::whereIn('id', $ids)->get();
        if ($records->isEmpty()) {
            return back()->with('error', 'No records found.');
        }

        $zip = new \ZipArchive();
        $zipFileName = 'Bulk_Certificates_' . date('m-d-Y_H-i-s') . '.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($records as $record) {
                $pdf = Pdf::loadView('stafford_certs.pdf', compact('record'));
                $filename = ($record->importer_name ?? 'Importer') . '-' . $record->id . '-' . date('m-d-Y') . '.pdf';
                $zip->addFromString($filename, $pdf->output());
            }
            $zip->close();
        } else {
            return back()->with('error', 'Failed to create zip file.');
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }

    private function validateCamelCase(Request $request)
    {
        return $request->validate([
            'id' => 'nullable|integer',
            'poNumber' => 'nullable|string',
            'season' => 'nullable|string|max:50',
            'allPoNumbers' => 'nullable|array',
            'allPoNumbers.*' => 'nullable|string',
            'poDate' => 'nullable|string',
            'buyer' => 'nullable|string',
            'shipment' => 'nullable|string',
            'origin' => 'nullable|string',
            'styleNo' => 'nullable|string|max:50',
            'erpStyleDesc' => 'nullable|string',
            'productName' => 'required|string|max:250',
            'brandName' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:50',
            'productStyle' => 'nullable|string|max:50',
            'primaryProductId' => 'required|string|max:50',
            'gtin' => 'nullable|string',
            'upc' => 'nullable|string',
            'certType' => 'required|string|in:GCC,CPC',
            'intendedUse' => 'nullable|string',
            'childrenProduct' => 'nullable|string',
            'certLanguage' => 'nullable|string',
            'certScope' => 'nullable|string',
            'certNo' => 'nullable|string',
            'factoryName' => 'required|string|max:100',
            'factoryAddress' => 'nullable|string|max:255',
            'factoryCity' => 'nullable|string|max:50',
            'factoryState' => 'nullable|string|max:100',
            'factoryPostal' => 'nullable|string|max:10',
            'factoryCountry' => 'nullable|string|max:50',
            'factoryGLN' => 'nullable|string|max:50',
            'factoryAltId' => 'nullable|string|max:50',
            'factoryContact' => 'nullable|string',
            'factoryEmail' => 'nullable|string|max:50|email',
            'factoryPhone' => 'nullable|string|max:20',
            'importerName' => 'nullable|string|max:100',
            'importerAddress' => 'nullable|string|max:100',
            'importerCity' => 'nullable|string|max:50',
            'importerState' => 'nullable|string|max:100',
            'importerPostal' => 'nullable|string|max:10',
            'importerCountry' => 'nullable|string|max:50',
            'certifierName' => 'nullable|string|max:100',
            'certifierTitle' => 'nullable|string',
            'certifierEmail' => 'nullable|string|max:50|email',
            'certifierPhone' => 'nullable|string|max:20',
            'tests' => 'nullable|array',
            'tests.*.labType' => 'nullable|string|max:50',
            'tests.*.labName' => 'required|string|max:100',
            'tests.*.testReportId' => 'nullable|string|max:400',
            'tests.*.testDate' => 'nullable|date',
            'tests.*.labCpscId' => 'nullable|string',
            'tests.*.gln' => 'nullable|string|max:50',
            'tests.*.alternateId' => 'nullable|string|max:50',
            'tests.*.addressLine1' => 'nullable|string|max:100',
            'tests.*.addressLine2' => 'nullable|string|max:100',
            'tests.*.aptNumber' => 'nullable|string|max:10',
            'tests.*.city' => 'nullable|string|max:50',
            'tests.*.stateProvince' => 'nullable|string|max:100',
            'tests.*.country' => 'nullable|string|max:50',
            'tests.*.postalCode' => 'nullable|string|max:10',
            'tests.*.phone' => 'nullable|string|max:20',
            'tests.*.email' => 'nullable|string|max:50|email',
            'tests.*.isExclusion' => 'nullable|boolean',
            'tests.*.exclusionReason' => 'nullable|string',
            'tests.*.labNameOther' => 'nullable|string',
            'tests.*.testReportUrl' => 'nullable|string',
            'tests.*.testReportKey' => 'nullable|string',
            'tests.*.testReportFile' => 'nullable|string',
            'tests.*.testReportFileName' => 'nullable|string',
            'tests.*.testReportFileUrl' => 'nullable|string',
            'tests.*.componentTesting' => 'nullable|boolean',
            'tests.*.componentDetails' => 'nullable|string',
            'tests.*.citations' => 'nullable|array',
            'tests.*.citations.*.code' => 'required|string',
            'tests.*.citations.*.desc' => 'nullable|string',
            'productIdType' => 'required|string',
            'additionalIdentifiers' => 'nullable|array',
            'description' => 'nullable|string|max:250',
            'versionId' => 'required|string|max:19',
            'status' => 'nullable|string',
            'manufactureDate' => 'required|string',
            'manufactureFullDate' => 'nullable|date',
            'manufactureStartDate' => 'nullable|date',
            'manufactureEndDate' => 'nullable|date',
            'lotNumber' => 'nullable|string|max:100',
            'lotAssignedBy' => 'nullable|string|max:50',
            'lastTestDate' => 'nullable|date',
            'labId' => 'nullable|exists:labs,id',
            'pointOfContact' => 'nullable|string',
            'pocType' => 'nullable|string|in:Importer,Domestic Manufacturer,Private Labeler,Manufacturer,Laboratory,Broker,Other',
            'pocGln' => 'nullable|string|max:50',
            'pocAlternateId' => 'nullable|string|max:50',
            'pocName' => 'nullable|string|max:100',
            'pocAddressLine1' => 'nullable|string|max:100',
            'pocAddressLine2' => 'nullable|string|max:100',
            'pocAptNumber' => 'nullable|string|max:10',
            'pocCity' => 'nullable|string|max:50',
            'pocStateProvince' => 'nullable|string|max:100',
            'pocCountry' => 'nullable|string|max:50',
            'pocPostalCode' => 'nullable|string|max:10',
            'pocPhone' => 'nullable|string|max:20',
            'pocEmail' => 'nullable|string|max:50|email',
        ], [], [
            'poNumber' => 'PO Number',
            'styleNo' => 'Style No',
            'productName' => 'Product Name',
            'primaryProductId' => 'Model # / Primary Product ID',
            'certType' => 'Certificate Type',
            'intendedUse' => 'Intended Use',
            'childrenProduct' => 'Children\'s Product',
            'certLanguage' => 'Certificate Language',
            'certScope' => 'Certification Scope',
            'factoryName' => 'Factory Name',
            'factoryAddress' => 'Factory Address',
            'factoryCity' => 'Factory City',
            'factoryCountry' => 'Factory Country',
            'certifierName' => 'Certifier Name',
            'certifierEmail' => 'Certifier Email',
            'certifierPhone' => 'Certifier Phone',
            'tests.*.labType' => 'Laboratory Type',
            'tests.*.labName' => 'Laboratory Name',
            'tests.*.testReportId' => 'Test Report ID',
            'tests.*.testDate' => 'Test Date',
        ]);
    }

    private function parseAddressComponents(?string $addr): array
    {
        if (empty($addr)) {
            return ['city' => null, 'state' => null, 'postal' => null, 'country' => null];
        }

        $lines = array_values(array_filter(array_map('trim', explode("\n", str_replace(["\r\n", "\r"], "\n", $addr)))));
        $country = null;
        $postal = null;
        $state = null;
        $city = null;

        $knownCountries = ['USA', 'United States', 'US', 'Canada', 'Egypt', 'Bangladesh', 'India', 'Sri Lanka', 'Vietnam', 'Cambodia', 'China'];
        foreach ($lines as $line) {
            foreach ($knownCountries as $c) {
                if (stripos($line, $c) !== false) {
                    $country = ($c === 'US') ? 'USA' : $c;
                    break 2;
                }
            }
        }

        foreach ($lines as $line) {
            if (preg_match('/\b([A-Z]{2})\s+(\d{5}(?:-\d{4})?)\b/i', $line, $m)) {
                $state = strtoupper($m[1]);
                $postal = $m[2];
                break;
            } elseif (preg_match('/\b([A-Z]\d[A-Z]\s*\d[A-Z]\d|\d{4,6})\b/', $line, $m)) {
                $postal = $m[1];
            }
        }

        if (preg_match('/([A-Za-z\s]+),\s*([A-Z]{2})\b/', $addr, $m)) {
            $city = trim($m[1]);
            if (!$state) $state = strtoupper($m[2]);
        } else {
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = $lines[$i];
                if ($country && stripos($line, $country) !== false) continue;
                if ($state && stripos($line, $state) !== false) continue;
                if ($postal && stripos($line, $postal) !== false) continue;
                if (preg_match('/^[A-Za-z\s]+$/', $line) && strlen($line) < 35) {
                    $city = $line;
                    break;
                }
            }
        }

        return [
            'city' => $city,
            'state' => $state,
            'postal' => $postal,
            'country' => $country ?: 'USA',
        ];
    }

    private function preserveExistingData(array $data, StaffordQualification $existing): array
    {
        $preserveFields = [
            'lab_id', 'style_no','season', 'product_style', 'buyer', 'po_date', 'factory_name', 
            'factory_address', 'factory_city', 'factory_state', 'factory_postal', 'factory_country',
            'factory_contact', 'factory_email', 'factory_phone', 'factory_gln', 'factory_alt_id',
            'importer_name', 'importer_address', 'importer_city', 'importer_state', 'importer_postal', 'importer_country',
            'certifier_name', 'certifier_title', 'certifier_email', 'certifier_phone',
            'origin', 'shipment', 'color', 'brand_name', 'upc', 'gtin', 'cert_no', 'description', 'erp_style_desc'
        ];

        foreach ($preserveFields as $field) {
            if (empty($data[$field]) && !empty($existing->$field)) {
                $data[$field] = $existing->$field;
            }
        }

        if (empty($data['lab_id'])) {
            if (auth()->check() && auth()->user()->isLab() && auth()->user()->lab_id) {
                $data['lab_id'] = auth()->user()->lab_id;
            } elseif (!empty($existing->lab_id)) {
                $data['lab_id'] = $existing->lab_id;
            }
        }

        return $data;
    }

    private function mapToSnakeCase($data)
    {
        $importerName = $data['importerName'] ?? null;
        $importerAddr = $data['importerAddress'] ?? null;
        $importerCity = $data['importerCity'] ?? null;
        $importerState = $data['importerState'] ?? null;
        $importerPostal = $data['importerPostal'] ?? null;
        $importerCountry = $data['importerCountry'] ?? null;

        if ($importerAddr && (empty($importerCity) || empty($importerCountry))) {
            $parsed = $this->parseAddressComponents($importerAddr);
            $importerCity = $importerCity ?: $parsed['city'];
            $importerState = $importerState ?: $parsed['state'];
            $importerPostal = $importerPostal ?: $parsed['postal'];
            $importerCountry = $importerCountry ?: $parsed['country'];
        }

        $pointOfContact = $data['pointOfContact'] ?? 'Importer';
        $pocType = $data['pocType'] ?? $pointOfContact;
        $pocName = $data['pocName'] ?? ($pointOfContact === 'Importer' ? $importerName : ($data['factoryName'] ?? null));
        $pocAddr1 = $data['pocAddressLine1'] ?? ($pointOfContact === 'Importer' ? $importerAddr : ($data['factoryAddress'] ?? null));
        $pocCity = $data['pocCity'] ?? ($pointOfContact === 'Importer' ? $importerCity : ($data['factoryCity'] ?? null));
        $pocState = $data['pocStateProvince'] ?? ($pointOfContact === 'Importer' ? $importerState : ($data['factoryState'] ?? null));
        $pocCountry = $data['pocCountry'] ?? ($pointOfContact === 'Importer' ? $importerCountry : ($data['factoryCountry'] ?? null));
        $pocPostal = $data['pocPostalCode'] ?? ($pointOfContact === 'Importer' ? $importerPostal : ($data['factoryPostal'] ?? null));
        $pocPhone = $data['pocPhone'] ?? ($pointOfContact === 'Importer' ? null : ($data['factoryPhone'] ?? null));
        $pocEmail = $data['pocEmail'] ?? ($pointOfContact === 'Importer' ? null : ($data['factoryEmail'] ?? null));

        $certifierName = $data['certifierName'] ?? 'Stafford Textile Ltd';
        $certifierTitle = $data['certifierTitle'] ?? 'Compliance Manager';
        $certifierEmail = $data['certifierEmail'] ?? 'compliance@staffordtextile.com';
        $certifierPhone = $data['certifierPhone'] ?? '+1 416-555-0199';
        $certLanguage = $data['certLanguage'] ?? 'English';
        $certScope = $data['certScope'] ?? 'National - USA (CPSC)';

        $upc = $data['upc'] ?? null;
        if (empty($upc) && !empty($data['primaryProductId']) && ($data['productIdType'] ?? '') === 'UPC') {
            $upc = $data['primaryProductId'];
        }

        // Calculate manufacture date before building the return array
        $mfgDate = null;
        if (!empty($data['manufactureFullDate']) && !empty($data['manufactureDate']) && str_starts_with($data['manufactureFullDate'], $data['manufactureDate'])) {
            $mfgDate = $data['manufactureFullDate'];
        } elseif (!empty($data['manufactureEndDate']) && !empty($data['manufactureDate']) && \Carbon\Carbon::parse($data['manufactureEndDate'])->addDays(5)->format('Y-m') === $data['manufactureDate']) {
            $mfgDate = \Carbon\Carbon::parse($data['manufactureEndDate'])->addDays(5)->format('Y-m-d');
        } elseif (!empty($data['manufactureDate'])) {
            $mfgDate = \Carbon\Carbon::parse($data['manufactureDate'])->format('Y-m-d');
        }
        // Auto-extract the latest test date from the tests array if not directly provided
        $lastTestDate = $data['lastTestDate'] ?? null;
        if (empty($lastTestDate) && !empty($data['tests']) && is_array($data['tests'])) {
            $lastTestDate = collect($data['tests'])->pluck('testDate')->filter()->sortDesc()->first() ?? null;
        }

        return [
            'po_number' => $data['poNumber'] ?? null,
            'po_date' => $data['poDate'] ?? null,
            'buyer' => $data['buyer'] ?? $importerName,
            'shipment' => $data['shipment'] ?? null,
            'origin' => $data['origin'] ?? ($data['factoryCountry'] ?? null),
            'style_no' => $data['styleNo'] ?? null,
            'season' => $data['season'] ?? null,
            'erp_style_desc' => $data['erpStyleDesc'] ?? null,
            'product_name' => $data['productName'] ?? null,
            'brand_name' => $data['brandName'] ?? null,
            'color' => $data['color'] ?? null,
            'product_style' => $data['productStyle'] ?? ($data['styleNo'] ?? null),
            'primary_product_id' => $data['primaryProductId'] ?? null,
            'gtin' => $data['gtin'] ?? null,
            'upc' => $upc,
            'cert_type' => $data['certType'] ?? 'GCC',
            'intended_use' => $data['intendedUse'] ?? null,
            'children_product' => $data['childrenProduct'] ?? null,
            'cert_language' => $certLanguage,
            'cert_scope' => $certScope,
            'cert_no' => $data['certNo'] ?? null,
            'factory_name' => $data['factoryName'] ?? null,
            'factory_address' => $data['factoryAddress'] ?? null,
            'factory_city' => $data['factoryCity'] ?? null,
            'factory_state' => $data['factoryState'] ?? null,
            'factory_postal' => $data['factoryPostal'] ?? null,
            'factory_country' => $data['factoryCountry'] ?? null,
            'factory_gln' => $data['factoryGLN'] ?? null,
            'factory_alt_id' => $data['factoryAltId'] ?? null,
            'factory_contact' => $data['factoryContact'] ?? null,
            'factory_email' => $data['factoryEmail'] ?? null,
            'factory_phone' => $data['factoryPhone'] ?? null,
            'importer_name' => $importerName,
            'importer_address' => $importerAddr,
            'importer_city' => $importerCity,
            'importer_state' => $importerState,
            'importer_postal' => $importerPostal,
            'importer_country' => $importerCountry,
            'certifier_name' => $certifierName,
            'certifier_title' => $certifierTitle,
            'certifier_email' => $certifierEmail,
            'certifier_phone' => $certifierPhone,
            'tests' => $data['tests'] ?? null,
            'product_id_type' => $data['productIdType'] ?? null,
            'additional_identifiers' => $data['additionalIdentifiers'] ?? null,
            'description' => $data['description'] ?? null,
            'version_id' => $data['versionId'] ?? null,
            'status' => $data['status'] ?? 'Incomplete',
            'manufacture_date' => $mfgDate,
            'manufacture_start_date' => $data['manufactureStartDate'] ?? null,
            'manufacture_end_date' => $data['manufactureEndDate'] ?? null,
            'lot_number' => $data['lotNumber'] ?? null,
            'lot_assigned_by' => $data['lotAssignedBy'] ?? 'Manufacturer',
            'last_test_date' => $lastTestDate,
            'lab_id' => $data['labId'] ?? null,
            'point_of_contact' => $pointOfContact,
            'poc_type' => $pocType,
            'poc_gln' => $data['pocGln'] ?? null,
            'poc_alternate_id' => $data['pocAlternateId'] ?? null,
            'poc_name' => $pocName,
            'poc_address_line1' => $pocAddr1,
            'poc_address_line2' => $data['pocAddressLine2'] ?? null,
            'poc_apt_number' => $data['pocAptNumber'] ?? null,
            'poc_city' => $pocCity,
            'poc_state_province' => $pocState,
            'poc_country' => $pocCountry,
            'poc_postal_code' => $pocPostal,
            'poc_phone' => $pocPhone,
            'poc_email' => $pocEmail,
            'rejection_reason' => $data['rejectionReason'] ?? null,
        ];
    }

    private function mapToCamelCase($record)
    {
        return [
            'id' => $record->id,
            'poNumber' => $record->po_number,
            'poDate' => $record->po_date,
            'buyer' => $record->buyer,
            'shipment' => $record->shipment,
            'origin' => $record->origin,
            'styleNo' => $record->style_no,
            'season' => $record->season ?? '',
            'erpStyleDesc' => $record->erp_style_desc,
            'productName' => $record->product_name,
            'brandName' => $record->brand_name,
            'color' => $record->color,
            'productStyle' => $record->product_style,
            'primaryProductId' => $record->primary_product_id,
            'gtin' => $record->gtin,
            'upc' => $record->upc,
            'certType' => $record->cert_type,
            'intendedUse' => $record->intended_use,
            'childrenProduct' => $record->children_product,
            'certLanguage' => $record->cert_language,
            'certScope' => $record->cert_scope,
            'certNo' => $record->cert_no,
            'factoryName' => $record->factory_name,
            'factoryAddress' => $record->factory_address,
            'factoryCity' => $record->factory_city,
            'factoryState' => $record->factory_state,
            'factoryPostal' => $record->factory_postal,
            'factoryCountry' => $record->factory_country,
            'factoryGLN' => $record->factory_gln,
            'factoryAltId' => $record->factory_alt_id,
            'factoryContact' => $record->factory_contact,
            'factoryEmail' => $record->factory_email,
            'factoryPhone' => $record->factory_phone,
            'importerName' => $record->importer_name,
            'importerAddress' => $record->importer_address,
            'importerCity' => $record->importer_city,
            'importerState' => $record->importer_state,
            'importerPostal' => $record->importer_postal,
            'importerCountry' => $record->importer_country,
            'certifierName' => $record->certifier_name,
            'certifierTitle' => $record->certifier_title,
            'certifierEmail' => $record->certifier_email,
            'certifierPhone' => $record->certifier_phone,
            'tests' => $record->tests,
            'productIdType' => $record->product_id_type,
            'additionalIdentifiers' => $record->additional_identifiers ?? [],
            'description' => $record->description,
            'versionId' => $record->version_id,
            'status' => $record->status,
            'manufactureDate' => $record->manufacture_date ? \Carbon\Carbon::parse($record->manufacture_date)->format('Y-m') : null,
            'manufactureFullDate' => $record->manufacture_date,
            'manufactureStartDate' => $record->manufacture_start_date,
            'manufactureEndDate' => $record->manufacture_end_date,
            'lotNumber' => $record->lot_number,
            'lotAssignedBy' => $record->lot_assigned_by,
            'lastTestDate' => $record->last_test_date ?: (collect($record->tests)->pluck('testDate')->filter()->sortDesc()->first() ?? null),
            'pointOfContact' => $record->point_of_contact,
            'pocType' => $record->poc_type,
            'pocGln' => $record->poc_gln,
            'pocAlternateId' => $record->poc_alternate_id,
            'pocName' => $record->poc_name,
            'pocAddressLine1' => $record->poc_address_line1,
            'pocAddressLine2' => $record->poc_address_line2,
            'pocAptNumber' => $record->poc_apt_number,
            'pocCity' => $record->poc_city,
            'pocStateProvince' => $record->poc_state_province,
            'pocCountry' => $record->poc_country,
            'pocPostalCode' => $record->poc_postal_code,
            'pocPhone' => $record->poc_phone,
            'pocEmail' => $record->poc_email,
            'apiSyncStatus' => $record->api_sync_status,
            'apiSyncMessage' => $record->api_sync_message,
            'rejectionReason' => $record->rejection_reason ?? null,
            'rejectedAt' => $record->rejected_at ? \Carbon\Carbon::parse($record->rejected_at)->format('M d, Y H:i') : null,
            'labId' => $record->lab_id,
            'assignedLabName' => $record->lab?->name,
        ];
    }

    public function simulateMockPo()
    {
        $lab = \App\Models\Lab::first();
        if (!$lab) {
            return redirect()->route('stafford-certs.index')->with('error', 'No Labs exist yet. Please create a lab first.');
        }

        // Fetch a real random PO from ERP and assign to lab
        $service = app(\App\Services\ApiIntegrationService::class);
        $qualification = $service->fetchPoAndAssignToLab('', $lab->id);

        return redirect()->route('stafford-certs.index')->with('success', "Real ERP PO #{$qualification->po_number} imported and assigned to Lab: {$lab->name}");
    }

    /**
     * Upload a test report document file (PDF, image, Word) — accessible by super_admin.
     */
    public function uploadReportFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:20480|mimes:pdf,doc,docx,jpg,jpeg,png,webp,xls,xlsx',
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $path = $file->store('lab_reports', 'public');
        $url = asset('storage/' . $path);

        return response()->json([
            'success'   => true,
            'file_path' => $path,
            'file_name' => $originalName,
            'file_url'  => $url,
        ]);
    }

    public function eravPoLookup(Request $request, \App\Services\ApiIntegrationService $service)
    {
        $poNumber = trim($request->query('po', ''));
        $style = trim($request->query('style', ''));
        if (!$poNumber) {
            return response()->json(['found' => false, 'message' => 'PO number is required'], 400);
        }

        $details = $service->getPoDetails($poNumber, $style ?: null);
        if (!$details) {
            return response()->json(['found' => false, 'message' => "PO #{$poNumber} was not found in the ERP database."], 404);
        }

        return response()->json($details);
    }

    public function eravPoSearch(Request $request, \App\Services\ApiIntegrationService $service)
    {
        $term = trim($request->query('q', ''));
        $query = \App\Models\Erav\EravProdOrder::query()
            ->whereNotNull('prodorder_ref')
            ->where('prodorder_ref', '!=', '')
            ->where('prodorder_ref', '!=', '-')
            ->where('isoffline', 0)
            ->whereHas('purchaseOrder.items');

        if ($term) {
            $query->where(function($q) use ($term) {
                $q->where('prodorder_ref', 'like', "%{$term}%")
                  ->orWhereHas('purchaseOrder', function($poQuery) use ($term) {
                      $poQuery->where('customer_name', 'like', "%{$term}%")
                          ->orWhereHas('items', function($sub) use ($term) {
                              $sub->where('item_styleno', 'like', "%{$term}%")
                                  ->orWhere('item_description', 'like', "%{$term}%");
                          });
                  });
            });
        }

        $poList = $query->with(['purchaseOrder.items:id,porder_pdf_list_id,item_styleno,item_description', 'purchaseOrder:id,customer_name,porder_date'])
                     ->orderBy('prodorder_id', 'desc')
                     ->limit(35)
                     ->get();

        $poRefs = $poList->pluck('prodorder_ref')->filter()->toArray();
        $qualifications = StaffordQualification::withoutGlobalScopes()
            ->with('lab')
            ->whereIn('po_number', $poRefs)
            ->whereNotNull('tests')
            ->get()
            ->filter(fn($q) => !empty($q->tests) && is_array($q->tests) && count($q->tests) > 0)
            ->keyBy('po_number');

        $results = $poList->map(function($po) use ($qualifications) {
            $firstItem = $po->purchaseOrder?->items?->first();
            $qual = $qualifications->get($po->prodorder_ref);
            $hasLabTests = !empty($qual);
            return [
                'id'          => $po->prodorder_id,
                'poNumber'    => $po->prodorder_ref,
                'poDate'      => $po->prodorder_date ?: $po->purchaseOrder?->porder_date,
                'buyer'       => $po->purchaseOrder?->customer_name,
                'style'       => $firstItem?->item_styleno ?? '',
                'product'     => $firstItem?->item_description ?? '',
                'hasLabTests' => $hasLabTests,
                'labName'     => $qual?->lab?->name,
                'testCount'   => $hasLabTests && is_array($qual->tests) ? count($qual->tests) : 0,
            ];
        });

        // Also include any StaffordQualification records with lab tests matching the search
        $existingPoSet = array_flip($results->pluck('poNumber')->toArray());
        $qualQuery = StaffordQualification::withoutGlobalScopes()
            ->with('lab')
            ->whereNotNull('po_number')
            ->where('po_number', '!=', '');

        if ($term) {
            $qualQuery->where(function($q) use ($term) {
                $q->where('po_number', 'like', "%{$term}%")
                  ->orWhere('buyer', 'like', "%{$term}%")
                  ->orWhere('style_no', 'like', "%{$term}%")
                  ->orWhere('product_name', 'like', "%{$term}%");
            });
        }

        $extraQuals = $qualQuery->orderBy('id', 'desc')->limit(10)->get();
        foreach ($extraQuals as $eq) {
            if (!isset($existingPoSet[$eq->po_number])) {
                $hasTests = !empty($eq->tests) && is_array($eq->tests) && count($eq->tests) > 0;
                $results->push([
                    'id'          => 'qual_' . $eq->id,
                    'poNumber'    => $eq->po_number,
                    'poDate'      => $eq->po_date ? (string)$eq->po_date : '',
                    'buyer'       => $eq->buyer,
                    'style'       => $eq->style_no ?? '',
                    'season'      => $eq->season ?? '',
                    'product'     => $eq->product_name ?? '',
                    'hasLabTests' => $hasTests,
                    'labName'     => $eq->lab?->name,
                    'testCount'   => $hasTests ? count($eq->tests) : 0,
                ]);
                $existingPoSet[$eq->po_number] = true;
            }
        }

        return response()->json($service->cleanUtf8($results->values()->all()));
    }
}
