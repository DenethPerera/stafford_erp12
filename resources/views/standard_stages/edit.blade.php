@extends('layouts.app')

@section('header', 'Renewal Stages Configuration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
            <i class="bi bi-pencil fs-4"></i>
        </div>
        <h2 class="h5 fw-bolder text-dark mb-0">Edit Renewal Stage</h2>
    </div>
    <a href="{{ route('standard-stages.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 mx-auto" style="max-width: 800px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('standard-stages.update', $standardStage) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="mb-4">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard</label>
                <select name="standard_id" class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" required>
                    @foreach($standards as $standard)
                        <option value="{{ $standard->id }}" {{ old('standard_id', $standardStage->standard_id) == $standard->id ? 'selected' : '' }}>
                            {{ $standard->name }} ({{ $standard->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            
            <div class="row g-4 mb-4">
                <div class="col-md-8">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Stage Name</label>
                    <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('name', $standardStage->name) }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Order Sequence</label>
                    <input type="number" name="order_sequence" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('order_sequence', $standardStage->order_sequence) }}" min="1" required>
                </div>
            </div>
            
            <div class="mb-5">
                <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description (Optional)</label>
                <textarea name="description" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" rows="3">{{ old('description', $standardStage->description) }}</textarea>
            </div>
            
            <div class="pt-4 border-top d-flex justify-content-end gap-3">
                <a href="{{ route('standard-stages.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm px-4 py-2 border text-secondary">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Update Stage
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
