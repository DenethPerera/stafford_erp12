<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravBranch;
use App\Models\Erav\EravProdOrder;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FactoryPortalController extends Controller
{
    /**
     * Get the active branch ID for the current authenticated user.
     * If user has mbranchlist_id, return it. If user is super_admin, allow branch selection from request or default to first branch.
     */
    protected function getBranchId(Request $request): ?int
    {
        $user = auth()->user();

        if ($user && $user->mbranchlist_id) {
            return (int) $user->mbranchlist_id;
        }

        // Fallback for testing or admin impersonation
        if ($request->has('branch_id')) {
            return (int) $request->input('branch_id');
        }

        return (int) EravBranch::value('id');
    }

    /**
     * Factory portal dashboard: allocated orders, KPI metrics.
     */
    public function index(Request $request)
    {
        $branchId = $this->getBranchId($request);
        $currentBranch = EravBranch::find($branchId);

        $search = $request->input('search');
        $season = $request->input('season');
        $status = $request->input('status');

        $query = EravProdOrder::with(['items', 'branch'])
            ->where('mbranchlist_id', $branchId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('prodorder_ref', 'like', "%{$search}%")
                  ->orWhere('buy_no', 'like', "%{$search}%")
                  ->orWhere('prodorder_id', $search);
            });
        }

        if ($season) {
            $query->where('prod_order_season', $season);
        }

        $orders = $query->orderByDesc('prodorder_id')->paginate(15);

        // Fetch created POs grouped by prodorder_id to support multiple POs per order
        $orderIds = $orders->pluck('prodorder_id')->toArray();
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->whereIn('prodorder_id', $orderIds)
            ->where('mbranchlist_id', $branchId)
            ->get()
            ->groupBy('prodorder_id');

        // Metrics
        $totalOrdersCount = EravProdOrder::where('mbranchlist_id', $branchId)->count();
        $posCreatedCount = PurchaseOrder::where('mbranchlist_id', $branchId)->count();
        $totalAllocatedPcs = (int) DB::table('prodorder_info')
            ->join('prodorder_list', 'prodorder_info.prodorder_id', '=', 'prodorder_list.prodorder_id')
            ->where('prodorder_list.mbranchlist_id', $branchId)
            ->sum('prodorder_info.prod_qty');

        // Seasons list
        $seasons = EravProdOrder::where('mbranchlist_id', $branchId)
            ->whereNotNull('prod_order_season')
            ->where('prod_order_season', '!=', '')
            ->distinct()
            ->pluck('prod_order_season');

        // All branches for admin switch
        $allBranches = auth()->user()?->isAdmin()
            ? EravBranch::whereNotNull('br_name')->orderBy('br_name')->get(['id', 'br_name', 'br_code'])
            : collect();

        return view('factory_portal.index', compact(
            'orders',
            'currentBranch',
            'branchId',
            'purchaseOrders',
            'totalOrdersCount',
            'posCreatedCount',
            'totalAllocatedPcs',
            'seasons',
            'allBranches',
            'search',
            'season',
            'status'
        ));
    }

    /**
     * Show single allocated order with line items, style costing suppliers, and PO creation.
     */
    public function show(Request $request, $id)
    {
        $branchId = $this->getBranchId($request);
        $order = EravProdOrder::with(['items', 'branch'])->findOrFail($id);

        // Security check: ensure order belongs to this factory
        if ($order->mbranchlist_id != $branchId && !auth()->user()?->isAdmin()) {
            abort(403, 'Unauthorized access to this allocated production order.');
        }

        // All POs created for this production order
        $purchaseOrders = PurchaseOrder::with(['items', 'supplier', 'creator'])
            ->where('prodorder_id', $id)
            ->where('mbranchlist_id', $branchId)
            ->orderByDesc('id')
            ->get();

        $existingPo = $purchaseOrders->first();

        // Fetch detailed line items joined with porder_pdf_info to retrieve item_styleno
        $lineItems = DB::table('prodorder_info')
            ->leftJoin('porder_pdf_info', 'prodorder_info.porder_pdf_info_id', '=', 'porder_pdf_info.id')
            ->where('prodorder_info.prodorder_id', $id)
            ->select(
                'prodorder_info.pack_no',
                'prodorder_info.prodorder_id',
                'prodorder_info.spec_pack_no',
                'prodorder_info.porder_pdf_info_id',
                'prodorder_info.upc_refno',
                'prodorder_info.color_code',
                'prodorder_info.tbl_style_color_desc',
                'prodorder_info.tbl_style_color_code',
                'prodorder_info.size_type',
                'prodorder_info.tbl_style_size_desc',
                'prodorder_info.prod_qty',
                'prodorder_info.vendor_unit_price',
                'porder_pdf_info.item_styleno',
                'porder_pdf_info.item_description'
            )
            ->orderBy('prodorder_info.pack_no')
            ->get();

        // Identify unique style numbers present in this order
        $styleNos = $lineItems->pluck('item_styleno')->filter()->unique()->values()->toArray();

        // Fetch active suppliers for these styles from prodstyle_list & prodstyle_cost
        $styleSupplierRows = DB::table('prodstyle_list')
            ->join('prodstyle_cost', 'prodstyle_cost.prodstyle_list_id', '=', 'prodstyle_list.id')
            ->join('mrmsupplier', 'mrmsupplier.supplierno', '=', 'prodstyle_cost.supplierno')
            ->whereIn('prodstyle_list.styleid', $styleNos)
            ->where('prodstyle_cost.qrycancel', 0)
            ->select(
                'prodstyle_list.styleid',
                'prodstyle_cost.supplierno',
                'prodstyle_cost.item_supprice',
                'prodstyle_cost.itemplacement',
                'prodstyle_cost.item_uom',
                'mrmsupplier.supname',
                'mrmsupplier.supregcode',
                'mrmsupplier.supcountry',
                'mrmsupplier.sup_contact_name'
            )
            ->get();

        // Map: styleid => [supplierno => supplierDetails]
        $styleSuppliersMap = [];
        // Map: supplierno => supplier aggregated info
        $suppliersMap = [];

        foreach ($styleSupplierRows as $row) {
            $sId = $row->styleid;
            $sNo = (int) $row->supplierno;

            if (!isset($styleSuppliersMap[$sId])) {
                $styleSuppliersMap[$sId] = [];
            }
            $styleSuppliersMap[$sId][$sNo] = $row;

            if (!isset($suppliersMap[$sNo])) {
                $suppliersMap[$sNo] = [
                    'supplierno'       => $sNo,
                    'supname'          => $row->supname,
                    'supregcode'       => $row->supregcode,
                    'supcountry'       => $row->supcountry,
                    'sup_contact_name' => $row->sup_contact_name,
                    'styles'           => [],
                    'pack_nos'         => [],
                ];
            }
            if (!in_array($sId, $suppliersMap[$sNo]['styles'])) {
                $suppliersMap[$sNo]['styles'][] = $sId;
            }
        }

        // Calculate ordered quantities per pack_no from active POs
        $orderedPacks = [];
        foreach ($purchaseOrders as $po) {
            if ($po->status === 'cancelled') {
                continue;
            }
            foreach ($po->items as $item) {
                $pNo = (int) $item->pack_no;
                if (!isset($orderedPacks[$pNo])) {
                    $orderedPacks[$pNo] = [
                        'total_ordered' => 0,
                        'pos'           => [],
                        'by_supplier'   => [],
                    ];
                }
                $orderedPacks[$pNo]['total_ordered'] += (int) $item->quantity;
                $orderedPacks[$pNo]['pos'][] = [
                    'id'            => $po->id,
                    'po_number'     => $po->po_number,
                    'supplierno'    => $po->supplierno,
                    'supplier_name' => $po->supplier?->supname,
                    'qty'           => (int) $item->quantity,
                ];
                $sNo = (int) $po->supplierno;
                $orderedPacks[$pNo]['by_supplier'][$sNo] = ($orderedPacks[$pNo]['by_supplier'][$sNo] ?? 0) + (int) $item->quantity;
            }
        }

        // Enrich line items with style suppliers and ordered/remaining quantities
        $enrichedLineItems = $lineItems->map(function ($item) use ($styleSuppliersMap, $orderedPacks, &$suppliersMap) {
            $pNo = (int) $item->pack_no;
            $style = $item->item_styleno;
            $supMap = $styleSuppliersMap[$style] ?? [];
            $supplierNos = array_keys($supMap);

            // Add pack_no to each supplier's list
            foreach ($supplierNos as $sNo) {
                if (isset($suppliersMap[$sNo]) && !in_array($pNo, $suppliersMap[$sNo]['pack_nos'])) {
                    $suppliersMap[$sNo]['pack_nos'][] = $pNo;
                }
            }

            $orderedInfo = $orderedPacks[$pNo] ?? ['total_ordered' => 0, 'pos' => [], 'by_supplier' => []];
            $baseQty = (int) ($item->prod_qty ?: 0);
            $totalOrdered = (int) $orderedInfo['total_ordered'];
            $remainingQty = max(0, $baseQty - $totalOrdered);

            $item->supplier_nos = $supplierNos;
            $item->supplier_names = array_map(fn($s) => $s->supname, array_values($supMap));
            $item->ordered_qty = $totalOrdered;
            $item->remaining_qty = $remainingQty;
            $item->ordered_pos = $orderedInfo['pos'];
            $item->is_fully_ordered = ($baseQty > 0 && $remainingQty === 0);

            return $item;
        });

        $suppliersList = collect(array_values($suppliersMap))->sortBy('supname')->values();

        // Metrics for summary cards
        $totalAllocatedUnits = (int) $enrichedLineItems->sum('prod_qty');
        $totalOrderedUnits = 0;
        foreach ($enrichedLineItems as $item) {
            $totalOrderedUnits += min((int) $item->prod_qty, (int) $item->ordered_qty);
        }
        $totalRemainingUnits = max(0, $totalAllocatedUnits - $totalOrderedUnits);
        $totalEstimatedValue = $enrichedLineItems->sum(function ($item) {
            return ($item->prod_qty ?: 0) * ($item->vendor_unit_price ?: 0);
        });

        $orderStats = [
            'total_units'     => $totalAllocatedUnits,
            'total_ordered'   => $totalOrderedUnits,
            'total_remaining' => $totalRemainingUnits,
            'total_amount'    => $totalEstimatedValue,
            'pos_count'       => $purchaseOrders->count(),
            'suppliers_count' => $suppliersList->count(),
        ];

        $totalUnits = $totalAllocatedUnits;

        return view('factory_portal.show', compact(
            'order',
            'branchId',
            'purchaseOrders',
            'existingPo',
            'enrichedLineItems',
            'suppliersList',
            'orderStats',
            'totalUnits'
        ));
    }

    /**
     * Generate / create Purchase Order for the selected supplier.
     */
    public function storePurchaseOrder(Request $request, $id)
    {
        $branchId = $this->getBranchId($request);
        $order = EravProdOrder::with(['items', 'branch'])->findOrFail($id);

        if ($order->mbranchlist_id != $branchId && !auth()->user()?->isAdmin()) {
            abort(403);
        }

        $request->validate([
            'supplierno'         => 'required|integer|exists:mrmsupplier,supplierno',
            'po_number'          => 'required|string|max:60|unique:purchase_orders,po_number',
            'order_date'         => 'required|date',
            'delivery_date'      => 'nullable|date',
            'wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'payment_terms'      => 'nullable|string|max:100',
            'shipping_method'    => 'nullable|string|max:50',
            'delivery_address'   => 'nullable|string',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.pack_no'    => 'required',
            'items.*.base_quantity'      => 'nullable|numeric|min:0',
            'items.*.wastage_percentage' => 'nullable|numeric|min:0|max:100',
            'items.*.quantity'           => 'required|numeric|min:0',
            'items.*.unit_price'         => 'required|numeric|min:0',
        ]);

        // Verify that the selected supplier is linked to this order's style cost sheets
        $validSupplierNos = DB::table('prodorder_info')
            ->join('porder_pdf_info', 'prodorder_info.porder_pdf_info_id', '=', 'porder_pdf_info.id')
            ->join('prodstyle_list', 'prodstyle_list.styleid', '=', 'porder_pdf_info.item_styleno')
            ->join('prodstyle_cost', 'prodstyle_cost.prodstyle_list_id', '=', 'prodstyle_list.id')
            ->where('prodorder_info.prodorder_id', $id)
            ->where('prodstyle_cost.qrycancel', 0)
            ->pluck('prodstyle_cost.supplierno')
            ->map(fn($v) => (int) $v)
            ->unique()
            ->toArray();

        if (!in_array((int) $request->supplierno, $validSupplierNos) && !auth()->user()?->isAdmin()) {
            return back()->withErrors(['supplierno' => 'Selected supplier is not linked to this production order\'s style cost sheets.'])->withInput();
        }

        // Filter items that have quantity > 0
        $itemsToOrder = [];
        foreach ($request->items as $itemInput) {
            $qty = (int) ($itemInput['quantity'] ?? 0);
            if ($qty > 0) {
                $itemsToOrder[] = $itemInput;
            }
        }

        if (empty($itemsToOrder)) {
            return back()->withErrors(['items' => 'Please include at least one item with quantity greater than zero.'])->withInput();
        }

        DB::beginTransaction();
        try {
            $baseTotalQty = 0;
            $totalQty = 0;
            $totalAmount = 0;

            foreach ($itemsToOrder as $itemInput) {
                $qty = (int) $itemInput['quantity'];
                $price = (float) $itemInput['unit_price'];
                $baseQty = isset($itemInput['base_quantity']) ? (int) $itemInput['base_quantity'] : $qty;
                $baseTotalQty += $baseQty;
                $totalQty += $qty;
                $totalAmount += ($qty * $price);
            }

            // Calculate effective overall wastage percentage
            $effectiveWastagePercentage = ($baseTotalQty > 0 && $totalQty >= $baseTotalQty)
                ? round((($totalQty - $baseTotalQty) / $baseTotalQty) * 100, 2)
                : max(0, (float) $request->input('wastage_percentage', 0));

            $po = PurchaseOrder::create([
                'po_number'          => trim($request->po_number),
                'prodorder_id'       => $id,
                'mbranchlist_id'     => $branchId,
                'supplierno'         => $request->supplierno,
                'order_date'         => $request->order_date,
                'delivery_date'      => $request->delivery_date ?: $order->proddispatch_date,
                'season'             => $order->prod_order_season,
                'base_total_qty'     => $baseTotalQty,
                'wastage_percentage' => $effectiveWastagePercentage,
                'total_qty'          => $totalQty,
                'total_amount'       => $totalAmount,
                'currency'           => 'USD',
                'payment_terms'      => $request->payment_terms ?: ($order->pay_term ?: 'Net 30'),
                'shipping_method'    => $request->shipping_method ?: ($order->ship_method ?: 'Sea'),
                'fob_code'           => $order->fob_code ?: 'FOB',
                'delivery_address'   => $request->delivery_address ?: ($order->branch?->br_addr ?: ''),
                'customer_name'      => $order->buy_no ?: 'Stafford ERP',
                'status'             => 'issued',
                'created_by'         => auth()->id(),
                'notes'              => $request->notes,
            ]);

            // Save line items
            $prodItemsMap = $order->items->keyBy('pack_no');
            foreach ($itemsToOrder as $itemInput) {
                $origItem = $prodItemsMap->get($itemInput['pack_no']);
                $qty = (int) $itemInput['quantity'];
                $price = (float) $itemInput['unit_price'];
                $baseQty = isset($itemInput['base_quantity']) ? (int) $itemInput['base_quantity'] : ($origItem?->prod_qty ?: $qty);
                $wastageQty = max(0, $qty - $baseQty);
                $itemWastagePct = isset($itemInput['wastage_percentage']) 
                    ? (float) $itemInput['wastage_percentage'] 
                    : ($baseQty > 0 ? round(($wastageQty / $baseQty) * 100, 2) : 0);

                PurchaseOrderItem::create([
                    'purchase_order_id'  => $po->id,
                    'pack_no'            => $itemInput['pack_no'],
                    'upc_refno'          => $origItem?->upc_refno ?: null,
                    'color_code'         => $origItem?->tbl_style_color_code ?: null,
                    'color_desc'         => $origItem?->tbl_style_color_desc ?: null,
                    'size_desc'          => $origItem?->tbl_style_size_desc ?: null,
                    'base_quantity'      => $baseQty,
                    'wastage_percentage' => $itemWastagePct,
                    'wastage_qty'        => $wastageQty,
                    'quantity'           => $qty,
                    'unit_price'         => $price,
                    'total_amount'       => ($qty * $price),
                ]);
            }

            $supplierName = DB::table('mrmsupplier')->where('supplierno', $request->supplierno)->value('supname') ?: 'Supplier #' . $request->supplierno;

            DB::commit();

            return redirect()->route('factory-portal.show', ['id' => $id, 'branch_id' => $branchId])
                ->with('success', "Purchase Order {$po->po_number} for {$supplierName} generated successfully! You can now create additional POs for other suppliers.");

        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create Purchase Order: ' . $e->getMessage()])->withInput();
        }
    }
}
