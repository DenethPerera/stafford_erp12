<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSalesContractPackingRequest;
use App\Http\Requests\UpdateSalesContractPricesRequest;
use App\Http\Requests\UpdateSalesContractRequest;
use App\Models\Erav\EravPurchaseOrder;
use App\Models\Erav\EravSalesCustomer;
use App\Models\Erav\EravSalesDeliveryAddress;
use App\Services\SalesContractService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SalesContractController extends Controller
{
    public function index(Request $request)
    {
        $search = mb_substr((string) $request->query('search', ''), 0, 100);
        $status = $request->query('status', '');
        $query = EravPurchaseOrder::where('isoffline', 0);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                foreach (['pdf_po_refno', 'order_conf_refno', 'customer_name'] as $field) {
                    $q->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        }
        if ($status === 'draft') {
            $query->where('qryapproved', 0)->where('qryclosed', 0);
        }
        if ($status === 'finalized') {
            $query->where('qryapproved', 1)->where('qryclosed', 0);
        }
        if ($status === 'closed') {
            $query->where('qryclosed', 1);
        }
        $orders = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return view('sales_contracts.index', compact('orders', 'search', 'status'));
    }

    public function show(int $id)
    {
        $po = $this->order($id);
        $customer = EravSalesCustomer::find($po->customer_id);
        $addresses = EravSalesDeliveryAddress::where('customer_id', $po->customer_id)->where('qrycancel', 0)->orderBy('delilocation_name')->get();
        $history = Activity::on('erav')->where('log_name', 'sales-contract')->where('subject_type', $po->getMorphClass())->where('subject_id', $po->id)->orderByDesc('id')->paginate(10, ['*'], 'history_page');

        return view('sales_contracts.show', compact('po', 'customer', 'addresses', 'history'));
    }

    public function update(UpdateSalesContractRequest $request, int $id, SalesContractService $service)
    {
        $service->updateHeader($id, $request->validated(), $request->user());

        return back()->with('success', 'Order details updated.');
    }

    public function updatePrices(UpdateSalesContractPricesRequest $request, int $id, SalesContractService $service)
    {
        $service->updateItems($id, $request->validated()['items'], $request->user(), true);

        return back()->with('success', 'Prices and order totals updated.');
    }

    public function updatePacking(UpdateSalesContractPackingRequest $request, int $id, SalesContractService $service)
    {
        $service->updateItems($id, $request->validated()['items'], $request->user(), false);

        return back()->with('success', 'Packing details updated.');
    }

    public function finalize(Request $request, int $id, SalesContractService $service)
    {
        $service->finalize($id, $request->user());

        return back()->with('success', 'Order finalized. Further editing is locked.');
    }

    public function printView(int $id)
    {
        $po = $this->order($id);

        return view('sales_contracts.print', compact('po'));
    }

    private function order(int $id): EravPurchaseOrder
    {
        return EravPurchaseOrder::where('isoffline', 0)->with(['items' => fn ($q) => $q->orderBy('item_seqno')])->findOrFail($id);
    }
}
