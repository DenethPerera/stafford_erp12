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
        Forwarder Details and Document Upload
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

            <hr class="my-4">

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
        <h3 class="h5 fw-bold mb-3">Saved Forwarders</h3>

        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Company Name</th>
                        <th>Contact Person</th>
                        <th>Email</th>
                        <th>Phone</th>

                        @foreach($documentFields as $label)
                            <th>{{ $label }}</th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse($forwarders as $forwarder)
                        <tr>
                            <td>{{ $forwarder->company_name }}</td>
                            <td>{{ $forwarder->contact_person }}</td>
                            <td>{{ $forwarder->email }}</td>
                            <td>{{ $forwarder->phone }}</td>

                            @foreach($documentFields as $field => $label)
                                <td>
                                    @if($forwarder->$field)
                                        <div class="d-flex gap-2">
                                            <a
                                                href="{{ route('forwarders.document', [
                                                    'forwarder' => $forwarder->id,
                                                    'document' => $field,
                                                ]) }}"
                                                target="_blank"
                                                rel="noopener"
                                                class="btn btn-sm btn-outline-primary"
                                            >
                                                View
                                            </a>

                                            <a
                                                href="{{ route('forwarders.document', [
                                                    'forwarder' => $forwarder->id,
                                                    'document' => $field,
                                                    'download' => 1,
                                                ]) }}"
                                                class="btn btn-sm btn-outline-secondary"
                                            >
                                                Download
                                            </a>
                                        </div>
                                    @else
                                        <span class="text-muted small">
                                            Not uploaded
                                        </span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
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
@endsection
