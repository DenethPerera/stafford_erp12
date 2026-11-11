@extends('layouts.app')

@section('header', 'Forwarder Details and Documents')

@section('content')
@php
    $documentFields = [
        'cargo_security_certificate' => 'Cargo Security Certificate',
        'container_inspection_checklist' => 'Container Inspection Checklist',
        'consolidation_file' => 'Consolidation File',
    ];
@endphp

<div class="mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">
        Forwarder Details and Documents
    </h2>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <form
            method="POST"
            action="{{ route('forwarders.store') }}"
            enctype="multipart/form-data"
        >
            @csrf

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="company_name" class="form-label fw-bold">
                        Forwarder Company Name
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="company_name"
                        name="company_name"
                        class="form-control bg-light border-light py-2"
                        value="{{ old('company_name') }}"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="contact_person" class="form-label fw-bold">
                        Contact Person
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="text"
                        id="contact_person"
                        name="contact_person"
                        class="form-control bg-light border-light py-2"
                        value="{{ old('contact_person') }}"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold">
                        Email
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="form-control bg-light border-light py-2"
                        value="{{ old('email') }}"
                        maxlength="255"
                        required
                    >
                </div>

                <div class="col-md-6">
                    <label for="phone" class="form-label fw-bold">
                        Phone Number
                        <span class="text-danger">*</span>
                    </label>

                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control bg-light border-light py-2"
                        value="{{ old('phone') }}"
                        maxlength="50"
                        required
                    >
                </div>
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

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <h3 class="h5 fw-bold mb-3">Saved Forwarders</h3>

        <div class="table-responsive">
            <table id="forwarder-details-table" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>

                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($forwarders as $forwarder)
                        <tr>
                            <td>{{ $forwarder->company_name }}</td>
                            <td>{{ $forwarder->contact_person }}</td>
                            <td>{{ $forwarder->email }}</td>
                            <td>{{ $forwarder->phone }}</td>

                            <td>
                                <form
                                    method="POST"
                                    action="{{ route('forwarders.destroy', $forwarder) }}"
                                    onsubmit="return confirm('Delete this forwarder and all its uploaded documents? This cannot be undone.');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                No forwarders saved yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $forwarders->links('pagination::bootstrap-5') }}
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
            <h3 class="h5 fw-bold mb-0">Forwarder Documents</h3>

            <form method="GET" action="{{ route('forwarders.index') }}" class="position-relative"
                  style="width: 320px; max-width: 100%;">
                @if($forwarders->currentPage() > 1)
                    <input type="hidden" name="page" value="{{ $forwarders->currentPage() }}">
                @endif
                <label for="document_search" class="visually-hidden">Search documents by company name or Shipment ID</label>
                <input type="text" id="document_search" name="document_search"
                       class="form-control rounded-pill bg-white shadow-sm border-0 pe-5"
                       value="{{ $documentSearch }}" maxlength="255" placeholder="Search documents...">
                <button type="submit"
                        class="btn position-absolute top-50 end-0 translate-middle-y border-0 bg-transparent text-muted px-3 py-0"
                        aria-label="Search documents">
                    <i class="bi bi-search" aria-hidden="true"></i>
                </button>
            </form>
        </div>

        <div class="table-responsive">
            <table id="forwarder-documents-table" class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
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
                            <td>{{ $documentRecord->forwarder?->company_name ?? 'Not available' }}</td>
                            <td>{{ $documentRecord->shipment_id ?: 'Not assigned' }}</td>
                            @foreach($documentFields as $field => $label)
                                <td>
                                    @if($documentRecord->$field)
                                        <div class="d-flex flex-wrap gap-2">
                                            <a href="{{ route('forwarders.document', [
                                                    'forwarder' => $documentRecord->forwarder_id,
                                                    'document' => $field,
                                                    'forwarder_document_id' => $documentRecord->id,
                                                ]) }}"
                                               class="btn btn-sm btn-outline-primary" target="_blank" rel="noopener"
                                               aria-label="View {{ $label }} for {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}">
                                                View
                                            </a>
                                            <a href="{{ route('forwarders.document', [
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
                                        data-action="{{ route('forwarder-documents.destroy', $documentRecord->id) }}"
                                        data-method="DELETE"
                                        data-message="Delete documents for {{ $documentRecord->forwarder?->company_name ?? 'this forwarder' }} / {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}?"
                                        data-submessage="All uploaded files for this company and shipment will be permanently deleted. The forwarder company will remain."
                                        aria-label="Delete documents for {{ $documentRecord->shipment_id ?: 'unassigned shipment' }}">
                                    Delete
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                @if($documentSearch !== '')
                                    No documents match your search.
                                @else
                                    No forwarder documents uploaded yet.
                                @endif
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
