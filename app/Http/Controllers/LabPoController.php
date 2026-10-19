<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravProductStyle;

use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravPurchaseOrderItem;
use App\Models\Lab;
use App\Models\StaffordQualification;
use App\Models\TestReportType;
use App\Services\ApiIntegrationService;
use Illuminate\Http\Request;

class LabPoController extends Controller
{
    /**
     * Display the All POs table for Laboratory users.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $lab = $user?->lab;

        // Distinct style numbers from ERP purchase order items for the filter
         try {
            $styles = EravProductStyle::whereNotNull('styleid')
                ->where('styleid', '!=', '')
                ->distinct()
                ->orderBy('styleid')
                ->pluck('styleid');
        } catch (\Throwable $e) {
            $styles = collect();
        }

        $testReportTypes = TestReportType::where('is_active', true)->orderBy('name')->get();
        $labs = Lab::where('is_active', true)->orderBy('name')->get();
        $userLabName = $lab?->name ?? 'Laboratory';
        $citationMaster = $this->getCitationMaster();

        return view('lab.pos.index', compact('styles', 'testReportTypes', 'labs', 'userLabName', 'citationMaster'));
    }

    /**
     * AJAX endpoint returning paginated ERP Purchase Orders with Style filter & Report status.
     */
    /**
     * AJAX endpoint returning paginated ERP Purchase Orders with Style filter & Report status.
     */
    public function poData(Request $request, ApiIntegrationService $service)
    {
        $user = auth()->user();
        $labId = $user?->lab_id;

        // 1. Get all PO numbers that actually have submitted test reports across all labs
        $existingQualifications = StaffordQualification::withoutGlobalScopes()
            ->with('lab:id,name')
            ->whereNotNull('tests')
            ->where('tests', '!=', '')
            ->where('tests', '!=', '[]')
            ->where('tests', '!=', 'null')
            ->get(['id', 'po_number', 'lab_id', 'status', 'api_sync_status', 'tests', 'created_at'])
            ->filter(fn($q) => !empty($q->tests) && is_array($q->tests) && count($q->tests) > 0)
            ->keyBy('po_number');

        $query = EravPurchaseOrder::query()
            ->whereNotNull('pdf_po_refno')
            ->where('pdf_po_refno', '!=', '')
            ->has('items');

        // Filter by Style
        if ($request->filled('style')) {
            $style = trim($request->style);
            $query->whereHas('items', function ($q) use ($style) {
                $q->where('item_styleno', $style);
            });
        }

        // Filter by Search Query (PO Ref, Customer/Buyer, Style No, or Description)
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('pdf_po_refno', 'like', "%{$term}%")
                    ->orWhere('customer_name', 'like', "%{$term}%")
                    ->orWhereHas('items', function ($sub) use ($term) {
                        $sub->where('item_styleno', 'like', "%{$term}%")
                            ->orWhere('item_description', 'like', "%{$term}%");
                    });
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 15;
        }

        // Fetch matching POs
        if ($request->filled('style') || $request->filled('search')) {
            $rawPos = $query->with(['items', 'prodOrders.branch'])
                ->orderBy('id', 'desc')
                ->get();
        } else {
            $rawPos = $query->with(['items', 'prodOrders.branch'])
                ->orderBy('id', 'desc')
                ->take(300)
                ->get();
        }

        // 2. Map raw PO items
        $mappedItems = $rawPos->map(function ($po) use ($existingQualifications, $labId) {
            $firstItem = $po->items->first();
            $prodOrder = $po->prodOrders->first(fn($p) => !empty($p->mbranchlist_id) && $p->branch !== null) ?? $po->prodOrders->first();
            $branch = $prodOrder?->branch;
            $factoryName = $branch?->br_name ? trim($branch->br_name) : ($branch?->br_code ?: 'Branch N/A');

            // Retrieve Season 
            $season = $po->prodOrders->first(fn($p) => !empty($p->prod_order_season))?->prod_order_season
                ?? $prodOrder?->prod_order_season
                ?? '';

            $poRef = (string) $po->pdf_po_refno;
            $existing = $existingQualifications->get($poRef);

            return [
                'id'            => $po->id,
                'poNumber'      => $poRef,
                'poDate'        => $po->porder_date ? (string)$po->porder_date : '',
                'buyer'         => $po->customer_name ?: 'Stafford Buyer',
                'factoryName'   => $factoryName,
                'styleNo'       => $firstItem?->item_styleno ?: '',
                'season'        => $season,
                'productName'   => $firstItem?->item_description ?: '',
                'hasReport'     => !empty($existing),
                'isMyLab'       => $existing ? ($labId && $existing->lab_id == $labId) : false,
                'submittingLab' => $existing?->lab?->name,
                'reportId'      => $existing?->id,
                'reportStatus'  => $existing?->status,
                'apiSyncStatus' => $existing?->api_sync_status,
                'itemCount'     => $po->items->count(),
            ];
        });

