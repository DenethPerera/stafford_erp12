<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends Controller
{
    /**
     * Display a listing of newly created Purchase Orders.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status');

        $query = PurchaseOrder::with(['branch', 'supplier', 'creator', 'items']);

        // If user is factory role, scope to their factory
        $user = auth()->user();
        if ($user && $user->isFactory() && $user->mbranchlist_id) {
            $query->where('mbranchlist_id', $user->mbranchlist_id);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'like', "%{$search}%")
                  ->orWhere('prodorder_id', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('supname', 'like', "%{$search}%")
                         ->orWhere('supregcode', 'like', "%{$search}%");
                  })
                  ->orWhereHas('branch', function ($bq) use ($search) {
                      $bq->where('br_name', 'like', "%{$search}%")
                         ->orWhere('br_code', 'like', "%{$search}%");
                  });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $purchaseOrders = $query->orderByDesc('id')->paginate(15);

        $counts = [
            'total'     => PurchaseOrder::count(),
            'issued'    => PurchaseOrder::where('status', 'issued')->count(),
            'confirmed' => PurchaseOrder::where('status', 'confirmed')->count(),
            'completed' => PurchaseOrder::where('status', 'completed')->count(),
        ];

        return view('purchase_orders.index', compact('purchaseOrders', 'counts', 'search', 'status'));
    }

    /**
     * Display the specified Purchase Order.
     */
    public function show($id)
    {
        $po = PurchaseOrder::with(['branch', 'supplier', 'creator', 'items', 'prodOrder'])->findOrFail($id);

        $user = auth()->user();
        if ($user && $user->isFactory() && $user->mbranchlist_id && $po->mbranchlist_id != $user->mbranchlist_id) {
            abort(403, 'Unauthorized access to this purchase order.');
        }

        return view('purchase_orders.show', compact('po'));
    }

    /**
     * Printable view of Purchase Order.
     */
    public function printView($id)
    {
        $po = PurchaseOrder::with(['branch', 'supplier', 'creator', 'items', 'prodOrder'])->findOrFail($id);

        return view('purchase_orders.print', compact('po'));
    }
}
