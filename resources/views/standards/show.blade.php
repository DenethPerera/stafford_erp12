@extends('layouts.app')

@section('header', 'Standards')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center border border-white shadow-sm" style="width: 56px; height: 56px;">
            <i class="bi bi-journal-bookmark fs-3"></i>
        </div>
        <div>
            <h2 class="h4 fw-bolder text-dark mb-1">Standard Details</h2>
            <p class="small text-muted fw-bold d-flex align-items-center mb-0">
                {{ $standard->name }} ({{ $standard->code }})
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('standards.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
            <i class="bi bi-arrow-left me-2"></i> Back to List
        </a>
        <a href="{{ route('standards.edit', $standard) }}" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4">
            <i class="bi bi-pencil me-2 fs-6"></i> Edit Standard
        </a>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 mx-auto" style="max-width: 900px;">
    <div class="card-body p-4 p-md-5">
        <div class="row g-5">
            <div class="col-md-6 d-flex flex-column gap-4">
                <div>
                    <p class="text-uppercase fw-black text-muted mb-1" style="font-size: 11px; letter-spacing: 1px;">Standard Code</p>
                    <p class="h5 fw-bolder text-dark mb-0">{{ $standard->code }}</p>
                </div>
                <div>
                    <p class="text-uppercase fw-black text-muted mb-1" style="font-size: 11px; letter-spacing: 1px;">Standard Name</p>
                    <p class="h5 fw-bolder text-dark mb-0">{{ $standard->name }}</p>
                </div>
            </div>
            
            <div class="col-md-6 d-flex flex-column gap-4">
                <div>
                    <p class="text-uppercase fw-black text-muted mb-1" style="font-size: 11px; letter-spacing: 1px;">Validity Period</p>
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-calendar3 text-muted"></i>
                        <p class="fw-bold text-dark mb-0">{{ $standard->validity_period_months }} Months</p>
                    </div>
                </div>
            </div>
        </div>
        
        @if($standard->description)
        <div class="mt-5 pt-4 border-top">
            <p class="text-uppercase fw-black text-muted mb-3" style="font-size: 11px; letter-spacing: 1px;">Description</p>
            <div class="bg-light p-4 rounded-3 border">
                <p class="fw-semibold text-secondary mb-0" style="line-height: 1.6;">{{ $standard->description }}</p>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
