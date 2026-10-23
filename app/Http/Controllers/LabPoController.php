<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravProdOrder;
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
            $styles = EravPurchaseOrderItem::whereNotNull('item_styleno')
                ->where('item_styleno', '!=', '')
                ->selectRaw('DISTINCT TRIM(item_styleno) as style')
                ->orderBy('style')
                ->pluck('style')
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            $styles = collect();
        }

        // Distinct PO numbers for the filter (from prodorder_list.prodorder_ref)
        try {
            $poNumbers = EravProdOrder::whereNotNull('prodorder_ref')
                ->where('prodorder_ref', '!=', '')
                ->where('prodorder_ref', '!=', '-')
                ->where('isoffline', 0)
                ->whereHas('purchaseOrder.items')
                ->distinct()
                ->orderBy('prodorder_ref')
                ->pluck('prodorder_ref')
                ->filter()
                ->values();
        } catch (\Throwable $e) {
            $poNumbers = collect();
        }

        $testReportTypes = TestReportType::where('is_active', true)->orderBy('name')->get();
        $labs = Lab::where('is_active', true)->orderBy('name')->get();
        $userLabName = $lab?->name ?? 'Laboratory';
        $citationMaster = $this->getCitationMaster();

        return view('lab.pos.index', compact('styles', 'poNumbers', 'testReportTypes', 'labs', 'userLabName', 'citationMaster'));
    }

    /**
     * AJAX endpoint returning paginated ERP Purchase Orders grouped by:
     * 1 Style + 1 Season + 1 Factory = 1 Consolidated Test Report row.
     * All item variations (colors, sizes) are collapsed into this single line.
     */
    public function poData(Request $request, ApiIntegrationService $service)
    {
        $user = auth()->user();
        $labId = $user?->lab_id;

        // 1. Get all submitted test reports across all labs to check status
        $existingQualifications = StaffordQualification::withoutGlobalScopes()
            ->with('lab:id,name')
            ->whereNotNull('tests')
            ->where('tests', '!=', '')
            ->where('tests', '!=', '[]')
            ->where('tests', '!=', 'null')
            ->get(['id', 'po_number', 'style_no', 'season', 'factory_name', 'lab_id', 'status', 'api_sync_status', 'tests', 'created_at'])
            ->filter(fn($q) => !empty($q->tests) && is_array($q->tests) && count($q->tests) > 0);

        $query = EravProdOrder::query()
            ->whereNotNull('prodorder_ref')
            ->where('prodorder_ref', '!=', '')
            ->where('prodorder_ref', '!=', '-')
            ->where('isoffline', 0)
            ->whereHas('purchaseOrder.items');

        // Filter by Style (exact trimmed match)
        $selectedStyle = $request->filled('style') ? trim($request->style) : null;
        if (!empty($selectedStyle)) {
            $query->whereHas('purchaseOrder.items', function ($q) use ($selectedStyle) {
                $q->whereRaw('TRIM(item_styleno) = ?', [$selectedStyle]);
            });
        }

        // Filter and search by Purchase Order Reference Number (prodorder_ref)
        $selectedPo = $request->filled('po_number') ? trim($request->po_number) : null;
        if (!empty($selectedPo)) {
            $query->where('prodorder_ref', 'like', "%{$selectedPo}%");
        }

        // Filter by Search Query (PO Ref, Customer/Buyer, Factory/Branch, Style No, or Description)
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('prodorder_ref', 'like', "%{$term}%")
                    ->orWhereHas('branch', function ($br) use ($term) {
                        $br->where('br_name', 'like', "%{$term}%")
                            ->orWhere('br_code', 'like', "%{$term}%");
                    })
                    ->orWhereHas('purchaseOrder', function ($poQuery) use ($term) {
                        $poQuery->where('customer_name', 'like', "%{$term}%")
                            ->orWhereHas('items', function ($sub) use ($term) {
                                $sub->where('item_styleno', 'like', "%{$term}%")
                                    ->orWhere('item_description', 'like', "%{$term}%");
                            });
                    });
            });
        }

        $perPage = (int) $request->get('per_page', 15);
        if ($perPage <= 0 || $perPage > 100) {
            $perPage = 15;
        }

        // Fetch matching POs with line items and production branch
        if ($selectedStyle || $selectedPo || $request->filled('search')) {
            $rawProdOrders = $query->with(['purchaseOrder.items', 'branch'])
                ->orderBy('prodorder_id', 'desc')
                ->get();
        } else {
            $rawProdOrders = $query->with(['purchaseOrder.items', 'branch'])
                ->orderBy('prodorder_id', 'desc')
                ->take(300)
                ->get();
        }

        // 2. Map POs and group item variations (color/size) per Style ID
        $mappedItems = $rawProdOrders->flatMap(function ($prodOrder) use ($selectedStyle) {
            $po = $prodOrder->purchaseOrder;
            if (!$po || !$po->items) {
                return [];
            }

            $branch = $prodOrder->branch;
            $factoryName = $branch?->br_name ? trim($branch->br_name) : ($branch?->br_code ?: 'Branch N/A');

            $season = $prodOrder->prod_order_season ?: '';

            $poRef = (string) $prodOrder->prodorder_ref;
            $poDate = $prodOrder->prodorder_date ? (string) $prodOrder->prodorder_date : ($po->porder_date ? (string) $po->porder_date : '');

            // Group line items in this PO by their Style ID
            $groupedByStyle = $po->items->groupBy(fn($i) => trim($i->item_styleno ?: ''))
                ->filter(fn($items, $s) => !empty($s));

            // If a specific Style ID is filtered, only retain that style
            if (!empty($selectedStyle)) {
                $groupedByStyle = $groupedByStyle->filter(fn($items, $s) => strcasecmp($s, $selectedStyle) === 0);
            }

            // Map each Style in this PO to a consolidated record collapsing color/size variations
            return $groupedByStyle->map(function ($styleItems, $styleNo) use ($prodOrder, $po, $poRef, $poDate, $factoryName, $season) {
                $firstItem = $styleItems->first();
                $totalQty = (int) $styleItems->sum('item_order_qty');

                $colors = $styleItems->pluck('item_colordesc')
                    ->map(fn($c) => trim($c))
                    ->filter(fn($c) => !empty($c) && $c !== '-')
                    ->unique()
                    ->values()
                    ->all();

                $sizes = $styleItems->pluck('size_code')
                    ->map(fn($s) => trim($s))
                    ->filter(fn($s) => !empty($s) && $s !== '-')
                    ->unique()
                    ->values()
                    ->all();

                $variations = $styleItems->map(function ($item) {
                    return [
                        'id'       => $item->id,
                        'color'    => $item->item_colordesc ?: ($item->item_colorcode ?: '-'),
                        'size'     => $item->size_code ?: '-',
                        'qty'      => (int) $item->item_order_qty,
                        'itemCode' => $item->item_code ?: '',
                    ];
                })->values()->all();

                return [
                    'id'              => $prodOrder->prodorder_id . '_' . md5($styleNo),
                    'poId'            => $prodOrder->prodorder_id,
                    'poNumber'        => $poRef,
                    'poDate'          => $poDate,
                    'buyer'           => $po->customer_name ?: 'Stafford Buyer',
                    'factoryName'     => $factoryName,
                    'styleNo'         => (string) $styleNo,
                    'season'          => $season,
                    'productName'     => $firstItem?->item_description ?: '',
                    'totalQty'        => $totalQty,
                    'variationsCount' => $styleItems->count(),
                    'colors'          => $colors,
                    'sizes'           => $sizes,
                    'variations'      => $variations,
                ];
            })->values();
        });

        // 3. Core Rule Consolidation: 1 Style + 1 Season + 1 Factory = 1 Consolidated Row
        $groupedData = $mappedItems->groupBy(function ($item) {
            $factory = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['factoryName'] ?? '')));
            $style   = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['styleNo'] ?? '')));
            $season  = mb_strtolower(preg_replace('/\s+/', ' ', trim($item['season'] ?? '')));
            return $factory . '___' . $style . '___' . $season;
        })->map(function ($group) use ($existingQualifications, $labId) {
            $first = $group->first();
            $allPoNumbers = $group->pluck('poNumber')->unique()->values()->all();
            $allVariations = $group->pluck('variations')->flatten(1)->values()->all();
            $totalVariationsCount = $group->sum('variationsCount');
            $totalGroupQty = $group->sum('totalQty');
            $allColors = $group->pluck('colors')->flatten()->unique()->values()->all();
            $allSizes = $group->pluck('sizes')->flatten()->unique()->values()->all();

            // Check if ANY qualification matches this Style + Factory + Season or PO group
            $styleClean = mb_strtolower(trim($first['styleNo']));
            $factoryClean = mb_strtolower(trim($first['factoryName']));
            $seasonClean = mb_strtolower(trim($first['season']));

            $existing = $existingQualifications->first(function ($q) use ($styleClean, $factoryClean, $seasonClean, $allPoNumbers) {
                $qStyle = mb_strtolower(trim($q->style_no ?? ''));
                $qFactory = mb_strtolower(trim($q->factory_name ?? ''));
                $qSeason = mb_strtolower(trim($q->season ?? ''));
                $qPo = (string)($q->po_number ?? '');

                // A qualification must strictly match the style of this group if style is defined
                if (!empty($styleClean)) {
                    if (empty($qStyle) || $qStyle !== $styleClean) {
                        return false;
                    }
                }

                // Match 1: By Style + Season + Factory
                if (!empty($qStyle) && $qStyle === $styleClean) {
                    if (empty($seasonClean) || empty($qSeason) || $seasonClean === $qSeason) {
                        if (empty($factoryClean) || empty($qFactory) || str_contains($factoryClean, $qFactory) || str_contains($qFactory, $factoryClean)) {
                            return true;
                        }
                    }
                }

                // Match 2: By PO Number within the same Style
                if (!empty($qPo) && in_array($qPo, $allPoNumbers, true)) {
                    return true;
                }

                return false;
            });

            $hasReport = !empty($existing);
            $isMyLab = $existing ? ($labId && $existing->lab_id == $labId) : false;

            return array_merge($first, [
                'allPoNumbers'    => $allPoNumbers,
                'otherPosCount'   => count($allPoNumbers) - 1,
                'totalQty'        => $totalGroupQty,
                'variationsCount' => $totalVariationsCount,
                'colors'          => $allColors,
                'sizes'           => $allSizes,
                'variations'      => $allVariations,
                'hasReport'       => $hasReport,
                'isMyLab'         => $isMyLab,
                'submittingLab'   => $existing?->lab?->name,
                'reportId'        => $existing?->id,
                'reportStatus'    => $existing?->status,
                'apiSyncStatus'   => $existing?->api_sync_status,
            ]);
        })->values();

        // 4. Paginate the grouped results
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
                    'rejectionReason'  => $record->rejection_reason ?? null,
                    'rejectedAt'       => $record->rejected_at ? $record->rejected_at->format('M d, Y H:i') : null,
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

        // When revising a rejected task, reset status to Complete
        if ($qualification->status === 'Rejected' || $request->input('status') === 'Rejected') {
            $request->merge(['status' => 'Complete', 'rejectionReason' => null]);
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