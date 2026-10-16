@extends('layouts.app')

@section('header', 'Standards')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0 px-2">Compliance Standards</h2>
    <div class="d-flex align-items-center gap-3 w-100 w-sm-auto">
        <div class="position-relative flex-grow-1 flex-sm-grow-0">
            <input type="text" id="standardSearch" class="form-control rounded-pill bg-white shadow-sm border-0 pe-5 w-100" placeholder="Search standards..." style="max-width: 250px;">
            <i class="bi bi-search position-absolute text-muted" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
        <button type="button" class="btn fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" style="background: linear-gradient(135deg, #124E78, #00A39A); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addStandardModal">
            <i class="bi bi-plus-lg me-2"></i> Add Standard
        </button>
    </div>
</div>

<div class="row g-4">
    @forelse($standards as $standard)
        <div class="col-md-6 col-lg-4 standard-card" data-search="{{ strtolower($standard->name . ' ' . $standard->code) }}">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative">
                <div class="position-absolute rounded-circle opacity-25" style="background: linear-gradient(135deg, #124E78, #00A39A); width: 120px; height: 120px; top: -30px; right: -30px; filter: blur(20px);"></div>
                
                <div class="card-body p-4 d-flex flex-column position-relative z-1">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-dark text-white rounded-pill px-3 py-2 text-uppercase fw-bolder shadow-sm tracking-wider" style="font-size: 10px; letter-spacing: 1px;">
                            {{ $standard->code }}
                        </span>
                    </div>
                    
                    <h4 class="h5 fw-bolder text-dark mb-2">{{ $standard->name }}</h4>
                    
                    <div class="mt-auto pt-4 border-top d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-uppercase fw-black text-muted mb-0" style="font-size: 10px; letter-spacing: 1px;">Validity</p>
                            <p class="fw-bold text-dark small mb-0">{{ $standard->validity_period_months }} Months</p>
                        </div>
                        
                        <div class="d-flex align-items-center gap-1">
                            <a href="{{ route('standards.show', $standard) }}" class="btn btn-sm btn-light text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button type="button" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit"
                                onclick='openEditModal({!! json_encode([
                                    "id" => $standard->id,
                                    "code" => $standard->code,
                                    "name" => $standard->name,
                                    "description" => $standard->description,
                                    "validity_period_months" => $standard->validity_period_months
                                ]) !!})'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Delete"
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmModal" 
                                data-action="{{ route('standards.destroy', $standard) }}"
                                data-message="Are you sure you want to delete &quot;{{ addslashes($standard->name) }}&quot;?"
                                data-submessage="This may affect existing certificates.">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12 text-center py-5">
            <div class="card border-0 shadow-sm rounded-4 bg-white">
                <div class="card-body p-5">
                    <p class="text-muted fw-bold mb-0">No standards found.</p>
                </div>
            </div>
        </div>
    @endforelse
    <div class="col-12 text-center py-5" id="noResultsMsg" style="display: none;">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-5">
                <p class="text-muted fw-bold mb-0">No standards match your search.</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Standard Modal -->
<div class="modal fade {{ $errors->any() && !old('_method') ? 'show' : '' }}" id="addStandardModal" tabindex="-1" aria-labelledby="addStandardModalLabel" aria-hidden="true" {!! $errors->any() && !old('_method') ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(0, 163, 154, 0.1); color: #00A39A;">
                        <i class="bi bi-journal-bookmark fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark" id="addStandardModalLabel">Add Standard</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('standards.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Code</label>
                            <input type="text" name="code" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('code') }}" placeholder="e.g., ISO-9001" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Name</label>
                            <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('name') }}" placeholder="e.g., Quality Management System" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description</label>
                        <textarea name="description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3">{{ old('description') }}</textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Period (Months)</label>
                        <input type="number" name="validity_period_months" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('validity_period_months', 12) }}" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold rounded-pill px-4 py-2 shadow-sm text-white" style="background: linear-gradient(135deg, #124E78, #00A39A); border: none;">Save Standard</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Standard Modal -->
<div class="modal fade {{ $errors->any() && old('_method') == 'PUT' ? 'show' : '' }}" id="editStandardModal" tabindex="-1" aria-labelledby="editStandardModalLabel" aria-hidden="true" {!! $errors->any() && old('_method') == 'PUT' ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: rgba(0, 163, 154, 0.1); color: #00A39A;">
                        <i class="bi bi-pencil fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editStandardModalLabel">Edit Standard</h5>
                        <p class="small text-muted fw-semibold mb-0" id="editStandardNameDisplay"></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editStandardForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Code</label>
                            <input type="text" name="code" id="edit_code" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description</label>
                        <textarea name="description" id="edit_description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Period (Months)</label>
                        <input type="number" name="validity_period_months" id="edit_validity_period_months" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold rounded-pill px-4 py-2 shadow-sm text-white" style="background: linear-gradient(135deg, #124E78, #00A39A); border: none;">Update Standard</button>
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
    // Search functionality
    document.getElementById('standardSearch')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const cards = document.querySelectorAll('.standard-card');
        let hasVisible = false;
        
        cards.forEach(card => {
            const searchData = card.getAttribute('data-search');
            if (searchData.includes(query)) {
                card.style.display = '';
                hasVisible = true;
            } else {
                card.style.display = 'none';
            }
        });
        
        const noResultsMsg = document.getElementById('noResultsMsg');
        if (noResultsMsg) {
            noResultsMsg.style.display = hasVisible ? 'none' : 'block';
        }
    });

    // Edit Modal functionality
    let editModalInstance = null;
    function openEditModal(standard) {
        document.getElementById('editStandardNameDisplay').textContent = standard.name;
        document.getElementById('editStandardForm').action = '/standards/' + standard.id;
        
        document.getElementById('edit_code').value = standard.code || '';
        document.getElementById('edit_name').value = standard.name || '';
        document.getElementById('edit_description').value = standard.description || '';
        document.getElementById('edit_validity_period_months').value = standard.validity_period_months || 12;
        
        const modalEl = document.getElementById('editStandardModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }
</script>
@endsection
