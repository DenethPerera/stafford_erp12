<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravBranch;
use App\Models\Erav\EravProdOrder;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class OrderAllocationController extends Controller
{
    /**
     * Display list of allocated production orders with factory details and PO statuses.
     */
    public function index(Request $request)
    {
        $search   = $request->input('search');
        $branchId = $request->input('branch_id');
        $season   = $request->input('season');

        $query = EravProdOrder::with(['branch', 'items']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('prodorder_ref', 'like', "%{$search}%")
                  ->orWhere('buy_no', 'like', "%{$search}%")
                  ->orWhere('prodorder_id', $search);
            });
        }

        if ($branchId) {
            $query->where('mbranchlist_id', $branchId);
        }

        if ($season) {
            $query->where('prod_order_season', $season);
        }

        $orders = $query->orderByDesc('prodorder_id')->paginate(15);

        // Fetch PO creation statuses for these orders
        $orderIds = $orders->pluck('prodorder_id')->toArray();
        $createdPos = PurchaseOrder::whereIn('prodorder_id', $orderIds)
            ->pluck('po_number', 'prodorder_id');

        // Filter dropdown data
        $branches = EravBranch::whereNotNull('br_name')->where('br_name', '!=', '')->orderBy('br_name')->get(['id', 'br_name', 'br_code']);
        $seasons  = EravProdOrder::whereNotNull('prod_order_season')->where('prod_order_season', '!=', '')->distinct()->orderBy('prod_order_season')->pluck('prod_order_season');

        return view('order_allocations.index', compact('orders', 'branches', 'seasons', 'createdPos', 'search', 'branchId', 'season'));
    }
}
