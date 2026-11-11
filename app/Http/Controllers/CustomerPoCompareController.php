<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravPurchaseOrder;
use App\Services\CustomerPoCompareService;
use App\Services\CustomerPoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPoCompareController extends Controller
{
    public function index(Request $request, CustomerPoUploadService $parser, CustomerPoCompareService $compare)
    {
        $customers = DB::connection('erav')->table('salescustomerlist')->where('isoffline', 0)
            ->orderBy('customer_name')->get(['customer_id', 'customer_ref', 'customer_name']);
        $inbox = storage_path('app/ab_ftp/inbox');
        $files = is_dir($inbox) ? collect(File::files($inbox))
            ->filter(fn($file) => !$file->isLink() && in_array(strtolower($file->getExtension()), ['csv', 'txt'], true))
            ->sort(fn($a, $b) => ($b->getMTime() <=> $a->getMTime()) ?: strcmp($a->getFilename(), $b->getFilename()))
            ->map(fn($file) => $file->getFilename())->values() : collect();
        $results = [];
        $selectedCustomer = null;
        $selectedFile = '';
        if ($request->has('ftp_file')) {
            $request->validate(['customer_id' => 'required|integer', 'ftp_file' => 'required|string|max:255']);
            $selectedCustomer = $customers->firstWhere('customer_id', (int) $request->customer_id);
            if (!$selectedCustomer) {
                throw ValidationException::withMessages(['customer_id' => 'Select an active customer.']);
            }
            $selectedFile = $request->string('ftp_file')->toString();
            if (!$files->containsStrict($selectedFile)) {
                throw ValidationException::withMessages(['ftp_file' => 'Select an available inbox file.']);
            }
            $path = $inbox . DIRECTORY_SEPARATOR . $selectedFile;
            if (!is_readable($path) || filesize($path) > 10 * 1024 * 1024) {
                throw ValidationException::withMessages(['ftp_file' => 'File must be readable and no larger than 10 MB.']);
            }
            try {
                $groups = $parser->parseAbFtp($path, $selectedCustomer);
                $existing = EravPurchaseOrder::with('items')
                    ->where('customer_id', $selectedCustomer->customer_id)
                    ->whereIn('pdf_po_refno', array_column($groups, 'po_number'))->get()
                    ->groupBy(fn($po) => trim($po->pdf_po_refno));
                foreach ($groups as $group) {
                    $matches = $existing->get($group['po_number'], collect());
                    if ($matches->count() > 1) {
                        $results[] = ['po_number' => $group['po_number'], 'incoming' => $group,
                            'status' => 'Multiple DB POs — review required', 'header_changes' => [],
                            'items' => [], 'revised_count' => 0];
                        continue;
                    }
                    $po = $matches->first();
                    $old = $po ? [
                        'order_date' => $po->porder_date, 'ship_date' => $po->ship_date, 'indc_date' => $po->indc_date,
                        'delivery_addr' => $po->delivery_addr, 'ship_term' => $po->ship_term,
                        'ship_method' => $po->ship_method, 'season' => $po->item_dev_season, 'vendor' => $po->supplier_refno,
                        'items' => $po->items->map(fn($i) => [
                            'style' => $i->item_styleno, 'color_code' => $i->item_colorcode, 'size' => $i->size_code,
                            'qty' => $i->item_order_qty, 'unit_price' => $i->receipt_unit_price,
                            'description' => $i->item_description, 'color_name' => $i->item_colordesc,
                            'upc' => $i->upc_refno, 'master_pack' => $i->master_packqty, 'case_pack' => $i->case_packqty,
                        ])->all(),
                    ] : null;
                    $results[] = $compare->compare($group, $old);
                }
            } catch (ValidationException $e) {
                throw $e;
            } catch (Throwable $e) {
                report($e);
                throw ValidationException::withMessages(['ftp_file' => 'Comparison failed. Check the server logs.']);
            }
        }
        return view('purchase_orders.compare', compact('customers', 'files', 'results', 'selectedCustomer', 'selectedFile'));
    }
}
