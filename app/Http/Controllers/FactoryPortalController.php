<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravBranch;
use App\Models\Erav\EravProdOrder;
use App\Models\Erav\EravProdOrderInfo;
use App\Models\Erav\EravProdShipment;
use App\Models\Erav\EravProdShipmentItem;
use App\Models\Erav\EravProductStyle;
use App\Models\Erav\EravPurchaseOrderItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Shipment;
use App\Models\ShipmentOrderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FactoryPortalController extends Controller
{

    protected function getBranchId(Request $request): ?int
    {
        $user = auth()->user();

        if ($user && $user->mbranchlist_id) {
            return (int) $user->mbranchlist_id;
        }

        if ($request->has('branch_id')) {
            return (int) $request->input('branch_id');
        }

        return (int) EravBranch::value('id');
    }


      //Factory portal dashboard: allocated orders, KPI metrics.
    public function index(Request $request)
    {
        if (auth()->user()?->isAdmin() && !auth()->user()?->isFactory()) {
            return redirect()->route('purchase-orders.index');
        }

        $branchId = $this->getBranchId($request);
        $currentBranch = EravBranch::find($branchId);

        $search = $request->input('search');
        $season = $request->input('season');
        $status = $request->input('status');

        $query = EravProdOrder::with(['items', 'branch'])
            ->where('mbranchlist_id', $branchId)
            ->where('isoffline', 0);

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
        $totalOrdersCount = EravProdOrder::where('mbranchlist_id', $branchId)->where('isoffline', 0)->count();
        $posCreatedCount = PurchaseOrder::where('mbranchlist_id', $branchId)->count();
        $totalAllocatedPcs = (int) DB::table('prodorder_info')
            ->join('prodorder_list', 'prodorder_info.prodorder_id', '=', 'prodorder_list.prodorder_id')
            ->where('prodorder_list.mbranchlist_id', $branchId)
            ->where('prodorder_list.isoffline', 0)
            ->sum('prodorder_info.prod_qty');

        // Seasons list
        $seasons = EravProdOrder::where('mbranchlist_id', $branchId)
            ->where('isoffline', 0)
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


     //Show single allocated order with line items, style costing suppliers, and PO creation.

    public function show(Request $request, $id)
    {
        $branchId = $this->getBranchId($request);
        $order = EravProdOrder::with(['items', 'branch'])
            ->where('isoffline', 0)
            ->findOrFail($id);

        //ensure order belongs to this factory
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

        $styleSuppliersMap = [];
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


     //create Purchase Order for the selected supplier.

    public function storePurchaseOrder(Request $request, $id)
    {
        // Admins CANNOT create purchase orders.
        if (auth()->user()?->isAdmin() || !auth()->user()?->isFactory()) {
            abort(403, 'Admins cannot create purchase orders. Only factory accounts are authorized to generate Purchase Orders.');
        }

        $branchId = $this->getBranchId($request);
        $order = EravProdOrder::with(['items', 'branch'])
            ->where('isoffline', 0)
            ->findOrFail($id);

        if ($order->mbranchlist_id != $branchId) {
            abort(403, 'Unauthorized access to this allocated production order.');
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

        if (!in_array((int) $request->supplierno, $validSupplierNos)) {
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

    public function shipmentDetails(Request $request)
    {
        // Use only the factory assigned to the logged-in user.
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        $validated = $request->validate([
            'shipment_search' => 'nullable|string|max:255',
        ]);
        $shipmentSearch = trim($validated['shipment_search'] ?? '');

        // ERP tables use the existing erav database connection.
        $erpDatabase = DB::connection('erav')->getDatabaseName();

        $shipmentQuery = DB::table('shipment_order_factories as link')
            ->join('shipments as shipment', 'shipment.id', '=', 'link.shipment_id')
            ->join($erpDatabase . '.porder_pdf_list as po', 'po.id', '=', 'link.purchase_order_id')
            ->join($erpDatabase . '.mbranchlist as factory', 'factory.id', '=', 'link.factory_id')
            ->where('link.factory_id', $factoryId)
            ->select(
                'link.id as assignment_id',
                'shipment.shipment_id as shipment_number',
                'factory.br_name as factory_name',
                'po.pdf_po_refno as po_number'
            );

        // A blank search shows all shipments for this factory.
        if ($shipmentSearch !== '') {
            $shipmentQuery->where('shipment.shipment_id', 'like', '%' . $shipmentSearch . '%');
        }

        $shipmentRows = $shipmentQuery->orderByDesc('shipment.id')
            ->orderBy('link.purchase_order_id')
            ->paginate(15)
            ->withQueryString();

        return view('factory_portal.shipment_details', compact('shipmentRows', 'shipmentSearch'));
    }

    public function importExport(Request $request)
    {
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        // Use the same assigned purchase orders as the Create Shipment page.
        $purchaseOrderIds = DB::table('shipment_order_factories')
            ->where('factory_id', $factoryId)
            ->pluck('purchase_order_id');

        $purchaseOrders = DB::connection('erav')->table('porder_pdf_list')
            ->whereIn('id', $purchaseOrderIds)
            ->orderBy('pdf_po_refno')
            ->pluck('pdf_po_refno', 'id');

        return view('factory_portal.import_export', compact('purchaseOrders'));
    }

    public function createShipment(Request $request)
    {
        return $this->shipmentForm($request);
    }

    // Create and Edit share the same form and dropdowns.
    private function shipmentForm(Request $request, ?EravProdShipment $editingDelivery = null)
    {
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        $validated = $request->validate([
            'assignment_id' => 'nullable|integer|min:1',
        ]);

        $formData = [];
        if ($editingDelivery) {
            $assignment = $this->deliveryAssignment($editingDelivery);
            if (!$assignment || $assignment->shipment_number !== $editingDelivery->shipment_refno) {
                return redirect()->route('create-shipment.index', ['history' => 1])
                    ->with('error', 'The original shipment assignment is no longer available.');
            }
            $validated['assignment_id'] = $assignment->id;
            $item = $editingDelivery->items()->where('qrycancel', 0)->firstOrFail();
            $formData = [
                'production_order_id' => $editingDelivery->prodorder_list_prodorder_id,
                'prepared_date' => $editingDelivery->shiporder_docdate,
                'remarks' => $editingDelivery->shipment_moreinfo,
                'container_number' => $editingDelivery->ship_container_no,
                'vessel' => $editingDelivery->vessel_code,
                'vendor_invoice_number' => $editingDelivery->shipment_invno,
                'stafford_invoice_number' => $editingDelivery->stafford_invno,
                'ship_via' => strtolower($editingDelivery->ship_method),
                'etd' => $editingDelivery->plan_etd,
                'pl_label' => $editingDelivery->pl_labels,
                'approve_shipment' => $editingDelivery->qryapproved,
                'production_item_id' => $item->prodorder_info_packno,
                'ship_quantity' => $item->ship_qty,
                'carton_dimension_id' => $item->idtbl_carton_dimension,
                'pieces_per_carton' => $item->pcs_per_carton,
                'carton_length' => $item->carton_length,
                'carton_width' => $item->carton_width,
                'carton_height' => $item->carton_height,
                'total_net_weight' => $item->pack_netwgt,
                'total_gross_weight' => $item->pack_grossweight,
                'odd_net_weight' => $item->odditems_netwgt,
                'odd_gross_weight' => $item->oddpack_grossweight,
                'delivery_address_id' => $item->porderctndeliaddrlist_id,
            ];
        }

        $factoryName = EravBranch::where('id', $factoryId)->value('br_name');

        // Show only purchase orders assigned to this factory in saved shipments.
        $purchaseOrderIds = DB::table('shipment_order_factories')
            ->where('factory_id', $factoryId)
            ->pluck('purchase_order_id');

        $purchaseOrders = DB::connection('erav')->table('porder_pdf_list')
            ->whereIn('id', $purchaseOrderIds)
            ->orderBy('pdf_po_refno')
            ->pluck('pdf_po_refno', 'id');

        // Opening from the menu starts with empty shipment information.
        $customerName = '';
        $assignmentId = $validated['assignment_id'] ?? null;
        $productionOrders = collect();
        $orderItems = collect();
        $cartonDimensions = collect();
        $deliveryAddresses = collect();
        $selectedProductionOrderId = null;
        $shipmentNumber = '';
        $selectedPurchaseOrderId = null;

        if (!empty($validated['assignment_id'])) {
            // Read the selected row only when it belongs to this factory.
            $assignment = DB::table('shipment_order_factories as link')
                ->join('shipments as shipment', 'shipment.id', '=', 'link.shipment_id')
                ->where('link.id', $validated['assignment_id'])
                ->where('link.factory_id', $factoryId)
                ->select('link.purchase_order_id', 'shipment.shipment_id as shipment_number')
                ->first();

            abort_unless($assignment, 404);

            $selectedPurchaseOrderId = $assignment->purchase_order_id;
            $shipmentNumber = $assignment->shipment_number;

            $purchaseOrder = DB::connection('erav')->table('porder_pdf_list')
                ->where('id', $selectedPurchaseOrderId)
                ->first(['customer_id']);

            abort_unless($purchaseOrder, 404);

            $customerName = DB::connection('erav')->table('salescustomerlist')
                ->where('customer_id', $purchaseOrder->customer_id)
                ->value('customer_name') ?? '';

            // A PO can have more than one production order at the same factory.
            $productionOrders = EravProdOrder::where('porder_pdf_list_id', $selectedPurchaseOrderId)
                ->where('mbranchlist_id', $factoryId)
                ->where('isoffline', 0)
                ->whereNotNull('prodorder_ref')
                ->where('prodorder_ref', '!=', '')
                ->orderBy('prodorder_ref')
                ->get(['prodorder_id', 'prodorder_ref']);

            if ($productionOrders->count() === 1) {
                $selectedProductionOrderId = $productionOrders->first()->prodorder_id;
            }

            // Load actual style/color/size items for the checked production orders.
            $orderItems = EravProdOrderInfo::query()
                ->join('porder_pdf_info as po_item', 'po_item.id', '=', 'prodorder_info.porder_pdf_info_id')
                ->join('prodstyle_list as style', 'style.styleid', '=', 'po_item.item_styleno')
                ->whereIn('prodorder_info.prodorder_id', $productionOrders->pluck('prodorder_id'))
                ->where('po_item.porder_pdf_list_id', $selectedPurchaseOrderId)
                ->where('prodorder_info.qrycancel', 0)
                ->where('po_item.qrycancel', 0)
                ->where('style.isoffline', 0)
                ->select('prodorder_info.*', 'po_item.item_styleno', 'po_item.item_colorcode', 'style.id as style_id')
                ->orderBy('prodorder_info.pack_no')
                ->get();

            // Cartons and addresses are lookups, so they do not need new models.
            $cartonDimensions = DB::connection('erav')->table('tbl_carton_dimension')
                ->whereIn('prodstyle_list_id', $orderItems->pluck('style_id'))
                ->where('status', 1)
                ->orderBy('styleid')
                ->orderBy('tbl_style_size_desc')
                ->get();

            $deliveryAddresses = DB::connection('erav')->table('porderctndeliaddrlist as address')
                ->join('salesotheraddrlist as destination', 'destination.custdest_id', '=', 'address.salesotheraddr_custdest_id')
                ->where('address.porder_pdf_list_id', $selectedPurchaseOrderId)
                ->where('address.qrycancel', 0)
                ->where('destination.qrycancel', 0)
                ->where('destination.customer_id', $purchaseOrder->customer_id)
                ->select('address.id', 'address.item_styleno', 'address.item_colorcode', 'address.item_colordesc',
                    'destination.custdest_name', 'destination.custdest_addr')
                ->orderBy('destination.custdest_name')
                ->get();
        }

        return view('factory_portal.create_shipment', compact(
            'factoryName', 'purchaseOrders', 'customerName', 'productionOrders',
            'shipmentNumber', 'selectedPurchaseOrderId', 'assignmentId', 'selectedProductionOrderId',
            'orderItems', 'cartonDimensions', 'deliveryAddresses', 'editingDelivery', 'formData'
        ));
    }

    // The saved Shipment Number, PO and factory determine the assignment on Edit.
    private function deliveryAssignment(EravProdShipment $delivery)
    {
        return DB::table('shipment_order_factories as link')
            ->join('shipments as shipment', 'shipment.id', '=', 'link.shipment_id')
            ->where('shipment.shipment_id', $delivery->shipment_refno)
            ->where('link.purchase_order_id', $delivery->porder_pdf_list_id)
            ->where('link.factory_id', $delivery->mbranchlist_id)
            ->select('link.id', 'shipment.shipment_id as shipment_number')
            ->first();
    }

    private function factoryDelivery(Request $request, string $id): EravProdShipment
    {
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        return EravProdShipment::where('mbranchlist_id', $factoryId)->findOrFail($id);
    }

    private function deliveryChangeError(EravProdShipment $delivery, bool $editing = false): ?string
    {
        if ($delivery->qryfinalized || $delivery->import_id > 0 || $delivery->imports_container_number_id > 0) {
            return 'Finalized deliveries and deliveries linked to shipping records cannot be edited or deleted.';
        }

        if ($editing && $delivery->items()->where('qrycancel', 0)->count() !== 1) {
            return 'This form edits deliveries with one active order item. This delivery cannot be edited here.';
        }

        return null;
    }

    public function editShipment(Request $request, string $id)
    {
        $delivery = $this->factoryDelivery($request, $id);
        $error = $this->deliveryChangeError($delivery, true);
        if ($error) {
            return redirect()->route('create-shipment.index', ['history' => 1])->with('error', $error);
        }

        return $this->shipmentForm($request, $delivery);
    }

    public function updateShipment(Request $request, string $id)
    {
        $delivery = $this->factoryDelivery($request, $id);
        $error = $this->deliveryChangeError($delivery, true);
        if ($error) {
            return redirect()->route('create-shipment.index', ['history' => 1])->with('error', $error);
        }

        $assignment = $this->deliveryAssignment($delivery);
        if (!$assignment || $assignment->shipment_number !== $delivery->shipment_refno) {
            return redirect()->route('create-shipment.index', ['history' => 1])
                ->with('error', 'The original shipment assignment is no longer available.');
        }
        $request->merge(['assignment_id' => $assignment->id]);

        return $this->saveShipment($request, $delivery);
    }

    public function destroyShipment(Request $request, string $id)
    {
        $delivery = $this->factoryDelivery($request, $id);
        $error = $this->deliveryChangeError($delivery);
        if ($error) {
            return redirect()->route('create-shipment.index', ['history' => 1])->with('error', $error);
        }

        // Delete only this delivery and its items. Shared addresses stay available.
        $error = DB::connection('erav')->transaction(function () use ($delivery) {
            $delivery = EravProdShipment::where('mbranchlist_id', $delivery->mbranchlist_id)
                ->lockForUpdate()->findOrFail($delivery->id);
            $error = $this->deliveryChangeError($delivery);
            if ($error) {
                return $error;
            }
            $delivery->items()->delete();
            $delivery->delete();

            return null;
        });
        if ($error) {
            return redirect()->route('create-shipment.index', ['history' => 1])->with('error', $error);
        }

        return redirect()->route('create-shipment.index', ['history' => 1])
            ->with('success', 'Delivery deleted successfully.');
    }

    public function shipmentHistory(Request $request)
    {
        // Never take the factory ID from the URL or form.
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        $factoryName = EravBranch::where('id', $factoryId)->value('br_name');

        // Include older and inactive deliveries as well as newly saved ones.
        $historyShipments = DB::connection('erav')->table('prodshipment_list as shipment')
            ->leftJoin('porder_pdf_list as po', 'po.id', '=', 'shipment.porder_pdf_list_id')
            ->leftJoin('salescustomerlist as customer', 'customer.customer_id', '=', 'po.customer_id')
            ->where('shipment.mbranchlist_id', $factoryId)
            ->select('shipment.*', 'po.pdf_po_refno', 'customer.customer_name')
            ->orderBy('shipment.id', 'desc')
            ->get();

        // Load all items once, then put each one under its own delivery.
        // Left joins keep saved items visible if a lookup has been removed.
        $historyItems = DB::connection('erav')->table('prodshipment_info as item')
            ->leftJoin('prodstyle_list as style', 'style.id', '=', 'item.prodstyle_list_id')
            ->leftJoin('prodorder_info as ordered', 'ordered.pack_no', '=', 'item.prodorder_info_packno')
            ->leftJoin('porderctndeliaddrlist as address', 'address.id', '=', 'item.porderctndeliaddrlist_id')
            ->leftJoin('salesotheraddrlist as destination', 'destination.custdest_id', '=', 'address.salesotheraddr_custdest_id')
            ->whereIn('item.prodshipment_list_id', $historyShipments->pluck('id'))
            ->select('item.*', 'style.styleid as style_code', 'ordered.prod_qty as order_quantity',
                'destination.custdest_name', 'destination.custdest_addr')
            ->orderBy('item.id')
            ->get()
            ->groupBy('prodshipment_list_id');

        return view('factory_portal.partials.shipment_history', compact(
            'factoryName', 'historyShipments', 'historyItems'
        ));
    }

    public function storeShipment(Request $request)
    {
        return $this->saveShipment($request);
    }

    // Keep the same validation and packing calculations for Create and Update.
    private function saveShipment(Request $request, ?EravProdShipment $editingDelivery = null)
    {
        // Always use the logged-in user's factory.
        $factoryId = $request->user()->mbranchlist_id;
        abort_unless($factoryId, 403, 'Your account is not linked to a factory.');

        // Optional odd-carton weights start at zero, including for validation.
        $request->merge([
            'odd_net_weight' => $request->input('odd_net_weight') ?? 0,
            'odd_gross_weight' => $request->input('odd_gross_weight') ?? 0,
            'address_mode' => $request->input('address_mode', 'existing'),
        ]);

        // Limits match the columns in prodshipment_list.
        $data = $request->validate([
            'assignment_id' => 'required|integer|min:1',
            'production_order_id' => 'required|integer|min:1',
            'production_item_id' => 'required|integer|min:1',
            'carton_dimension_id' => 'required|integer|min:1',
            'address_mode' => 'required|in:existing,manual',
            'delivery_address_id' => 'exclude_if:address_mode,manual|required|integer|min:1',
            'manual_address' => 'exclude_unless:address_mode,manual|required|string|max:200',
            'manual_address_name' => 'exclude_unless:address_mode,manual|nullable|string|max:50',
            'ship_quantity' => 'required|integer|min:1|max:2147483647',
            'pieces_per_carton' => 'required|integer|min:1|max:2147483647',
            'total_net_weight' => 'required|numeric|min:0',
            'total_gross_weight' => 'required|numeric|min:0|gte:total_net_weight',
            'odd_net_weight' => 'required|numeric|min:0|lte:total_net_weight',
            'odd_gross_weight' => 'required|numeric|min:0|gte:odd_net_weight|lte:total_gross_weight',
            'carton_length' => 'required|numeric|gt:0',
            'carton_width' => 'required|numeric|gt:0',
            'carton_height' => 'required|numeric|gt:0',
            'prepared_date' => 'required|date_format:Y-m-d',
            'remarks' => 'nullable|string|max:100',
            'container_number' => 'nullable|string|max:50',
            'vessel' => 'nullable|string|max:25',
            'vendor_invoice_number' => 'required|string|max:40',
            'stafford_invoice_number' => 'nullable|string|max:40',
            'ship_via' => 'required|in:courier,sea,air,road',
            'etd' => 'nullable|date_format:Y-m-d',
            'pl_label' => 'nullable|string|max:30',
            'approve_shipment' => 'nullable|boolean',
        ]);

        $assignment = ShipmentOrderFactory::where('id', $data['assignment_id'])
            ->where('factory_id', $factoryId)
            ->firstOrFail();

        $shipment = Shipment::findOrFail($assignment->shipment_id);

        // The order must belong to both the selected PO and this factory.
        $productionOrder = EravProdOrder::where('prodorder_id', $data['production_order_id'])
            ->where('porder_pdf_list_id', $assignment->purchase_order_id)
            ->where('mbranchlist_id', $factoryId)
            ->where('isoffline', 0)
            ->whereNotNull('prodorder_ref')
            ->where('prodorder_ref', '!=', '')
            ->firstOrFail();

        // The current database column allows 15 characters.
        if (mb_strlen($shipment->shipment_id) > 15) {
            return back()->withErrors([
                'shipment_number' => 'This shipment number exceeds the current 15-character database limit.',
            ])->withInput();
        }

        $productionItem = EravProdOrderInfo::where('pack_no', $data['production_item_id'])
            ->where('prodorder_id', $productionOrder->prodorder_id)
            ->where('qrycancel', 0)
            ->firstOrFail();

        $purchaseOrderItem = EravPurchaseOrderItem::where('id', $productionItem->porder_pdf_info_id)
            ->where('porder_pdf_list_id', $assignment->purchase_order_id)
            ->where('qrycancel', 0)
            ->firstOrFail();

        $style = EravProductStyle::where('styleid', $purchaseOrderItem->item_styleno)
            ->where('isoffline', 0)
            ->firstOrFail();

        $carton = DB::connection('erav')->table('tbl_carton_dimension')
            ->where('idtbl_carton_dimension', $data['carton_dimension_id'])
            ->where('prodstyle_list_id', $style->id)
            ->where('tbl_style_size_desc', $productionItem->tbl_style_size_desc)
            ->where('status', 1)
            ->first();
        abort_unless($carton, 404);

        $purchaseOrder = $purchaseOrderItem->purchaseOrder;
        abort_unless($purchaseOrder && !$purchaseOrder->isoffline, 404);

        $deliveryAddressId = null;
        if ($data['address_mode'] === 'existing') {
            $deliveryAddress = DB::connection('erav')->table('porderctndeliaddrlist as address')
                ->join('salesotheraddrlist as destination', 'destination.custdest_id', '=', 'address.salesotheraddr_custdest_id')
                ->where('address.id', $data['delivery_address_id'])
                ->where('address.porder_pdf_list_id', $assignment->purchase_order_id)
                ->where('address.item_styleno', $purchaseOrderItem->item_styleno)
                ->where('address.item_colorcode', $purchaseOrderItem->item_colorcode)
                ->where('address.qrycancel', 0)
                ->where('destination.qrycancel', 0)
                ->where('destination.customer_id', $purchaseOrder->customer_id)
                ->select('address.id')
                ->first();
            abort_unless($deliveryAddress, 404);
            $deliveryAddressId = $deliveryAddress->id;
        }

        $fullCartons = intdiv($data['ship_quantity'], $data['pieces_per_carton']);
        $oddCartons = $data['ship_quantity'] % $data['pieces_per_carton'] > 0 ? 1 : 0;
        $cbmPerCarton = round($data['carton_length'] * $data['carton_width'] * $data['carton_height'] / 1000000, 6);

        if ($oddCartons === 0 && (($data['odd_net_weight'] ?? 0) > 0 || ($data['odd_gross_weight'] ?? 0) > 0)) {
            return back()->withErrors([
                'odd_net_weight' => 'Odd-carton weights must be zero when there is no odd carton.',
            ])->withInput();
        }

        $userId = (string) $request->user()->id;
        $preparedAt = now('Asia/Colombo')->format('Y-m-d H:i:s');
        $approved = $request->boolean('approve_shipment');

        DB::connection('erav')->beginTransaction();

        try {
            if ($editingDelivery) {
                // Recheck the current status while saving, in case another user changed it.
                $editingDelivery = EravProdShipment::where('mbranchlist_id', $factoryId)
                    ->lockForUpdate()->findOrFail($editingDelivery->id);
                $error = $this->deliveryChangeError($editingDelivery, true);
                if ($error) {
                    DB::connection('erav')->rollBack();

                    return back()->withErrors(['shipment' => $error])->withInput();
                }
            }
            // Serialize saves of the same item before checking its remaining quantity.
            $productionItem = EravProdOrderInfo::where('pack_no', $productionItem->pack_no)
                ->where('qrycancel', 0)->lockForUpdate()->firstOrFail();

            $shippedItems = EravProdShipmentItem::query()
                ->join('prodshipment_list as header', 'header.id', '=', 'prodshipment_info.prodshipment_list_id')
                ->where('prodshipment_info.prodorder_info_packno', $productionItem->pack_no)
                ->where('prodshipment_info.qrycancel', 0)
                ->where('header.isoffline', 0);
            if ($editingDelivery) {
                // The delivery being edited must not consume its own quantity allowance.
                $shippedItems->where('header.id', '!=', $editingDelivery->id);
            }
            $alreadyShipped = $shippedItems->sum('prodshipment_info.ship_qty');

            $remainingQuantity = max(0, $productionItem->prod_qty - $alreadyShipped);
            if ($data['ship_quantity'] > $remainingQuantity) {
                DB::connection('erav')->rollBack();

                return back()->withErrors([
                    'ship_quantity' => 'Only ' . $remainingQuantity . ' pieces remain for this order item.',
                ])->withInput();
            }

            $delivery = $editingDelivery ?? new EravProdShipment();
            $delivery->prodorder_list_prodorder_id = $productionOrder->prodorder_id;
            $delivery->porder_pdf_list_id = $assignment->purchase_order_id;
            $delivery->mbranchlist_id = $factoryId;
            $delivery->prodorder_ref = $productionOrder->prodorder_ref;
            $delivery->shiporder_docdate = $data['prepared_date'];
            $delivery->shipment_moreinfo = $data['remarks'] ?? '';
            $delivery->ship_container_no = $data['container_number'] ?? null;
            $delivery->vessel_code = $data['vessel'] ?? '';
            $delivery->shipment_invno = $data['vendor_invoice_number'];
            $delivery->stafford_invno = $data['stafford_invoice_number'] ?? null;
            $delivery->ship_method = strtoupper($data['ship_via']);
            $delivery->plan_etd = $data['etd'] ?? null;
            $delivery->shipment_refno = $shipment->shipment_id;
            $delivery->pl_labels = $data['pl_label'] ?? null;
            $delivery->ship_tot_cartons = $fullCartons + $oddCartons;
            // This form saves one item; totals include the odd carton.
            $delivery->ship_net_weight = $data['total_net_weight'];
            $delivery->ship_gross_weight = $data['total_gross_weight'];
            if ($editingDelivery) {
                $delivery->updated_by = $userId;
                $delivery->updated_at = $preparedAt;
            } else {
                $delivery->shipment_htsno = '';
                $delivery->prepare_by = $userId;
                $delivery->prep_time = $preparedAt;
                $delivery->isoffline = 0;
            }
            // Keep the original approval details unless the approval choice changes.
            if (!$editingDelivery || (bool) $delivery->qryapproved !== $approved) {
                $delivery->approve_by = $approved ? $userId : null;
                $delivery->approve_on = $approved ? $preparedAt : null;
            }
            $delivery->qryapproved = $approved ? 1 : 0;
            $delivery->save();

            if ($data['address_mode'] === 'manual') {
                // Create a new customer destination; do not edit a shared address.
                $destinationId = DB::connection('erav')->table('salesotheraddrlist')->insertGetId([
                    'customer_id' => $purchaseOrder->customer_id,
                    'custdest_name' => $data['manual_address_name'] ?? 'Shipment ' . $shipment->shipment_id,
                    'custdest_addr' => $data['manual_address'],
                    'custdest_regno' => '',
                    'qrycancel' => 0,
                    'created_by' => $userId,
                    'created_at' => $preparedAt,
                ], 'custdest_id');

                // Link the destination to this PO, style, color and delivery.
                $deliveryAddressId = DB::connection('erav')->table('porderctndeliaddrlist')->insertGetId([
                    'porder_pdf_list_id' => $assignment->purchase_order_id,
                    'item_styleno' => $purchaseOrderItem->item_styleno,
                    'prodstyle_list_id' => $style->id,
                    'item_colorcode' => $purchaseOrderItem->item_colorcode,
                    'item_colordesc' => $productionItem->tbl_style_color_desc,
                    'salesotheraddr_custdest_id' => $destinationId,
                    'prodcarton_from' => 1,
                    'prodcarton_to' => $fullCartons + $oddCartons,
                    'pack_by_ratio' => 0,
                    'ctnpo_num' => $purchaseOrder->pdf_po_refno,
                    'ctndoc_id' => 'SHIP-' . $delivery->id,
                    'delivery_schdate' => null,
                    'qrycancel' => 0,
                    'created_by' => $userId,
                    'created_at' => $preparedAt,
                ]);
            }

            $item = $editingDelivery
                ? $delivery->items()->where('qrycancel', 0)->firstOrFail()
                : new EravProdShipmentItem();
            $item->prodshipment_list_id = $delivery->id;
            $item->prodorder_info_packno = $productionItem->pack_no;
            $item->porder_pdf_info_id = $purchaseOrderItem->id;
            $item->prodstyle_list_id = $style->id;
            $item->color_code = $productionItem->color_code;
            $item->tbl_style_color_code = $productionItem->tbl_style_color_code;
            $item->tbl_style_color_desc = $productionItem->tbl_style_color_desc;
            $item->size_type = $productionItem->size_type;
            $item->tbl_style_size_desc = $productionItem->tbl_style_size_desc;
            $item->ship_qty = $data['ship_quantity'];
            $item->full_carton_nos = $fullCartons;
            $item->odd_carton_nos = $oddCartons;
            $item->pcs_per_carton = $data['pieces_per_carton'];
            $item->pack_netwgt = $data['total_net_weight'];
            $item->pack_grossweight = $data['total_gross_weight'];
            $item->odditems_netwgt = $data['odd_net_weight'] ?? 0;
            $item->oddpack_grossweight = $data['odd_gross_weight'] ?? 0;
            $item->idtbl_carton_dimension = $carton->idtbl_carton_dimension;
            $item->carton_length = $data['carton_length'];
            $item->carton_width = $data['carton_width'];
            $item->carton_height = $data['carton_height'];
            $item->item_weight = $carton->item_weight;
            $item->carton_weight = $carton->carton_weight;
            $item->cbm_per_carton = $cbmPerCarton;
            $item->porderctndeliaddrlist_id = $deliveryAddressId;
            // The existing legacy item records use zero for these rate fields.
            $item->qrycancel = 0;
            if ($editingDelivery) {
                $item->updated_by = $userId;
                $item->updated_at = $preparedAt;
            } else {
                $item->packrate_itemsize = 0;
                $item->baserate_itemcolor = 0;
                $item->created_by = $userId;
                $item->created_at = $preparedAt;
            }
            $item->save();

            DB::connection('erav')->commit();
        } catch (\Throwable $exception) {
            DB::connection('erav')->rollBack();
            report($exception);

            return back()->withErrors([
                'shipment' => 'The shipment could not be saved. Please try again.',
            ])->withInput();
        }

        if ($editingDelivery) {
            return redirect()->route('create-shipment.index', ['history' => 1])
                ->with('success', 'Delivery updated successfully.');
        }

        return redirect()->route('shipment-details.index')
            ->with('success', 'Shipment and order item saved successfully.');
    }
}
