@extends('layouts.app')

@section('header', 'Shipment Documents')

@section('content')
@php
    $documentFields = [
        'master_bl' => 'Master BL',
        'booking_confirmation' => 'Booking Confirmation',
        'container_loading_list' => 'Container Loading List',
    ];
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h3 class="h6 fw-bold mb-3"><i class="bi bi-upload me-2"></i>Upload Shipment Documents</h3>

        <form method="POST" action="{{ route('shipment-documents.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="shipment_id" class="form-label fw-bold">Shipment ID</label>
                    <input type="text" id="shipment_id" name="shipment_id"
                           class="form-control {{ $errors->has('shipment_id') ? 'is-invalid' : '' }}"
                           value="{{ old('shipment_id') }}" maxlength="255" placeholder="Enter shipment ID" required>
                    @error('shipment_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <h4 class="h6 fw-bold">Documents</h4>
            <p id="document_help" class="text-muted small">
                Upload one or more documents for this shipment. PDF, JPG, PNG,
                Word or Excel files. Maximum 5 MB per file.
            </p>

            <div class="row g-3 mb-4">
                @foreach($documentFields as $field => $label)
                    <div class="col-md-4">
                        <label for="{{ $field }}" class="form-label fw-bold">{{ $label }}</label>
                        <input type="file" id="{{ $field }}" name="{{ $field }}"
                               class="form-control {{ $errors->has($field) ? 'is-invalid' : '' }}"
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" aria-describedby="document_help">
                        @error($field)
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4">
                    <i class="bi bi-upload me-2"></i>Upload Shipment Documents
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h2 class="h5 fw-bold mb-3">
            <i class="bi bi-folder2-open me-2"></i>Shipment Documents
        </h2>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Shipment ID</th>
                        @foreach($documentFields as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse($shipmentRows as $shipmentRow)
                        <tr>
                            <td class="fw-semibold align-top text-nowrap">{{ $shipmentRow['shipment_id'] ?: 'Not assigned' }}</td>
                            @foreach($documentFields as $label)
                                <td class="align-top">
                                    <div class="d-flex flex-column gap-3">
                                        @forelse($shipmentRow['documents'][$label] ?? [] as $document)
                                            <div>
                                                <div class="fw-semibold text-break">{{ $document->original_name }}</div>
                                                <div class="text-muted small mt-1">{{ $document->created_at->format('d M Y') }}</div>
                                                <div class="d-flex flex-wrap gap-2 mt-2">
                                                    <a href="{{ route('shipment-documents.document', $document->id) }}"
                                                       class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                                       aria-label="View {{ $document->original_name }}">
                                                        View
                                                    </a>
                                                    <a href="{{ route('shipment-documents.document', ['shipmentDocument' => $document->id, 'download' => 1]) }}"
                                                       class="btn btn-sm btn-outline-secondary" aria-label="Download {{ $document->original_name }}">
                                                        <i class="bi bi-download me-1"></i>Download
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="modal" data-bs-target="#confirmModal"
                                                            data-action="{{ route('shipment-documents.destroy', $document->id) }}"
                                                            data-method="DELETE"
                                                            data-message="Delete {{ $document->original_name }}?"
                                                            data-submessage="The uploaded file will be permanently deleted. This cannot be undone."
                                                            aria-label="Delete {{ $document->original_name }}">
                                                        <i class="bi bi-trash me-1"></i>Delete
                                                    </button>
                                                </div>
                                            </div>
                                        @empty
                                            <span class="text-muted small">Not uploaded</span>
                                        @endforelse
                                    </div>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No shipment documents uploaded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $shipments->links() }}
    </div>
</div>
@endsection
