@extends('layouts.app')

@section('header', 'Standards')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="bi bi-journal-bookmark fs-4"></i>
        </div>
        <h2 class="h5 fw-bolder text-dark mb-0">Add Standard</h2>
    </div>
    <a href="{{ route('standards.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 mx-auto" style="max-width: 800px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('standards.store') }}" method="POST">
            @csrf
            
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Code</label>
                    <input type="text" name="code" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('code') }}" placeholder="e.g., ISO-9001" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Name</label>
                    <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('name') }}" placeholder="e.g., Quality Management System" required>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description</label>
                <textarea name="description" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" rows="4">{{ old('description') }}</textarea>
            </div>
            
            <div class="row g-4 mb-5">
                <div class="col-md-6">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Period (Months)</label>
                    <input type="number" name="validity_period_months" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('validity_period_months', 12) }}" required>
                </div>
            </div>
            
            <div class="pt-4 border-top d-flex justify-content-end gap-3">
                <a href="{{ route('standards.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm px-4 py-2 border text-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Save Standard
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
