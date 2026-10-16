<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravPurchaseOrderItem;
use Illuminate\Http\Request;

class RfidGenerationController extends Controller
{
    /**
     * Display a listing of PO detail/item records with RFID values.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = EravPurchaseOrderItem::with('purchaseOrder');

        // Search by PO Number (pdf_po_refno)
        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('purchaseOrder', function ($q) use ($search) {
                $q->where('pdf_po_refno', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('id', 'desc')->paginate(20)->withQueryString();

        return view('rfid_generation.index', compact('items'));
    }

    /**
     * Export the RFID Generation list to an Excel-compatible CSV file.
     * Respects the current PO Number search filter if applied.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function export(Request $request)
    {
        $query = EravPurchaseOrderItem::with('purchaseOrder');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->whereHas('purchaseOrder', function ($q) use ($search) {
                $q->where('pdf_po_refno', 'like', "%{$search}%");
            });
        }

        $fileName = 'rfid_generation_' . date('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $file = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel auto-detection
            fputs($file, "\xEF\xBB\xBF");

            // Mandatory Columns: RFID, PO Number, Style, Size, Color
            fputcsv($file, ['RFID', 'PO Number', 'Style', 'Size', 'Color']);

            // Chunk execution to optimize memory and prevent query timeouts
            $query->orderBy('id', 'desc')->chunk(500, function ($items) use ($file) {
                foreach ($items as $item) {
                    fputcsv($file, [
                        $item->id,
                        $item->purchaseOrder?->pdf_po_refno ?? '-',
                        $item->item_styleno ?? '-',
                        $item->size_code ?? '-',
                        $item->item_colordesc ?? '-',
                    ]);
                }
            });

            fclose($file);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
