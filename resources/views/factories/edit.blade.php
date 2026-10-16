@extends('layouts.app')

@section('header', 'Factories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="bi bi-pencil fs-4"></i>
        </div>
        <h2 class="h5 fw-bolder text-dark mb-0">Edit Factory</h2>
    </div>
    <a href="{{ route('factories.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 mx-auto" style="max-width: 800px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('factories.update', $factory) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Factory Name</label>
                <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('name', $factory->name) }}" required>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Location / Address</label>
                <input type="text" name="location" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('location', $factory->location) }}">
            </div>
            
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Registration No.</label>
                <input type="text" name="registration_no" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('registration_no', $factory->registration_no) }}">
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Primary Contact</label>
                    <input type="text" name="contact_person" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('contact_person', $factory->contact_person) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Phone</label>
                    <input type="text" name="contact_phone" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('contact_phone', $factory->contact_phone) }}">
                </div>
            </div>
            
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('contact_email', $factory->contact_email) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</label>
                    <select name="status" class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2">
                        <option value="active" {{ old('status', $factory->status) == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $factory->status) == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="pt-4 border-top d-flex justify-content-end gap-3">
                <a href="{{ route('factories.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm px-4 py-2 border text-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Update Factory
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
