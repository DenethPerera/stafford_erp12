<?php

namespace App\Http\Controllers;

use App\Models\Erav\EravPurchaseOrder;
use App\Services\CustomerPoUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerPoUploadController extends Controller
{
    /**
     * Display the Customer PO Upload dashboard and recent uploads.
     */
    public function index()
    {
        // 1. Fetch active customers from salescustomerlist
        $customers = DB::connection('erav')
            ->table('salescustomerlist')
            ->where('isoffline', 0)
            ->orderBy('customer_name')
            ->get(['customer_id', 'customer_ref', 'customer_name']);

        // 2. Fetch available files in the AB-FTP inbox folder
        $ftpPath = storage_path('app/ab_ftp/inbox');
        if (!File::exists($ftpPath)) {
            File::makeDirectory($ftpPath, 0755, true);
        }
        $ftpFiles = collect(File::files($ftpPath))
            ->filter(fn($file) => !$file->isLink() && in_array(strtolower($file->getExtension()), ['csv', 'txt']))
            ->sort(fn($a, $b) => ($b->getMTime() <=> $a->getMTime()) ?: strcmp($a->getFilename(), $b->getFilename()))
            ->map(fn($file) => $file->getFilename())
            ->values();

        // 3. Fetch recently uploaded Customer POs from porder_pdf_list
        $recentPos = EravPurchaseOrder::where('isoffline', 0)
            ->orderByDesc('id')
            ->paginate(15);

        return view('purchase_orders.customer_po_upload', compact('customers', 'ftpFiles', 'recentPos'));
    }

    /**
     * Parse the uploaded file (or FTP file) and render the preview screen.
     */
    public function preview(Request $request, CustomerPoUploadService $service)
    {
        $request->validate([
            'upload_type' => 'required|in:NON_FTP,AB_FTP',
        ]);

        try {
            if ($request->upload_type === 'NON_FTP') {
                $request->validate([
                    'customer_id' => 'required|integer',
                    'csv_file'    => 'required|file|max:10240|mimes:csv,txt,xlsx,xls',
                ]);

                $customer = DB::connection('erav')
                    ->table('salescustomerlist')
                    ->where('customer_id', $request->customer_id)
                    ->where('isoffline', 0)
                    ->first();

                if (!$customer) {
                    return back()->with('error', 'Selected customer was not found or is inactive.')->withInput();
                }

                $file = $request->file('csv_file');
                $originalFileName = $file->getClientOriginalName();
                $tempPath = $file->store('customer-pos/temp', 'local');
                $fullPath = storage_path('app/' . $tempPath);

                $poGroups = $service->parseNonFtp($fullPath, $customer);

            } else {
                // AB-FTP
                $request->validate([
                    'ftp_file' => 'required|string',
                ]);

                $customer = DB::connection('erav')
                    ->table('salescustomerlist')
                    ->where('isoffline', 0)
                    ->where(function ($query) {
                        $query->where('customer_name', 'like', '%Alpha%Broder%')
                            ->orWhere('customer_ref', 'like', '%AB%');
                    })
                    ->first();

                if (!$customer) {
                    return back()->with('error', 'AlphaBroder customer record was not found or is inactive.')->withInput();
                }

                $cleanFileName = basename($request->ftp_file);
                $fullPath = storage_path('app/ab_ftp/inbox/' . $cleanFileName);

                if ($cleanFileName !== $request->ftp_file || is_link($fullPath) || !is_file($fullPath) || !is_readable($fullPath)
                    || !in_array(strtolower(pathinfo($fullPath, PATHINFO_EXTENSION)), ['csv', 'txt'], true)) {
                    return back()->with('error', "FTP file '{$cleanFileName}' was not found in the FTP inbox folder.")->withInput();
                }

                if (filesize($fullPath) > 10 * 1024 * 1024) {
                    return back()->with('error', 'FTP file exceeds the 10 MB limit.')->withInput();
                }
                $originalFileName = $cleanFileName;
                $poGroups = $service->parseAbFtp($fullPath, $customer);
            }

            // Generate unique preview token and store parsed payload in session
            $token = Str::random(40);
            session()->put("customer_po_preview_{$token}", [
                'file_sha256'       => hash_file('sha256', $fullPath),
                'token'             => $token,
                'upload_type'       => $request->upload_type,
                'original_filename' => $originalFileName,
                'po_groups'         => $poGroups,
                'created_at'        => now()->timestamp,
            ]);

            return view('purchase_orders.customer_po_preview', [
                'token'            => $token,
                'uploadType'       => $request->upload_type,
                'originalFileName' => $originalFileName,
                'poGroups'         => $poGroups,
            ]);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to read the PO file. Check the file and server logs.')->withInput();
        }
    }

    /**
     * Confirm import and save the validated POs into porder_pdf_list & porder_pdf_info.
     */
    public function confirm(Request $request, CustomerPoUploadService $service)
    {
        $request->validate([
            'preview_token' => 'required|string',
        ]);

        $token = $request->preview_token;
        $sessionKey = "customer_po_preview_{$token}";
        $payload = session()->get($sessionKey);

        if (!$payload || !isset($payload['po_groups']) || ($payload['created_at'] ?? 0) < now()->subMinutes(30)->timestamp) {
            return redirect()->route('purchase-orders.customer-po-upload.index')
                ->with('error', 'Preview session has expired or is invalid. Please upload the file again.');
        }

        if (!collect($payload['po_groups'])->contains(fn($group) => empty($group['is_duplicate']))) {
            return redirect()->route('purchase-orders.customer-po-upload.index')
                ->with('error', 'All POs already exist. No data was imported or updated.');
        }

        try {
            $user = auth()->user() ?? (object) ['name' => 'admin'];
            $result = $service->saveCustomerPos($payload['po_groups'], $payload['original_filename'], $user);

            // Preserve source files for future Compare; store history outside the database.
            try {
                $auditPath = storage_path('app/customer-pos/import-history');
                File::ensureDirectoryExists($auditPath);
                File::put($auditPath . '/' . $token . '.json', json_encode([
                    'file' => $payload['original_filename'],
                    'sha256' => $payload['file_sha256'] ?? null,
                    'user_id' => $user->id ?? null,
                    'imported_at' => now()->toIso8601String(),
                    'result' => $result,
                ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            } catch (Throwable $auditError) {
                report($auditError);
                session()->flash('error', 'PO import succeeded, but history could not be written. Check storage permissions.');
            }

            // Clean up session
            session()->forget($sessionKey);

            $msg = "Customer PO import completed! Successfully saved {$result['saved_pos']} PO(s) and {$result['saved_items']} line item(s). Total: " . number_format($result['total_pcs']) . " pcs (\$" . number_format($result['total_val'], 2) . ").";
            if ($result['skipped_duplicates'] > 0) {
                $msg .= " Note: {$result['skipped_duplicates']} PO(s) were skipped because they already exist in the database. Changes to existing POs were not applied.";
            }

            return redirect()->route('purchase-orders.customer-po-upload.index')
                ->with('success', $msg);

        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (Throwable $e) {
            report($e);
            return back()->with('error', 'PO import failed. Check the server logs before trying again.');
        }
    }

    /**
     * View detailed items of an existing Customer PO.
     */
    public function show($id)
    {
        $po = EravPurchaseOrder::with('items')->findOrFail($id);

        $customer = DB::connection('erav')
            ->table('salescustomerlist')
            ->where('customer_id', $po->customer_id)
            ->first();

        return view('purchase_orders.customer_po_show', compact('po', 'customer'));
    }

    /**
     * AJAX endpoint to return list of available FTP files.
     */
    public function getFtpFiles()
    {
        $ftpPath = storage_path('app/ab_ftp/inbox');
        if (!File::exists($ftpPath)) {
            File::makeDirectory($ftpPath, 0755, true);
        }

        $files = collect(File::files($ftpPath))
            ->filter(fn($file) => !$file->isLink() && in_array(strtolower($file->getExtension()), ['csv', 'txt']))
            ->sort(fn($a, $b) => ($b->getMTime() <=> $a->getMTime()) ?: strcmp($a->getFilename(), $b->getFilename()))
            ->map(fn($file) => $file->getFilename())
            ->values();

        return response()->json($files);
    }
}

