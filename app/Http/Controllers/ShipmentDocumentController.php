<?php

namespace App\Http\Controllers;

use App\Models\ShipmentDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ShipmentDocumentController extends Controller
{
    public function index(Request $request)
    {
        $branchId = $request->user()->mbranchlist_id;
        abort_unless($branchId, 403, 'Your account is not linked to a factory.');

        // Paginate shipments so their documents stay together on one page.
        $shipments = ShipmentDocument::where('mbranchlist_id', $branchId)
            ->select('shipment_id')
            ->groupBy('shipment_id')
            ->orderByRaw('MAX(id) DESC')
            ->paginate(10);

        $shipmentRows = [];

        foreach ($shipments as $shipment) {
            $documents = ShipmentDocument::where('mbranchlist_id', $branchId)
                ->where('shipment_id', $shipment->shipment_id)
                ->orderBy('id', 'desc')
                ->get();

            $documentsByType = [];
            foreach ($documents as $document) {
                $documentsByType[$document->document_type][] = $document;
            }

            $shipmentRows[] = [
                'shipment_id' => $shipment->shipment_id,
                'documents' => $documentsByType,
            ];
        }

        return view('shipment_documents.index', compact('shipments', 'shipmentRows'));
    }

    public function store(Request $request)
    {
        $branchId = $request->user()->mbranchlist_id;
        abort_unless($branchId, 403, 'Your account is not linked to a factory.');

        $validated = $request->validate([
            'shipment_id' => 'required|string|max:255',
            'master_bl' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
            'booking_confirmation' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
            'container_loading_list' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx,xls,xlsx|max:5120',
        ]);

        if (!$request->hasFile('master_bl')
            && !$request->hasFile('booking_confirmation')
            && !$request->hasFile('container_loading_list')) {
            return back()
                ->withErrors(['documents' => 'Please select at least one document.'])
                ->withInput();
        }

        $documentFields = [
            'master_bl' => 'Master BL',
            'booking_confirmation' => 'Booking Confirmation',
            'container_loading_list' => 'Container Loading List',
        ];
        $storedPaths = [];

        // Save every selected document with the same shipment ID.
        DB::beginTransaction();

        try {
            foreach ($documentFields as $field => $type) {
                if ($request->hasFile($field)) {
                    $file = $request->file($field);
                    $path = $file->store((string) $branchId, 'shipment_documents');
                    $storedPaths[] = $path;

                    ShipmentDocument::create([
                        'mbranchlist_id' => $branchId,
                        'shipment_id' => $validated['shipment_id'],
                        'document_type' => $type,
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $exception) {
            // Undo this batch if a file or database record could not be saved.
            DB::rollBack();
            Storage::disk('shipment_documents')->delete($storedPaths);

            throw $exception;
        }

        return redirect()->route('shipment-documents.index')
            ->with('success', 'Shipment documents uploaded successfully.');
    }

    public function destroy(Request $request, ShipmentDocument $shipmentDocument)
    {
        // A factory can only delete its own documents.
        $branchId = $request->user()->mbranchlist_id;
        abort_unless($branchId && $shipmentDocument->mbranchlist_id == $branchId, 403);

        $disk = Storage::disk('shipment_documents');

        if ($disk->exists($shipmentDocument->file_path) && !$disk->delete($shipmentDocument->file_path)) {
            return redirect()->route('shipment-documents.index')
                ->with('error', 'The file could not be deleted. Please try again.');
        }

        $shipmentDocument->delete();

        return redirect()->route('shipment-documents.index')
            ->with('success', 'Shipment document deleted successfully.');
    }

    public function document(Request $request, ShipmentDocument $shipmentDocument)
    {
        // Administrators can view scan documents; factories can open only their own.
        if (!$request->user()->isAdmin()) {
            $branchId = $request->user()->mbranchlist_id;
            abort_unless($branchId && $shipmentDocument->mbranchlist_id == $branchId, 403);
        }

        $disk = Storage::disk('shipment_documents');
        abort_unless($disk->exists($shipmentDocument->file_path), 404, 'File not found.');

        if ($request->boolean('download')) {
            return $disk->download($shipmentDocument->file_path, $shipmentDocument->original_name);
        }

        return $disk->response($shipmentDocument->file_path, $shipmentDocument->original_name);
    }
}
