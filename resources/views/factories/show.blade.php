@extends('layouts.app')

@section('header', 'Factories')

@section('content')
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center border border-white shadow-sm" style="width: 56px; height: 56px;">
            <i class="bi bi-building fs-3"></i>
        </div>
        <div>
            <h2 class="h4 fw-bolder text-dark mb-1">{{ $factory->name }}</h2>
            <p class="small text-muted fw-bold d-flex align-items-center mb-0">
                <i class="bi bi-geo-alt-fill me-1"></i> {{ $factory->location ?: 'Location not specified' }}
            </p>
        </div>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('factories.edit', $factory) }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
            <i class="bi bi-pencil me-2"></i> Edit
        </a>
        <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" data-bs-toggle="modal" data-bs-target="#addCertModal">
            <i class="bi bi-award me-2 fs-5"></i> Add Certificate
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h3 class="text-uppercase fw-black text-muted border-bottom pb-2 mb-3" style="font-size: 11px; letter-spacing: 1px;">Factory Information</h3>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px;">Registration Number</p>
                        <p class="fw-bolder text-dark mb-0">{{ $factory->registration_no ?: 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px;">Status</p>
                        @if($factory->status == 'active')
                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 text-uppercase fw-bolder" style="font-size: 10px;">
                                Active
                            </span>
                        @else
                            <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 text-uppercase fw-bolder" style="font-size: 10px;">
                                Inactive
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body p-4">
                <h3 class="text-uppercase fw-black text-muted border-bottom pb-2 mb-3" style="font-size: 11px; letter-spacing: 1px;">Contact Details</h3>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px;">Contact Person</p>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-person-fill text-muted"></i>
                            <p class="fw-bolder text-dark mb-0">{{ $factory->contact_person ?: 'N/A' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px;">Contact Phone</p>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-telephone-fill text-muted"></i>
                            <p class="fw-bold text-dark mb-0">{{ $factory->contact_phone ?: 'N/A' }}</p>
                        </div>
                    </div>
                    <div>
                        <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px;">Contact Email</p>
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-envelope-fill text-muted"></i>
                            <p class="fw-bold text-dark mb-0">{{ $factory->contact_email ?: 'N/A' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<h3 class="h5 fw-bolder text-dark mb-3 px-2">Certificates</h3>
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard & Cert Number</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Timeline</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factory->certificates as $certificate)
                    <tr>
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-award fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">{{ $certificate->standard->name ?? 'Unknown Standard' }}</h6>
                                    <div class="small text-muted fw-bold font-monospace">{{ $certificate->certificate_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-4">
                                <div>
                                    <p class="text-uppercase fw-black text-muted mb-0" style="font-size: 10px;">Issue Date</p>
                                    <p class="fw-bold text-dark small mb-0">{{ $certificate->issue_date->format('M d, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-uppercase fw-black text-muted mb-0" style="font-size: 10px;">Expiry Date</p>
                                    <p class="fw-bold text-dark small mb-0">{{ $certificate->expiry_date->format('M d, Y') }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @php
                                $badgeClass = match($certificate->status) {
                                    'valid' => 'bg-success bg-opacity-10 text-success border-success',
                                    'expiring_soon' => 'bg-warning bg-opacity-10 text-warning border-warning',
                                    'expired' => 'bg-danger bg-opacity-10 text-danger border-danger',
                                    'under_renewal' => 'bg-info bg-opacity-10 text-info border-info',
                                    default => 'bg-light text-secondary border-secondary'
                                };
                            @endphp
                            <span class="badge rounded-pill px-3 py-2 text-uppercase fw-bolder border border-opacity-25 {{ $badgeClass }}" style="font-size: 10px;">
                                {{ str_replace('_', ' ', $certificate->status) }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('certificates.show', $certificate) }}" class="btn btn-sm btn-light text-dark rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px;" title="Edit"
                                    onclick='openEditCertModal({!! json_encode([
                                        "id" => $certificate->id,
                                        "standard_id" => $certificate->standard_id,
                                        "certificate_number" => $certificate->certificate_number,
                                        "issue_date" => $certificate->issue_date->format("Y-m-d"),
                                        "expiry_date" => $certificate->expiry_date->format("Y-m-d"),
                                        "status" => $certificate->status,
                                        "notes" => $certificate->notes
                                    ]) !!})'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center border" style="width: 36px; height: 36px;" title="Delete"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmModal" 
                                    data-action="{{ route('certificates.destroy', $certificate) }}"
                                    data-message="Are you sure you want to delete certificate &quot;{{ addslashes($certificate->certificate_number) }}&quot;?"
                                    data-submessage="This action cannot be undone.">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center py-5">
                            <div class="d-flex flex-column justify-content-center align-items-center">
                                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mb-3 shadow-sm border border-white text-secondary" style="width: 64px; height: 64px;">
                                    <i class="bi bi-award fs-2"></i>
                                </div>
                                <h5 class="fw-bold text-dark mb-1">No Certificates Found</h5>
                                <p class="text-muted fw-semibold mb-4">This factory does not have any certificates recorded yet.</p>
                                <button type="button" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#addCertModal">
                                    Add First Certificate
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Add Certificate Modal -->
<div class="modal fade {{ $errors->any() && !old('_method') ? 'show' : '' }}" id="addCertModal" tabindex="-1" aria-labelledby="addCertModalLabel" aria-hidden="true" {!! $errors->any() && !old('_method') ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-award fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="addCertModalLabel">Add Certificate</h5>
                        <p class="small text-muted fw-semibold mb-0">For {{ $factory->name }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('certificates.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="factory_id" value="{{ $factory->id }}">
                
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard</label>
                            <select name="standard_id" id="add_standard_id" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2" required>
                                <option value="">Select Standard...</option>
                                @foreach($standards as $standard)
                                    <option value="{{ $standard->id }}" data-validity-months="{{ $standard->validity_period_months }}" {{ old('standard_id') == $standard->id ? 'selected' : '' }}>
                                        {{ $standard->name }} ({{ $standard->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Certificate Number</label>
                            <input type="text" name="certificate_number" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('certificate_number') }}" placeholder="e.g. CERT-2026-001" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Issue Date</label>
                            <input type="date" name="issue_date" id="add_issue_date" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('issue_date') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Expiry Date</label>
                            <input type="date" name="expiry_date" id="add_expiry_date" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('expiry_date') }}" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</label>
                            <select name="status" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2" required>
                                <option value="valid" {{ old('status') == 'valid' ? 'selected' : '' }}>Valid</option>
                                <option value="expiring_soon" {{ old('status') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                                <option value="expired" {{ old('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                                <option value="under_renewal" {{ old('status') == 'under_renewal' ? 'selected' : '' }}>Under Renewal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Document (PDF, JPG)</label>
                            <input type="file" name="document" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Notes (Optional)</label>
                        <textarea name="notes" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="2">{{ old('notes') }}</textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Save Certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Certificate Modal -->
<div class="modal fade {{ $errors->any() && old('_method') == 'PUT' ? 'show' : '' }}" id="editCertModal" tabindex="-1" aria-labelledby="editCertModalLabel" aria-hidden="true" {!! $errors->any() && old('_method') == 'PUT' ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editCertModalLabel">Edit Certificate</h5>
                        <p class="small text-muted fw-semibold mb-0" id="editCertNameDisplay"></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editCertForm" action="" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <input type="hidden" name="factory_id" value="{{ $factory->id }}">
                
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard</label>
                            <select name="standard_id" id="edit_standard_id" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2" required>
                                <option value="">Select Standard...</option>
                                @foreach($standards as $standard)
                                    <option value="{{ $standard->id }}" data-validity-months="{{ $standard->validity_period_months }}">
                                        {{ $standard->name }} ({{ $standard->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Certificate Number</label>
                            <input type="text" name="certificate_number" id="edit_certificate_number" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Issue Date</label>
                            <input type="date" name="issue_date" id="edit_issue_date" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Expiry Date</label>
                            <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</label>
                            <select name="status" id="edit_status" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2" required>
                                <option value="valid">Valid</option>
                                <option value="expiring_soon">Expiring Soon</option>
                                <option value="expired">Expired</option>
                                <option value="under_renewal">Under Renewal</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Update Document (Optional)</label>
                            <input type="file" name="document" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" accept=".pdf,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Notes</label>
                        <textarea name="notes" id="edit_notes" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Update Certificate</button>
                </div>
            </form>
        </div>
    </div>
</div>

@if($errors->any())
    <div class="modal-backdrop fade show"></div>
@endif

@endsection

@section('scripts')
<script>
    // Edit Modal functionality
    let editCertModalInstance = null;
    function openEditCertModal(cert) {
        document.getElementById('editCertNameDisplay').textContent = cert.certificate_number;
        document.getElementById('editCertForm').action = '/certificates/' + cert.id;
        
        document.getElementById('edit_standard_id').value = cert.standard_id || '';
        document.getElementById('edit_certificate_number').value = cert.certificate_number || '';
        document.getElementById('edit_issue_date').value = cert.issue_date || '';
        document.getElementById('edit_expiry_date').value = cert.expiry_date || '';
        document.getElementById('edit_status').value = cert.status || 'valid';
        document.getElementById('edit_notes').value = cert.notes || '';
        
        if (!editCertModalInstance) {
            editCertModalInstance = new bootstrap.Modal(document.getElementById('editCertModal'));
        }
        editCertModalInstance.show();
    }

    // Auto-calculate expiry date
    document.addEventListener('DOMContentLoaded', function() {
        function setupDateCalculator(standardSelectId, issueDateId, expiryDateId) {
            const standardSelect = document.getElementById(standardSelectId);
            const issueDateInput = document.getElementById(issueDateId);
            const expiryDateInput = document.getElementById(expiryDateId);

            if (!standardSelect || !issueDateInput || !expiryDateInput) return;

            function calculateExpiryDate() {
                const selectedOption = standardSelect.options[standardSelect.selectedIndex];
                const issueDateVal = issueDateInput.value;

                if (selectedOption && selectedOption.value && issueDateVal) {
                    const months = parseInt(selectedOption.getAttribute('data-validity-months'));
                    
                    if (!isNaN(months)) {
                        const date = new Date(issueDateVal);
                        date.setMonth(date.getMonth() + months);
                        
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        
                        expiryDateInput.value = `${year}-${month}-${day}`;
                    }
                }
            }

            standardSelect.addEventListener('change', calculateExpiryDate);
            issueDateInput.addEventListener('change', calculateExpiryDate);
        }

        setupDateCalculator('add_standard_id', 'add_issue_date', 'add_expiry_date');
        setupDateCalculator('edit_standard_id', 'edit_issue_date', 'edit_expiry_date');
    });
</script>
@endsection
