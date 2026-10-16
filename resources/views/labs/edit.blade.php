@extends('layouts.app')

@section('header', 'Edit Lab')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Edit Lab: {{ $lab->name }}</h2>
    <a href="{{ route('labs.index') }}" class="btn btn-outline-secondary fw-bold rounded-pill shadow-sm px-4">
        <i class="bi bi-arrow-left me-2"></i> Back to Labs
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-body p-4 p-md-5">
        <form method="POST" action="{{ route('labs.update', $lab) }}">
            @csrf
            @method('PUT')
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="name" class="form-label fw-bold text-dark small text-uppercase">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $lab->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mt-3 mt-md-0">
                    <label for="lab_code" class="form-label fw-bold text-dark small text-uppercase">Lab Code</label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('lab_code') is-invalid @enderror" id="lab_code" name="lab_code" value="{{ old('lab_code', $lab->lab_code) }}" placeholder="e.g. LAB-001">
                    @error('lab_code')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="email" class="form-label fw-bold text-dark small text-uppercase">Email</label>
                    <input type="email" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $lab->email) }}">
                    @error('email')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mt-3 mt-md-0">
                    <label for="phone" class="form-label fw-bold text-dark small text-uppercase">Phone</label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone', $lab->phone) }}">
                    @error('phone')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="contact_person" class="form-label fw-bold text-dark small text-uppercase">Contact Person</label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('contact_person') is-invalid @enderror" id="contact_person" name="contact_person" value="{{ old('contact_person', $lab->contact_person) }}">
                    @error('contact_person')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <label for="address_line1" class="form-label fw-bold text-dark small text-uppercase">Address Line 1</label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('address_line1') is-invalid @enderror" id="address_line1" name="address_line1" value="{{ old('address_line1', $lab->address_line1) }}" placeholder="Street address, P.O. Box">
                    @error('address_line1')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="col-md-6 mt-3 mt-md-0">
                    <label for="address_line2" class="form-label fw-bold text-dark small text-uppercase">Address Line 2</label>
                    <input type="text" class="form-control bg-light border-light py-2 px-3 fw-semibold @error('address_line2') is-invalid @enderror" id="address_line2" name="address_line2" value="{{ old('address_line2', $lab->address_line2) }}" placeholder="Suite, unit, building, floor">
                    @error('address_line2')
                        <div class="invalid-feedback fw-bold">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="mb-4">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" {{ $lab->is_active ? 'checked' : '' }} style="cursor: pointer;">
                    <label class="form-check-label fw-bold ms-2" for="is_active" style="cursor: pointer;">
                        Active Status
                    </label>
                </div>
            </div>

            <hr class="text-muted opacity-25 my-4">

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-5 py-2">
                    Update Lab
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