        // 3. Group by: Factory Name + Style No + Season
        $groupedData = $mappedItems->groupBy(function ($item) {
            $factory = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['factoryName'] ?? '')));
            $style   = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['styleNo'] ?? '')));
            $season  = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['season'] ?? '')));
            return $factory . '___' . $style . '___' . $season;
        })->map(function ($group) {
            $first = $group->first();
            $allPoNumbers = $group->pluck('poNumber')->unique()->values()->all();

            // Check if ANY PO in this group already has a test report
            $reportItem = $group->first(fn($item) => $item['hasReport']);

            return array_merge($first, [
                'allPoNumbers'  => $allPoNumbers,
                'otherPosCount' => count($allPoNumbers) - 1,
                'hasReport'     => !empty($reportItem),
                'isMyLab'       => $reportItem ? $reportItem['isMyLab'] : false,
                'submittingLab' => $reportItem ? $reportItem['submittingLab'] : null,
                'reportId'      => $reportItem ? $reportItem['reportId'] : null,
                'reportStatus'  => $reportItem ? $reportItem['reportStatus'] : null,
                'apiSyncStatus' => $reportItem ? $reportItem['apiSyncStatus'] : null,
            ]);
        })->values();

        // 4. Filter by Report Status AFTER grouping so entire groups are included/excluded together
        if ($request->report_status === 'needs_report') {
            $groupedData = $groupedData->filter(fn($item) => !$item['hasReport'])->values();
        } elseif ($request->report_status === 'added') {
            $groupedData = $groupedData->filter(fn($item) => $item['hasReport'])->values();
        }

        // 5. Paginate the grouped results
        $total = $groupedData->count();
        $page = (int) $request->get('page', 1);
        $paginatedData = $groupedData->forPage($page, $perPage)->values();

        return response()->json($service->cleanUtf8([
            'data'         => $paginatedData,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
            'total'        => $total,
            'from'         => $total > 0 ? (($page - 1) * $perPage) + 1 : 0,
            'to'           => min($page * $perPage, $total),
            'per_page'     => $perPage,
        ]));
    }

    /**
     * Display Completed Tasks for the Laboratory.
     */
    public function completedTasks(Request $request)
    {
        $user = auth()->user();
        $labId = $user?->lab_id;

        if ($request->wantsJson() || $request->ajax()) {
            $query = StaffordQualification::withoutGlobalScopes()
                ->when($labId, fn($q) => $q->where('lab_id', $labId))
                ->whereNotNull('tests')
                ->where('tests', '!=', '')
                ->where('tests', '!=', '[]')
                ->where('tests', '!=', 'null')
                ->orderBy('created_at', 'desc');

            if ($request->filled('sync_status') && $request->sync_status !== 'all') {
                $query->where('api_sync_status', $request->sync_status);
            }

            if ($request->filled('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            if ($request->filled('search')) {
                $term = trim($request->search);
                $query->where(function ($q) use ($term) {
                    $q->where('po_number', 'like', "%{$term}%")
                        ->orWhere('style_no', 'like', "%{$term}%")
                        ->orWhere('product_name', 'like', "%{$term}%")
                        ->orWhere('factory_name', 'like', "%{$term}%");
                });
            }

            $records = $query->get()
                ->filter(fn($record) => !empty($record->tests) && is_array($record->tests) && count($record->tests) > 0)
                ->values()
                ->map(function ($record) {
                return [
                    'id'               => $record->id,
                    'poNumber'         => $record->po_number,
                    'poDate'           => $record->po_date,
                    'buyer'            => $record->buyer,
                    'styleNo'          => $record->style_no,
                    'productStyle'     => $record->product_style ?: $record->style_no,
                    'labId'            => $record->lab_id,
                    'productName'      => $record->product_name,
                    'brandName'        => $record->brand_name,
                    'factoryName'      => $record->factory_name,
                    'factoryAddress'   => $record->factory_address,
                    'status'           => $record->status,
                    'certType'         => $record->cert_type,
                    'intendedUse'      => $record->intended_use,
                    'childrenProduct'  => $record->children_product,
                    'certNo'           => $record->cert_no,
                    'lastTestDate'     => $record->last_test_date,
                    'manufactureDate'  => $record->manufacture_date,
                    'lotNumber'        => $record->lot_number,
                    'tests'            => $record->tests ?: [],
                    'apiSyncStatus'    => $record->api_sync_status ?: 'pending',
                    'apiSyncMessage'   => $record->api_sync_message,
                    'createdAt'        => $record->created_at?->format('M d, Y H:i'),
                    'isSynced'         => ($record->api_sync_status === 'synced'),
                ];
            });

            return response()->json($records);
        }

        $testReportTypes = TestReportType::where('is_active', true)->orderBy('name')->get();
        $labs = Lab::where('is_active', true)->orderBy('name')->get();
        $userLabName = $user?->lab?->name ?? 'Laboratory';
        $citationMaster = $this->getCitationMaster();

        return view('lab.tasks.completed', compact('testReportTypes', 'labs', 'userLabName', 'citationMaster'));
    }

    /**
     * Build citation master dictionary.
     */
    private function getCitationMaster(): array
    {
        $testReportTypes = TestReportType::where('is_active', true)->orderBy('name')->get();
        return collect($testReportTypes)->mapWithKeys(function ($t) {
            $code = $t->standard_citation ?: $t->code;
            return [$code => ($t->standard_citation ? $t->standard_citation . ': ' : '') . $t->name . ' (' . $t->default_validity_days . 'd)'];
        })->union([
            '16 CFR 1610' => 'Standard for the Flammability of Clothing Textiles (365d)',
            '16 CFR 1615' => 'Standard for the Flammability of Children\'s Sleepwear (365d)',
            '16 CFR 1303' => 'Lead in Paint (730d)',
            '16 CFR 1500.48' => 'Technical Requirements for Determining a Sharp Point in Toys (365d)',
        ])->all();
    }

    /**
     * Update a qualification report submitted by the laboratory.
     * Enforces that synced records CANNOT be edited!
     */
    public function updateTask(Request $request, $id)
    {
        $qualification = StaffordQualification::withoutGlobalScopes()->findOrFail($id);

        // Strict lock rule: once synced no edits allowed
        if ($qualification->api_sync_status === 'synced') {
            return response()->json([
                'success' => false,
                'message' => 'This record has already been synced to CPSC/ERP. Edits are locked.'
            ], 403);
        }

        // Ensure labId and existing PO attributes are preserved if not provided in payload
        if (!$request->filled('labId')) {
            $request->merge(['labId' => $qualification->lab_id ?: auth()->user()?->lab_id]);
        }
        if (!$request->filled('styleNo') && !empty($qualification->style_no)) {
            $request->merge(['styleNo' => $qualification->style_no]);
        }
        if (!$request->filled('productStyle') && !empty($qualification->product_style)) {
            $request->merge(['productStyle' => $qualification->product_style]);
        }
        if (!$request->filled('buyer') && !empty($qualification->buyer)) {
            $request->merge(['buyer' => $qualification->buyer]);
        }
        if (!$request->filled('poDate') && !empty($qualification->po_date)) {
            $request->merge(['poDate' => $qualification->po_date]);
        }
        if (!$request->filled('factoryAddress') && !empty($qualification->factory_address)) {
            $request->merge(['factoryAddress' => $qualification->factory_address]);
        }

        // Normalise tests array: strip client-only flags and ensure required fields are present
        $tests = $request->input('tests', []);
        if (is_array($tests)) {
            $tests = array_map(function (array $test) {
                // Remove non-serialisable client-side flags
                unset($test['isUploadingFile']);
                // Fill in required server-side fields with safe defaults if missing
                $test['labName'] = !empty($test['labName']) ? $test['labName'] : 'Testing Laboratory';
                $test['labType'] = !empty($test['labType']) ? $test['labType'] : 'CPSC-Accepted';
                // Ensure citations is always an array
                if (!isset($test['citations']) || !is_array($test['citations'])) {
                    $test['citations'] = [];
                }
                return $test;
            }, $tests);
            $request->merge(['tests' => $tests]);
        }

        // Delegate to StaffordQualificationController update logic
        $staffordController = app(StaffordQualificationController::class);
        return $staffordController->update($request, $id);
    }

    /**
     * Upload a test report document file (PDF, image, Word).
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
}