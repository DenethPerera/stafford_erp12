<?php

namespace App\Http\Controllers;

use App\Models\Country;
use App\Models\Shipment;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
                    'purchase_order_id' => count($purchaseOrderIds) === 1 ? $purchaseOrderIds[0] : null,
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

    public function scan()
    {
        return view('shipment_management.scan');
    }

    public function forwarderLogin()
    {
        return view('shipment_management.forwarder_login');
    }
}
