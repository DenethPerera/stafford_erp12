<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Shipment;
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

    public function create()
    {
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
$shipments = Shipment::orderBy('id', 'desc')->paginate(10);


        // Collect factory IDs from the displayed shipments.
$factoryIds = [];

foreach ($shipments as $shipment) {
    foreach ($shipment->factory_ids ?? [] as $factoryId) {
        $factoryIds[] = $factoryId;
    }
}

// Get each factory name using its ID.
$factoryNames = DB::connection('erav')
    ->table('mbranchlist')
    ->whereIn('id', $factoryIds)
    ->pluck('br_name', 'id');

        return view('shipment_management.create', compact(
            'countries', 'buyers', 'nextShipmentNumbers','shipments','factoryNames'
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
                'factory.br_name as factory_name'
            )
            ->distinct()->orderBy('factory.br_name')->get();
    }

    public function store(Request $request)
    {
        // 1. Check the form fields.
        $data = $request->validate([
            'country_of_origin' => ['required', 'string', 'max:10', Rule::exists(Country::class, 'country_code')],
            'buyer_id' => 'required|integer|exists:erav.salescustomerlist,customer_id,isoffline,0',
            'shipment_id_mode' => 'required|in:manual,auto',
            'shipment_id' => 'required|string|max:255|unique:shipments,shipment_id',
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
            $shipment = DB::transaction(function () use ($data, $purchaseOrderIds, $factoryIds, $selectedLinks) {
                $shipment = Shipment::create([
                    'country_of_origin' => $data['country_of_origin'],
                    'buyer_id' => $data['buyer_id'],
                    'shipment_id' => $data['shipment_id'],
                    'shipment_id_mode' => $data['shipment_id_mode'],
                    'purchase_order_id' => $purchaseOrderIds,
                    'shipment_type' => $data['shipment_type'],
                    'factory_ids' => $factoryIds,
                ]);

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

        return redirect()->route('shipment-management.create')
            ->with('success', 'Shipment '.$shipment->shipment_id.' created successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }

    public function scan(Request $request)
{
    $shipment = null;
    $searchError = null;
    $importOptions = [];
    $shippingDetails = [];

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
        $import = null;

        if ($shipment->import_id !== null) {
            // Use the saved import directly.
            $import = DB::connection('erav')
                ->table('imports as shipping')
                ->leftJoin(
                    'tbl_delivery_statuses as status',
                    'status.id',
                    '=',
                    'shipping.delivery_status'
                )
                ->where('shipping.id', $shipment->import_id)
                ->where('shipping.deleted', 0)
                ->first([
                    'shipping.*',
                    'status.name as status_name',
                ]);

            if (! $import) {
                $searchError =
                    'The saved shipping record is missing or deleted.';
            }
        } else {
            $matches = $this->matchingShipmentImports($shipment)->get();

            // Each import appears once, with its matching PO numbers.
            foreach ($matches->groupBy('id') as $importId => $rows) {
                $match = $rows->first();

                $importOptions[] = [
                    'id' => $importId,
                    'po_numbers' => $rows->pluck('po_no')
                        ->unique()
                        ->implode(', '),
                    'bl_number' => trim((string) $match->house_bl_no),
                    'vessel_name' => trim((string) $match->vsl_name),
                ];
            }
        }

        if ($import) {
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

                $details[$displayField] = $value !== ''
                    ? $value
                    : null;
            }

            $numbers = [];

            $containers = DB::connection('erav')
                ->table('imports_container_numbers')
                ->where('import_id', $import->id)
                ->orderBy('id')
                ->pluck('container_no');

            foreach ($containers as $container) {
                $number = trim((string) $container);

                if ($number !== '') {
                    $numbers[] = $number;
                }
            }

            if (count($numbers) > 0) {
                $details['container_number'] = implode(
                    ', ',
                    array_unique($numbers)
                );
            }

            // Invalid dates and zero dates remain unavailable.
            foreach (['etd', 'eta'] as $field) {
                $value = $details[$field];

                if ($value !== null) {
                    $date = \DateTimeImmutable::createFromFormat(
                        '!Y-m-d',
                        $value
                    );

                    if (! $date || $date->format('Y-m-d') !== $value) {
                        $details[$field] = null;
                    }
                }
            }

            if ($details['etd'] && $details['eta']) {
                $etd = new \DateTimeImmutable($details['etd']);
                $eta = new \DateTimeImmutable($details['eta']);

                if ($eta >= $etd) {
                    $details['transit_time'] =
                        $etd->diff($eta)->days.' days';
                }
            }
        }
         if ($shipment->import_id !== null) {
            $containers = DB::connection('erav')
                ->table('imports_container_numbers')
                ->where('import_id', $shipment->import_id)
                ->orderBy('id')
                ->pluck('container_no');

            if ($containers->isNotEmpty()) {
                $details['container_number'] = $containers->implode(', ');
            }
        }

        // Preserve the existing details card and missing-value display.
        $shippingDetails[] = $details;
    }




    return view('shipment_management.scan', compact(
        'shipment',
        'shipmentId',
        'searchError',
        'shippingDetails',
        'importOptions'
    ));
}

private function matchingShipmentImports($shipment)
{
    $purchaseOrderIds = DB::table('shipment_order_factories')
        ->where('shipment_id', $shipment->id)
        ->distinct()
        ->pluck('purchase_order_id');

    $poNumbers = DB::connection('erav')
        ->table('porder_pdf_list')
        ->whereIn('id', $purchaseOrderIds)
        ->whereNotNull('pdf_po_refno')
        ->where('pdf_po_refno', '!=', '')
        ->distinct()
        ->pluck('pdf_po_refno');

    return DB::connection('erav')
        ->table('imports as shipping')
        ->join(
            'imports_po_nos as links',
            'links.import_id',
            '=',
            'shipping.id'
        )
        ->where('shipping.deleted', 0)
        ->whereIn('links.po_no', $poNumbers)
        ->select(
            'shipping.id',
            'shipping.house_bl_no',
            'shipping.vsl_name',
            'links.po_no'
        )
        ->distinct()
        ->orderBy('shipping.id')
        ->orderBy('links.po_no');
}

public function saveShipmentImport(Request $request)
{
    $data = $request->validate([
        'shipment_id' => 'required|string|max:255',
        'import_id' => 'required|integer|min:1',
    ]);

    $shipment = DB::table('shipments')
        ->where('shipment_id', $data['shipment_id'])
        ->first();

    if (! $shipment || $shipment->shipment_id !== $data['shipment_id']) {
        abort(404, 'Shipment not found.');
    }

    if ($shipment->import_id !== null) {
        throw ValidationException::withMessages([
            'import_id' =>
                'This shipment already has a saved shipping record. Search again to view it.',
        ]);
    }

    // Check the selected record against the shipment POs again.
    $validImport = $this->matchingShipmentImports($shipment)
        ->where('shipping.id', $data['import_id'])
        ->exists();

    if (! $validImport) {
        throw ValidationException::withMessages([
            'import_id' =>
                'Select a shipping record matching the shipment POs.',
        ]);
    }

    // Prevent a stale form from overwriting another user's saved link.
    $updated = DB::table('shipments')
        ->where('id', $shipment->id)
        ->whereNull('import_id')
        ->update([
            'import_id' => $data['import_id'],
            'updated_at' => now(),
        ]);

    if ($updated === 0) {
        throw ValidationException::withMessages([
            'import_id' =>
                'The shipment was linked by another user. Search again to view it.',
        ]);
    }

    return redirect()->route('shipment-management.scan', [
        'shipment_id' => $shipment->shipment_id,
    ])->with('success', 'Shipping record saved successfully.');
}





    public function forwarderLogin()
    {
        return view('shipment_management.forwarder_login');
    }
}
