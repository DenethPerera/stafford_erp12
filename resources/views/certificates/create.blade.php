@extends('layouts.app')

@section('header', 'Certificates')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary border border-white shadow-sm" style="width: 48px; height: 48px;">
            <i class="bi bi-file-earmark-text-fill fs-4"></i>
        </div>
        <div>
            <h2 class="h4 fw-bolder text-dark mb-0">Add Certificate</h2>
            <p class="small fw-bold text-secondary mb-0">For {{ $factory->name }}</p>
        </div>
    </div>
    <a href="{{ route('factories.show', $factory->id) }}" class="btn btn-light text-secondary border fw-bold rounded-pill shadow-sm px-4">
        Back to Factory
    </a>
</div>

<div class="bubble-card p-4 p-md-5 w-100 mx-auto" style="max-width: 900px;">
    <form action="{{ route('certificates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        <input type="hidden" name="factory_id" value="{{ $factory->id }}">
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Standard</label>
                <select name="standard_id" class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" required>
                    <option value="">Select Standard...</option>
                    @foreach($standards as $standard)
                        <option value="{{ $standard->id }}" data-validity-months="{{ $standard->validity_period_months }}" {{ old('standard_id') == $standard->id ? 'selected' : '' }}>
                            {{ $standard->name }} ({{ $standard->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Certificate Number</label>
                <input type="text" name="certificate_number" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('certificate_number') }}" placeholder="e.g. CERT-2026-001">
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Issue Date</label>
                <input type="date" name="issue_date" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('issue_date') }}" required>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Expiry Date</label>
                <input type="date" name="expiry_date" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" value="{{ old('expiry_date') }}" required>
            </div>
        </div>
        
        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Status</label>
                <select name="status" class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" required>
                    <option value="valid" {{ old('status') == 'valid' ? 'selected' : '' }}>Valid</option>
                    <option value="expiring_soon" {{ old('status') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                    <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="under_renewal" {{ old('status') == 'under_renewal' ? 'selected' : '' }}>Under Renewal</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-bold text-dark small mb-2 ms-1">Document (PDF, JPG, PNG)</label>
                <input type="file" name="document" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" accept=".pdf,.jpg,.jpeg,.png">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label fw-bold text-dark small mb-2 ms-1">Notes (Optional)</label>
            <textarea name="notes" class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2" rows="3">{{ old('notes') }}</textarea>
        </div>
        
        <div class="pt-4 border-top mt-5 d-flex justify-content-end gap-3">
            <a href="{{ route('factories.show', $factory->id) }}" class="btn btn-light fw-bold rounded-pill px-4 shadow-sm border text-secondary">
                Cancel
            </a>
            <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4">
                Save Certificate
            </button>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const standardSelect = document.querySelector('select[name="standard_id"]');
        const issueDateInput = document.querySelector('input[name="issue_date"]');
        const expiryDateInput = document.querySelector('input[name="expiry_date"]');

        function calculateExpiryDate() {
            const selectedOption = standardSelect.options[standardSelect.selectedIndex];
            const issueDateVal = issueDateInput.value;

            if (selectedOption && selectedOption.value && issueDateVal) {
                const months = parseInt(selectedOption.getAttribute('data-validity-months'));
                
                if (!isNaN(months)) {
                    // Calculate new date
                    const date = new Date(issueDateVal);
                    date.setMonth(date.getMonth() + months);
                    
                    // Format to YYYY-MM-DD
                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');
                    
                    expiryDateInput.value = `${year}-${month}-${day}`;
                }
            }
        }

        standardSelect.addEventListener('change', calculateExpiryDate);
        issueDateInput.addEventListener('change', calculateExpiryDate);
    });
</script>
@endsection
