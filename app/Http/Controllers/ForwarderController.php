<?php

namespace App\Http\Controllers;

use App\Models\Forwarder;
use App\Models\ForwarderDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ForwarderController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'document_search' => 'nullable|string|max:255',
        ]);
        $documentSearch = trim($validated['document_search'] ?? '');

        $forwarders = Forwarder::orderBy('id', 'desc')
            ->paginate(10)
            ->withQueryString();

        $documentQuery = ForwarderDocument::with('forwarder');

        if ($documentSearch !== '') {
            $companyIds = Forwarder::where('company_name', 'like', '%' . $documentSearch . '%')
                ->pluck('id');

            $documentQuery->where('shipment_id', 'like', '%' . $documentSearch . '%')
                ->orWhereIn('forwarder_id', $companyIds);
        }

        $documents = $documentQuery
            ->orderBy('id', 'desc')
            ->paginate(10, ['*'], 'documents_page')
            ->withQueryString();

        return view('forwarders.index', compact('forwarders', 'documents', 'documentSearch'));
    }

    public function fileUpload(Request $request)
    {
        $forwarder = $request->user()->forwarder;

        abort_unless(
            $forwarder,
            403,
            'Your account is not assigned to a forwarder.'
        );

        $documents = ForwarderDocument::where('forwarder_id', $forwarder->id)
            ->orderBy('id', 'desc')
            ->paginate(10);

        return view('forwarders.file-upload', compact('documents'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
        ]);

        Forwarder::create($validated);

        return redirect()
            ->route('forwarders.index')
            ->with('success', 'Forwarder details saved successfully.');
    }

    public function storeDocuments(Request $request)
    {
        // Always use the assignment on the logged-in account.
        $forwarder = $request->user()->forwarder;

        abort_unless(
            $forwarder,
            403,
            'Your account is not assigned to a forwarder.'
        );

        $validated = $request->validate([
            'shipment_id' => 'required|string|max:255',
            'cargo_security_certificate' =>
                'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

            'container_inspection_checklist' =>
                'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',

            'consolidation_file' =>
                'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $documentFields = [
            'cargo_security_certificate',
            'container_inspection_checklist',
            'consolidation_file',
        ];

        $documentPaths = [];

        try {
            foreach ($documentFields as $field) {
                if ($request->hasFile($field)) {
                    $documentPaths[$field] = $request->file($field)->store(
                        $forwarder->id . '/' . $field,
                        'forwarders'
                    );
                }
            }

            if (empty($documentPaths)) {
                return redirect()
                    ->route('forwarder.file-upload')
                    ->with('error', 'Please select at least one document.');
            }

            ForwarderDocument::updateOrCreate(
                [
                    'forwarder_id' => $forwarder->id,
                    'shipment_id' => $validated['shipment_id'],
                ],
                $documentPaths
            );
        } catch (\Throwable $exception) {
            // Remove only the new files if this upload could not be saved.
            Storage::disk('forwarders')->delete(array_values($documentPaths));

            throw $exception;
        }

        return redirect()
            ->route('forwarder.file-upload')
            ->with('success', 'Documents uploaded successfully.');
    }

    public function destroyDocuments(Request $request, ForwarderDocument $forwarderDocument)
    {
        $redirectRoute = 'forwarders.index';

        if ($request->routeIs('forwarder.documents.destroy')) {
            // Forwarder logins can delete only their assigned company's records.
            $assignedForwarderId = $request->user()->forwarder_id;
            abort_unless(
                $assignedForwarderId && $assignedForwarderId == $forwarderDocument->forwarder_id,
                403
            );
            $redirectRoute = 'forwarder.file-upload';
        }

        $disk = Storage::disk('forwarders');

        foreach ([
            'cargo_security_certificate',
            'container_inspection_checklist',
            'consolidation_file',
        ] as $field) {
            $path = $forwarderDocument->$field;

            if ($path && $disk->exists($path) && !$disk->delete($path)) {
                return redirect()->route($redirectRoute)
                    ->with('error', 'A forwarder document could not be deleted. Please try again.');
            }
        }

        $forwarderDocument->delete();

        return redirect()->route($redirectRoute)
            ->with('success', 'Forwarder shipment documents deleted successfully.');
    }

    public function destroy(Forwarder $forwarder)
    {
        $disk = Storage::disk('forwarders');

        foreach ([
            'cargo_security_certificate',
            'container_inspection_checklist',
            'consolidation_file',
        ] as $field) {
            $paths = [$forwarder->$field];

            foreach ($forwarder->documentFiles as $documentRecord) {
                $paths[] = $documentRecord->$field;
            }

            foreach (array_unique(array_filter($paths)) as $path) {
                if ($disk->exists($path) && !$disk->delete($path)) {
                    return redirect()->route('forwarders.index')
                        ->with('error', 'A forwarder document could not be deleted. Please try again.');
                }
            }
        }

        $forwarder->delete();

        return redirect()->route('forwarders.index')
            ->with('success', 'Forwarder deleted successfully.');
    }

    public function document(
        Request $request,
        Forwarder $forwarder,
        string $document
    ) {
        // A forwarder login can open only its assigned company's documents.
        if (!$request->user()->isAdmin()) {
            $assignedForwarderId = $request->user()->forwarder_id;
            abort_unless($assignedForwarderId && $assignedForwarderId == $forwarder->id, 403);
        }

        $allowedDocuments = [
            'cargo_security_certificate',
            'container_inspection_checklist',
            'consolidation_file',
        ];

        // Only allow access to these three document fields.
        abort_unless(
            in_array($document, $allowedDocuments, true),
            404
        );

        if ($request->has('forwarder_document_id')) {
            $validated = $request->validate([
                'forwarder_document_id' => 'required|integer|min:1',
            ]);

            // Look up the selected shipment record only within this forwarder.
            $documentRecord = $forwarder->documentFiles()
                ->findOrFail($validated['forwarder_document_id']);
            $path = $documentRecord->$document;
        } else {
            // Keep older links working, including documents in the old columns.
            $documentRecord = $forwarder->documentFiles()
                ->whereNotNull($document)
                ->orderBy('id', 'desc')
                ->first();
            $path = $documentRecord?->$document ?? $forwarder->$document;
        }

        abort_unless($path, 404);

        $disk = Storage::disk('forwarders');

        abort_unless($disk->exists($path), 404);

        if ($request->boolean('download')) {
            return $disk->download($path);
        }

        return $disk->response($path);
    }
}
