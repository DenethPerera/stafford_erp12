<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Shipment;
use App\Models\ShipmentDocument;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipmentManagementController extends Controller
{
    public function index()
    {
        return view('shipment_management.index');
    }

    public function create(Request $request)
    {
        return $this->shipmentForm($request);
    }

    // Create and Edit use the same form and dropdown lists.
    private function shipmentForm(Request $request, ?Shipment $editingShipment = null)
    {
        $validated = $request->validate([
            'shipment_search' => 'nullable|string|max:255',
        ]);
        $shipmentSearch = trim($validated['shipment_search'] ?? '');

        $buyers = DB::connection('erav')->table('salescustomerlist')
            ->where('isoffline', 0)
            ->orderBy('customer_name')
            ->pluck('customer_name', 'customer_id');

        $countries = Country::query()
            ->whereNotNull('country')->whereNotNull('country_code')
            ->where('country', '!=', '')->where('country_code', '!=', '')
            ->orderBy('country')->pluck('country', 'country_code');

        // Keep the existing Shipment ID sequence. //Generate the Next Shipment Number
        $nextShipmentNumbers = [];
        foreach (Shipment::get(['country_of_origin', 'shipment_id']) as $shipment) {
            $country = $shipment->country_of_origin;
            $pattern = '/^'.preg_quote($country, '/').'-([0-9]+)\\z/';
            if (preg_match($pattern, $shipment->shipment_id, $matches)) {
                $nextShipmentNumbers[$country] = max(
                    $nextShipmentNumbers[$country] ?? 1,
                    (int) $matches[1] + 1
                );
            }
        }

        // Get saved shipments, newest first.
        $shipmentQuery = Shipment::query();
        if ($shipmentSearch !== '') {
            $shipmentQuery->where('shipment_id', 'like', '%' . $shipmentSearch . '%');
        }

        $shipments = $shipmentQuery->orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();


        // Collect factory IDs from the displayed shipments.
        $factoryIds = [];
        foreach ($shipments as $shipment) {
            foreach ($shipment->factory_ids ?? [] as $factoryId) {
                $factoryIds[] = $factoryId;
            }
        }

        $factoryNames = DB::connection('erav')->table('mbranchlist')
            ->whereIn('id', $factoryIds)
            ->pluck('br_name', 'id');

        // Read only the saved PO assignments for this page of shipments.
        $shipmentLinks = DB::table('shipment_order_factories')
            ->whereIn('shipment_id', $shipments->pluck('id'))
            ->orderBy('purchase_order_id')
            ->get();

        $purchaseOrderNames = DB::connection('erav')->table('porder_pdf_list')
            ->whereIn('id', $shipmentLinks->pluck('purchase_order_id'))
            ->pluck('pdf_po_refno', 'id');

        // Keep each PO under its own shipment and factory.
        $factoryPurchaseOrders = [];
        foreach ($shipmentLinks as $link) {
            $factoryPurchaseOrders[$link->shipment_id][$link->factory_id][] =
                $purchaseOrderNames[$link->purchase_order_id] ?? 'PO #' . $link->purchase_order_id;
        }

        return view('shipment_management.create', compact(
            'countries', 'buyers', 'nextShipmentNumbers', 'shipments', 'factoryNames',
            'shipmentSearch', 'factoryPurchaseOrders', 'editingShipment'
        ));








    }



    public function purchaseOrdersByBuyer(Request $request)
    {
        $request->validate([
            'buyer_id' => 'required|integer|exists:erav.salescustomerlist,customer_id,isoffline,0',
        ]);

        $purchaseOrders = DB::connection('erav')->table('porder_pdf_list')
            ->where('customer_id', $request->buyer_id)
            ->where('isoffline', 0)
            ->whereNotNull('pdf_po_refno')->where('pdf_po_refno', '!=', '')
            ->orderBy('pdf_po_refno')
            ->get(['id', 'pdf_po_refno']);

        return response()->json([
            'purchase_orders' => $purchaseOrders,
            'factory_links' => $this->purchaseOrderFactoryLinks($purchaseOrders->pluck('id')->all()),
        ]);
    }

    // Read each real PO-factory pair once, even if several production orders use it.
    private function purchaseOrderFactoryLinks(array $purchaseOrderIds)
    {
        return DB::connection('erav')->table('prodorder_list as production')
            ->join('mbranchlist as factory', 'factory.id', '=', 'production.mbranchlist_id')
            ->whereIn('production.porder_pdf_list_id', $purchaseOrderIds)
            ->where('production.isoffline', 0)->where('factory.isoffline', 0)
            ->select(
                'production.porder_pdf_list_id as purchase_order_id',
                'factory.id as factory_id',
                'factory.br_name as factory_name',
                'factory.br_code'
            )
            ->distinct()->orderBy('factory.br_name')->get();
    }

    public function store(Request $request)
    {
        return $this->saveShipment($request);
    }

    // Validate both forms in one place so Create and Edit follow the same rules.
    private function saveShipment(Request $request, ?Shipment $editingShipment = null)
    {
        // 1. Check the form fields.
        $data = $request->validate([
            'country_of_origin' => ['required', 'string', 'max:10', Rule::exists(Country::class, 'country_code')],
            'buyer_id' => 'required|integer|exists:erav.salescustomerlist,customer_id,isoffline,0',
            'shipment_id_mode' => 'required|in:manual,auto',
            'shipment_id' => ['required', 'string', 'max:255', Rule::unique('shipments', 'shipment_id')->ignore($editingShipment?->id)],
            'purchase_order_ids' => 'required|array|min:1',
            'purchase_order_ids.*' => 'required|integer|distinct',
            'shipment_type' => 'required|in:individual,consolidated',
            'factory_id' => 'exclude_unless:shipment_type,individual|required|integer|exists:erav.mbranchlist,id,isoffline,0',
            'factory_ids' => 'exclude_unless:shipment_type,consolidated|required|array|min:2',
            'factory_ids.*' => 'exclude_unless:shipment_type,consolidated|required|integer|distinct|exists:erav.mbranchlist,id,isoffline,0',
        ]);

        $purchaseOrderIds = [];
        foreach ($data['purchase_order_ids'] as $id) {
            $purchaseOrderIds[] = (int) $id;
        }

        $factoryIds = [];
        if ($data['shipment_type'] === 'individual') {
            $factoryIds[] = (int) $data['factory_id'];
        } else {
            foreach ($data['factory_ids'] as $id) {
                $factoryIds[] = (int) $id;
            }
        }

        // 2. Every PO must be active and belong to the selected buyer.
        $validOrderCount = DB::connection('erav')->table('porder_pdf_list')
            ->where('customer_id', $data['buyer_id'])->where('isoffline', 0)
            ->whereNotNull('pdf_po_refno')->where('pdf_po_refno', '!=', '')
            ->whereIn('id', $purchaseOrderIds)->count();

        if ($validOrderCount !== count($purchaseOrderIds)) {
            throw ValidationException::withMessages([
                'purchase_order_ids' => 'Select active purchase orders belonging to this buyer.',
            ]);
        }

        // 3. Keep the selected pairs and remember which POs and factories they cover.
        $selectedLinks = [];
        $linkedOrderIds = [];
        $linkedFactoryIds = [];
        foreach ($this->purchaseOrderFactoryLinks($purchaseOrderIds) as $link) {
            if (in_array($link->factory_id, $factoryIds)) {
                $selectedLinks[] = $link;
                $linkedOrderIds[] = $link->purchase_order_id;
                $linkedFactoryIds[] = $link->factory_id;
            }
        }

        foreach ($purchaseOrderIds as $id) {
            if (! in_array($id, $linkedOrderIds)) {
                throw ValidationException::withMessages([
                    'purchase_order_ids' => 'Every selected PO must be connected to a selected factory.',
                ]);
            }
        }

        foreach ($factoryIds as $id) {
            if (! in_array($id, $linkedFactoryIds)) {
                throw ValidationException::withMessages([
                    'factory_ids' => 'Every selected factory must be connected to a selected PO.',
                ]);
            }
        }

        // 4. Save one shipment and its pairs together.
        try {
            $shipment = DB::transaction(function () use ($data, $purchaseOrderIds, $factoryIds, $selectedLinks, $editingShipment) {
                $shipment = $editingShipment ?? new Shipment();
                $shipment->fill([
                    'country_of_origin' => $data['country_of_origin'],
                    'buyer_id' => $data['buyer_id'],
                    'shipment_id' => $data['shipment_id'],
                    'shipment_id_mode' => $data['shipment_id_mode'],
                    'purchase_order_id' => $purchaseOrderIds,
                    'shipment_type' => $data['shipment_type'],
                    'factory_ids' => $factoryIds,
                ]);
                $shipment->save();

                // Replace the old assignments when editing an unused shipment.
                DB::table('shipment_order_factories')->where('shipment_id', $shipment->id)->delete();

                foreach ($selectedLinks as $link) {
                    DB::table('shipment_order_factories')->insert([
                        'shipment_id' => $shipment->id,
                        'purchase_order_id' => $link->purchase_order_id,
                        'factory_id' => $link->factory_id,
                    ]);
                }

                return $shipment;
            });
        } catch (UniqueConstraintViolationException $exception) {
            throw ValidationException::withMessages([
                'shipment_id' => 'This shipment ID already exists. Reload for the next automatic ID, or enter another manual ID.',
            ]);
        }

        $message = 'Shipment '.$shipment->shipment_id.' created successfully.';
        if ($editingShipment) {
            $message = 'Shipment '.$shipment->shipment_id.' updated successfully.';
        }

        return redirect()->route('shipment-management.create')->with('success', $message);
    }

    public function show(string $id)
    {
        //
    }

    public function edit(Request $request, string $id)
    {
        $shipment = Shipment::findOrFail($id);

        if ($this->hasLinkedRecords($shipment)) {
            return redirect()->route('shipment-management.create')
                ->with('error', 'This shipment has linked deliveries or documents and cannot be edited.');
        }

        return $this->shipmentForm($request, $shipment);
    }

    public function update(Request $request, string $id)
    {
        $shipment = Shipment::findOrFail($id);

        if ($this->hasLinkedRecords($shipment)) {
            return redirect()->route('shipment-management.create')
                ->with('error', 'This shipment has linked deliveries or documents and cannot be edited.');
        }

        return $this->saveShipment($request, $shipment);
    }

    public function destroy(string $id)
    {
        $shipment = Shipment::findOrFail($id);

        if ($this->hasLinkedRecords($shipment)) {
            return redirect()->route('shipment-management.create')
                ->with('error', 'This shipment has linked deliveries or documents and cannot be deleted.');
        }

        // The database also deletes this shipment's PO/factory assignments.
        $shipment->delete();

        return redirect()->route('shipment-management.create')
            ->with('success', 'Shipment '.$shipment->shipment_id.' deleted successfully.');
    }

    private function hasLinkedRecords(Shipment $shipment): bool
    {
        if (! empty($shipment->import_id)) {
            return true;
        }

        if (DB::connection('erav')->table('prodshipment_list')
            ->where('shipment_refno', $shipment->shipment_id)->exists()) {
            return true;
        }

        if (DB::table('shipment_documents')->where('shipment_id', $shipment->shipment_id)->exists()) {
            return true;
        }

        return DB::table('forwarder_documents')->where('shipment_id', $shipment->shipment_id)->exists();
    }

    public function scan(Request $request)
{
    $shipment = null;
    $searchError = null;
    $shippingDetails = [];
    $shipmentDocuments = [];
    $containerDetails = [];
    $consolidationFactories = [];
    $consolidationTotals = [
        'ordered_qty' => null,
        'shipped_qty' => null,
        'cartons' => null,
        'cbm' => null,
        'shipped_values' => null,
    ];


    $shipmentId = $request->query('shipment_id', '');
    $shipmentId = is_string($shipmentId) ? $shipmentId : '';

    $details = [
        'export_import' => null,
        'shipment_status' => null,
        'bl_number' => null,
        'booking_number' => null,
        'vessel_name' => null,
        'voyage_number' => null,
        'pol' => null,
        'pod' => null,
        'etd' => null,
        'eta' => null,
        'transit_time' => null,
        'container_number' => null,
        'seal_number' => null,
        'carrier' => null,
    ];

    if ($request->query->has('shipment_id')) {
        $validator = Validator::make($request->query(), [
            'shipment_id' => 'required|string|max:255',
        ], [
            'shipment_id.required' => 'Please enter a Shipment ID.',
        ]);

        if ($validator->fails()) {
            $searchError = $validator->errors()->first('shipment_id');
        } else {
            $shipment = DB::table('shipments')
                ->where('shipment_id', $shipmentId)
                ->first();

            // Preserve the existing case-sensitive match.
            if ($shipment && $shipment->shipment_id !== $shipmentId) {
                $shipment = null;
            }

            if (! $shipment) {
                $searchError = 'Shipment not found.';
            }
        }
    }

    if ($shipment) {
        $documents = ShipmentDocument::where('shipment_id', $shipment->shipment_id)
            ->orderBy('id', 'desc')
            ->get();

        // Keep the same exact Shipment ID match used by the search.
        foreach ($documents as $document) {
            if ($document->shipment_id === $shipment->shipment_id) {
                $shipmentDocuments[] = $document;
            }
        }

        // The displayed Shipment Number owns the production deliveries.
        // PO/factory assignments are an additional check, never a lookup fallback.
        $links = DB::table('shipment_order_factories')
            ->where('shipment_id', $shipment->id)
            ->get(['purchase_order_id', 'factory_id']);

        $allowedPairs = [];
        foreach ($links as $link) {
            $allowedPairs[$link->purchase_order_id . ':' . $link->factory_id] = true;
        }

        $referenceDeliveries = DB::connection('erav')
            ->table('prodshipment_list')
            ->where('shipment_refno', $shipment->shipment_id)
            ->orderBy('id')
            ->get()
            ->filter(function ($delivery) use ($shipment) {
                // MySQL may match different case or trailing spaces.
                return $delivery->shipment_refno === $shipment->shipment_id;
            });

        $activeDeliveries = $referenceDeliveries->where('isoffline', 0);
        $deliveries = $activeDeliveries->filter(function ($delivery) use ($allowedPairs) {
            $key = $delivery->porder_pdf_list_id . ':' . $delivery->mbranchlist_id;

            return isset($allowedPairs[$key]);
        })->values();

        $deliveryIds = $deliveries->pluck('id')->all();
        $importIds = $deliveries->pluck('import_id')
            ->filter(fn ($id) => (int) $id > 0)
            ->map(fn ($id) => (int) $id)
            ->unique()->values();

        // Keep explicitly saved historical shipping information. Never use it
        // to guess which production deliveries belong to this shipment.
        $usesLegacyImport = $referenceDeliveries->isEmpty()
            && (int) ($shipment->import_id ?? 0) > 0;

        if ($usesLegacyImport) {
            $importIds = collect([(int) $shipment->import_id]);
        }

        $imports = DB::connection('erav')->table('imports as shipping')
            ->leftJoin('tbl_delivery_statuses as status', 'status.id', '=', 'shipping.delivery_status')
            ->whereIn('shipping.id', $importIds->all())
            ->where('shipping.deleted', 0)
            ->select('shipping.*', 'status.name as status_name')
            ->orderBy('shipping.id')
            ->get();

        $warnings = [];
        if ($imports->count() !== $importIds->count()) {
            $warnings[] = $usesLegacyImport
                ? 'The saved shipping record is missing or deleted.'
                : 'One or more linked shipping records are missing or deleted.';
        }
        if ($usesLegacyImport) {
            $warnings[] = 'Historical shipping information is shown. Production quantities require records linked by Shipment Number.';
        } elseif ($referenceDeliveries->isEmpty()) {
            $warnings[] = 'No production shipment records are linked to this Shipment ID.';
        }
        if ($activeDeliveries->count() !== $deliveries->count()) {
            $warnings[] = 'Some production shipment records have a PO or factory that is not assigned to this shipment and were excluded.';
        }
        if ($deliveries->contains(fn ($delivery) => (int) $delivery->import_id <= 0)) {
            $warnings[] = 'Shipping information is not available for production shipments without a linked Import ID. Their quantities are included.';
        }
        if ($warnings) {
            $searchError = implode(' ', $warnings);
        }

        $emptyDetails = $details;
        foreach ($imports as $import) {
            // Each import gets a separate card; never inherit another import's fields.
            $details = $emptyDetails;
            $details['import_id'] = (int) $import->id;
            $keyVal = (string) ($import->key_val ?? '');
            if ($keyVal === '0') {
                $details['export_import'] = 'Import';
            } elseif ($keyVal === '1') {
                $details['export_import'] = 'Export';
            }

            $fields = [
                'bl_number' => 'house_bl_no',
                'vessel_name' => 'vsl_name',
                'pol' => 'loading_port_name',
                'pod' => 'discharge_port_name',
                'etd' => 'port_edt_date',
                'eta' => 'port_eta_date',
                'shipment_status' => 'status_name',
            ];
            foreach ($fields as $displayField => $databaseField) {
                $value = trim((string) ($import->$databaseField ?? ''));
                $details[$displayField] = $value !== '' ? $value : null;
            }

            $containers = DB::connection('erav')->table('imports_container_numbers')
                ->where('import_id', $import->id)
                ->orderBy('id')->pluck('container_no');

            $containerNumbers = $this->usableContainerNumbers($containers);
            if ($containerNumbers->isEmpty()) {
                $containerNumbers = $this->usableContainerNumbers(
                    $deliveries->where('import_id', $import->id)->pluck('ship_container_no')
                );
            }
            if ($containerNumbers->isNotEmpty()) {
                $details['container_number'] = $containerNumbers->implode(', ');
            }
            foreach ($containerNumbers as $number) {
                $containerDetails[] = [
                    'import_id' => (int) $import->id,
                    'container_no' => $number,
                    'size_type' => trim((string) ($import->container_type ?? '')),
                ];
            }

            // Preserve the current handling of invalid and zero dates.
            foreach (['etd', 'eta'] as $field) {
                $value = $details[$field];
                if ($value !== null) {
                    $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
                    if (! $date || $date->format('Y-m-d') !== $value) {
                        $details[$field] = null;
                    }
                }
            }
            if ($details['etd'] && $details['eta']) {
                $etd = new \DateTimeImmutable($details['etd']);
                $eta = new \DateTimeImmutable($details['eta']);
                if ($eta >= $etd) {
                    $details['transit_time'] = $etd->diff($eta)->days . ' days';
                }
            }
            $shippingDetails[] = $details;
        }
        if ($imports->isEmpty()) {
            $shippingDetails[] = $emptyDetails;
        }

        // The ERP tables use the erav database.
// Both configured connections currently use the same MySQL server.
$erpDatabase = DB::connection('erav')->getDatabaseName();

$consolidationRows = DB::table('shipments as shipment')
    ->join(
        'shipment_order_factories as link',
        'link.shipment_id',
        '=',
        'shipment.id'
    )
    ->leftJoin(
        $erpDatabase . '.mbranchlist as factory',
        'factory.id',
        '=',
        'link.factory_id'
    )
    ->leftJoin(
        $erpDatabase . '.porder_pdf_list as po',
        'po.id',
        '=',
        'link.purchase_order_id'
    )
    ->leftJoin(
        $erpDatabase . '.porder_pdf_info as item',
        function ($join) {
            $join->on('item.porder_pdf_list_id', '=', 'po.id')
                ->where('item.qrycancel', 0);
        }
    )
    // Use the shipment already found by the existing search.
    ->where('shipment.id', $shipment->id)
    ->select([
        'link.factory_id',
        'link.purchase_order_id',
        'factory.br_name',
        'po.pdf_po_refno',
        'item.id as item_id',
        'item.item_styleno',
        'item.item_description',
        'item.item_order_qty',
        'item.revised_unit_price',
    ])
    ->distinct()
    ->orderBy('factory.br_name')
    ->orderBy('link.factory_id')
    ->orderBy('po.pdf_po_refno')
    ->orderBy('link.purchase_order_id')
    ->orderBy('item.id')
    ->get();

// Read actual shipping rows separately so deliveries do not multiply PO items.
$shippingRows = collect();

if ($deliveries->isNotEmpty()) {
    $shippingRows = DB::table('shipment_order_factories as link')
        ->join($erpDatabase . '.prodshipment_list as delivery', function ($join) {
            $join->on('delivery.porder_pdf_list_id', '=', 'link.purchase_order_id')
                ->on('delivery.mbranchlist_id', '=', 'link.factory_id');
        })
        ->join(
            $erpDatabase . '.prodshipment_info as shipped',
            'shipped.prodshipment_list_id',
            '=',
            'delivery.id'
        )
        ->join($erpDatabase . '.porder_pdf_info as item', function ($join) {
            $join->on('item.id', '=', 'shipped.porder_pdf_info_id')
                ->on('item.porder_pdf_list_id', '=', 'delivery.porder_pdf_list_id');
        })
        ->leftJoin($erpDatabase . '.prodorder_list as production', function ($join) {
            $join->on('production.prodorder_id', '=', 'delivery.prodorder_list_prodorder_id')
                ->on('production.porder_pdf_list_id', '=', 'delivery.porder_pdf_list_id')
                ->on('production.mbranchlist_id', '=', 'delivery.mbranchlist_id');
        })
        ->leftJoin(
            $erpDatabase . '.tbl_currency as currency',
            'currency.idtbl_currency',
            '=',
            'production.currency_type'
        )
        ->where('link.shipment_id', $shipment->id)
        ->whereIn('delivery.id', $deliveryIds)
        ->where('delivery.isoffline', 0)
        ->where('shipped.qrycancel', 0)
        ->where('item.qrycancel', 0)
        ->select([
            'shipped.id as shipping_item_id',
            'link.factory_id',
            'link.purchase_order_id',
            'item.id as item_id',
            'shipped.ship_qty',
            'shipped.full_carton_nos',
            'shipped.odd_carton_nos',
            'shipped.cbm_per_carton',
            'item.revised_unit_price',
            'currency.currency_name',
        ])
        ->distinct()
        ->orderBy('shipped.id')
        ->get();
}

$shippingByFactory = $shippingRows->groupBy('factory_id');

// Make one card for each linked factory.
foreach ($consolidationRows->groupBy('factory_id') as $factoryId => $factoryRows) {
    $factoryItems = $factoryRows
        ->whereNotNull('item_id')
        ->unique('item_id');

    $factoryQuantity = null;

    $hasMissingQuantity = $factoryItems->contains(function ($item) {
        return ! is_numeric($item->item_order_qty);
    });

    if ($factoryItems->isNotEmpty() && ! $hasMissingQuantity) {
        $factoryQuantity = $factoryItems->sum('item_order_qty');
    }

    $factoryShipping = $shippingByFactory->get($factoryId, collect());
    $displayRows = [];

    // Combine size/color items into one row for each PO and style.
    foreach ($factoryRows->groupBy('purchase_order_id') as $poRows) {
        $styleGroups = $poRows->groupBy(function ($item) {
            return trim((string) $item->item_styleno);
        });

        foreach ($styleGroups as $styleRows) {
            $firstRow = $styleRows->first();
            $styleItems = $styleRows->whereNotNull('item_id')->unique('item_id');
            $orderedQuantity = null;
            $descriptions = [];

            foreach ($styleItems as $item) {
                $description = trim((string) $item->item_description);
                if ($description !== '' && ! in_array($description, $descriptions, true)) {
                    $descriptions[] = $description;
                }
            }

            $hasMissingQuantity = $styleItems->contains(function ($item) {
                return ! is_numeric($item->item_order_qty);
            });

            if ($styleItems->isNotEmpty() && ! $hasMissingQuantity) {
                $orderedQuantity = $styleItems->sum('item_order_qty');
            }

            // Sum only deliveries for the items in this PO/style group.
            // The existing helper counts each shipping record once.
            $styleShipping = $factoryShipping
                ->where('purchase_order_id', $firstRow->purchase_order_id)
                ->whereIn('item_id', $styleItems->pluck('item_id')->all());

            $displayRows[] = (object) array_merge([
                'pdf_po_refno' => $firstRow->pdf_po_refno,
                'has_items' => $styleItems->isNotEmpty(),
                'item_styleno' => trim((string) $firstRow->item_styleno),
                'item_description' => implode(' / ', $descriptions),
                'item_order_qty' => $orderedQuantity,
            ], $this->shipmentShippingTotals($styleShipping));
        }
    }

    $shippingTotals = $this->shipmentShippingTotals($factoryShipping);

    $consolidationFactories[] = array_merge([
        'factory_name' => trim(
            (string) ($factoryRows->first()->br_name ?? '')
        ),
        'ordered_qty' => $factoryQuantity,
        'rows' => collect($displayRows),
    ], $shippingTotals);
}

$consolidationTotals = array_merge(
    $consolidationTotals,
    $this->shipmentShippingTotals($shippingRows)
);

// A PO can be linked to multiple factories.
// Count each PO item once in the overall quantity.
$uniqueItems = $consolidationRows
    ->whereNotNull('item_id')
    ->unique('item_id');

$hasMissingQuantity = $uniqueItems->contains(function ($item) {
    return ! is_numeric($item->item_order_qty);
});

if ($uniqueItems->isNotEmpty() && ! $hasMissingQuantity) {
    $consolidationTotals['ordered_qty'] =
        $uniqueItems->sum('item_order_qty');
}




    }




    return view('shipment_management.scan', compact(
        'shipment',
        'shipmentId',
        'searchError',
        'shippingDetails',
        'shipmentDocuments',
        'containerDetails',
        'consolidationFactories',
        'consolidationTotals'
    ));
}

// Use the same calculation for item rows, factory cards and overall totals.
private function shipmentShippingTotals($records)
{
    $totals = [
        'shipped_qty' => null,
        'cartons' => null,
        'cbm' => null,
        'shipped_values' => null,
    ];

    if ($records->isEmpty()) {
        return $totals;
    }

    $totals = [
        'shipped_qty' => 0,
        'cartons' => 0,
        'cbm' => 0,
        'shipped_values' => [],
    ];

    // Count each shipping item once, including when PO/factory links repeat.
    foreach ($records->unique('shipping_item_id') as $record) {
        $quantity = is_numeric($record->ship_qty)
            ? (float) $record->ship_qty : null;

        $cartons = null;
        if (is_numeric($record->full_carton_nos) && is_numeric($record->odd_carton_nos)) {
            $cartons = (float) $record->full_carton_nos + (float) $record->odd_carton_nos;
        }

        $cbm = $cartons !== null && is_numeric($record->cbm_per_carton)
            ? $cartons * (float) $record->cbm_per_carton : null;

        foreach (['shipped_qty' => $quantity, 'cartons' => $cartons, 'cbm' => $cbm] as $field => $value) {
            // Do not show a partial total when a contributing value is missing.
            if ($value === null || $totals[$field] === null) {
                $totals[$field] = null;
            } else {
                $totals[$field] += $value;
            }
        }

        // Estimated shipped value at PO unit prices, using the linked
        // production order's currency. Never combine different currencies.
        $currency = trim((string) ($record->currency_name ?? ''));
        if ($quantity === null || ! is_numeric($record->revised_unit_price) || $currency === '') {
            $totals['shipped_values'] = null;
        } elseif ($totals['shipped_values'] !== null) {
            $value = round($quantity * (float) $record->revised_unit_price, 2);
            $totals['shipped_values'][$currency] =
                ($totals['shipped_values'][$currency] ?? 0) + $value;
        }
    }

    return $totals;
}

private function usableContainerNumbers($numbers)
{
    return $numbers->map(fn ($number) => strtoupper(trim((string) $number)))
        ->reject(fn ($number) => in_array($number, ['', '-', 'N/A', 'NA', 'NULL'], true))
        ->unique()->values();
}

public function saveShipmentImport(Request $request)
{
    // Keep the old endpoint explicit for stale forms, without changing legacy data.
    abort(410, 'Shipping records are now linked through Order Delivery.');
}

    public function forwarderLogin()
    {
        return view('shipment_management.forwarder_login');
    }
}
