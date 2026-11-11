@extends('layouts.app')

@section('header', 'Forwarder File Upload')

@section('content')
@php
    $documentFields = [
        'cargo_security_certificate' => 'Cargo Security Certificate',
        'container_inspection_checklist' => 'Container Inspection Checklist',
        'consolidation_file' => 'Consolidation File',
    ];
@endphp

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form
            method="POST"
            action="{{ route('forwarder.file-upload.store') }}"
            enctype="multipart/form-data"
        >
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

            <h3 class="h6 fw-bold">Documents</h3>

            <p class="text-muted small">
                Documents are optional. Upload PDF, JPG/JPEG or PNG files,
                up to 5 MB per file.
            </p>

            <div class="row g-3 mb-4">
                @foreach($documentFields as $field => $label)
                    <div class="col-md-4">
                        <label for="{{ $field }}" class="form-label fw-bold">
                            {{ $label }}
                        </label>

                        <input
                            type="file"
                            id="{{ $field }}"
                            name="{{ $field }}"
                            class="form-control"
                            accept=".pdf,.jpg,.jpeg,.png"
                        >
                    </div>
                @endforeach
            </div>

            <div class="d-flex justify-content-end">
                <button
                    type="submit"
                    class="btn btn-primary fw-bold rounded-pill px-4"
                >
                    Save Forwarder
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <h3 class="h5 fw-bold mb-3">Uploaded Documents</h3>

        <div class="table-responsive">
            <table id="forwarder-uploaded-documents-table" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Shipment ID</th>
                        @foreach($documentFields as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $documentRecord)
                        <tr>
                            <td class="fw-semibold">{{ $documentRecord->shipment_id ?: 'Not assigned' }}</td>
                            @foreach($documentFields as $field => $label)
                                <td>
                                    @if($documentRecord->$field)
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('forwarder.documents.show', [
                                                    'forwarder' => $documentRecord->forwarder_id,
                                                    'document' => $field,
                                                    'forwarder_document_id' => $documentRecord->id,
                                                ]) }}"
                                               class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                               aria-label="View {{ $label }} for {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}">
                                                View
                                            </a>
                                            <a href="{{ route('forwarder.documents.show', [
                                                    'forwarder' => $documentRecord->forwarder_id,
                                                    'document' => $field,
                                                    'forwarder_document_id' => $documentRecord->id,
                                                    'download' => 1,
                                                ]) }}"
                                               class="btn btn-sm btn-outline-secondary"
                                               aria-label="Download {{ $label }} for {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}">
                                                Download
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-muted small">Not uploaded</span>
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-danger"
                                        data-bs-toggle="modal" data-bs-target="#confirmModal"
                                        data-action="{{ route('forwarder.documents.destroy', $documentRecord->id) }}"
                                        data-method="DELETE"
                                        data-message="Delete documents for shipment {{ $documentRecord->shipment_id ?: 'Not assigned' }}?"
                                        data-submessage="All uploaded files for this shipment will be permanently deleted."
                                        aria-label="Delete documents for {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No documents uploaded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $documents->links('pagination::bootstrap-5') }}
    </div>
</div>
@endsection
