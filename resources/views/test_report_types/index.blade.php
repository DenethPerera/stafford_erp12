@extends('layouts.app')

@section('header', 'Test Report Types')

@section('content')
<div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3 mb-4">
    <div>
        <h2 class="h5 fw-bolder text-dark mb-1 px-2">Compliance Test Standards & Report Types</h2>
        <p class="small text-muted fw-semibold mb-0 px-2">Master catalog of standardized laboratory safety test protocols and validity periods.</p>
    </div>
    <div class="d-flex align-items-center gap-3 w-100 w-sm-auto">
        <div class="position-relative flex-grow-1 flex-sm-grow-0">
            <input type="text" id="testTypeSearch" class="form-control rounded-pill bg-white shadow-sm border-0 pe-5 w-100" placeholder="Search test types..." style="max-width: 250px;">
            <i class="bi bi-search position-absolute text-muted" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
        <button type="button" class="btn fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" style="background: linear-gradient(135deg, #124E78, #00A39A); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addTestTypeModal">
            <i class="bi bi-plus-lg me-2"></i> Add Test Type
        </button>
    </div>
</div>

<div class="row g-4">
    @forelse($testReportTypes as $item)
        <div class="col-md-6 col-lg-4 test-type-card" data-search="{{ strtolower($item->name . ' ' . $item->code . ' ' . $item->category . ' ' . $item->standard_citation) }}">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative bubble-card hover-shadow-bubble transition-all">
                <div class="position-absolute rounded-circle opacity-25" style="background: linear-gradient(135deg, #124E78, #00A39A); width: 120px; height: 120px; top: -30px; right: -30px; filter: blur(20px);"></div>
                
                <div class="card-body p-4 d-flex flex-column position-relative z-1">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <span class="badge bg-dark text-white rounded-pill px-3 py-2 text-uppercase fw-bolder shadow-sm tracking-wider" style="font-size: 11px; letter-spacing: 0.5px;">
                            {{ $item->code }}
                        </span>
                        @if($item->category)
                            <span class="badge bg-light text-secondary rounded-pill px-2 py-1 fw-bold border" style="font-size: 10px;">
                                {{ $item->category }}
                            </span>
                        @endif
                    </div>
                    
                    <h4 class="h5 fw-bolder text-dark mb-2" style="min-height: 48px;">{{ $item->name }}</h4>
                    
                    @if($item->standard_citation)
                        <div class="d-flex align-items-center gap-1 mb-2 text-primary small fw-bold">
                            <i class="bi bi-bookmark-check-fill" style="font-size: 12px;"></i>
                            <span>{{ $item->standard_citation }}</span>
                        </div>
                    @endif

                    <p class="text-muted small mb-3 flex-grow-1" style="font-size: 12px; line-height: 1.5; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                        {{ $item->description ?: 'Standardized safety testing criteria for product qualification.' }}
                    </p>
                    
                    <div class="mt-auto pt-3 border-top d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-uppercase fw-black text-muted mb-0" style="font-size: 9px; letter-spacing: 1px;">Validity</p>
                            <p class="fw-bold text-dark small mb-0 d-flex align-items-center gap-1">
                                <span class="badge rounded-pill {{ $item->default_validity_days <= 100 ? 'bg-warning text-dark' : ($item->default_validity_days >= 730 ? 'bg-info text-dark' : 'bg-primary text-white') }} px-2 py-1">
                                    {{ $item->default_validity_days }} Days
                                </span>
                                @if($item->default_validity_days == 365)
                                    <span class="text-muted fw-semibold" style="font-size: 11px;">(1 Year)</span>
                                @elseif($item->default_validity_days == 730)
                                    <span class="text-muted fw-semibold" style="font-size: 11px;">(2 Years)</span>
                                @endif
                            </p>
                        </div>
                        
                        <div class="d-flex align-items-center gap-1">
                            <button type="button" class="btn btn-sm btn-light text-secondary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;" title="View Details"
                                onclick='openViewModal({!! json_encode($item) !!})'>
                                <i class="bi bi-eye"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;" title="Edit"
                                onclick='openEditModal({!! json_encode($item) !!})'>
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center shadow-sm" style="width: 34px; height: 34px;" title="Delete"
                                data-bs-toggle="modal" 
                                data-bs-target="#confirmModal" 
                                data-action="{{ route('test-report-types.destroy', $item) }}"
                                data-message="Delete test report type &quot;{{ addslashes($item->name) }}&quot;?"
                                data-submessage="This record will be permanently removed.">
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
                    <i class="bi bi-card-checklist display-4 text-muted mb-3 d-block"></i>
                    <p class="text-muted fw-bold mb-0">No test report types found. Click "+ Add Test Type" to create one.</p>
                </div>
            </div>
        </div>
    @endforelse
    <div class="col-12 text-center py-5" id="noResultsMsg" style="display: none;">
        <div class="card border-0 shadow-sm rounded-4 bg-white">
            <div class="card-body p-5">
                <p class="text-muted fw-bold mb-0">No test report types match your search.</p>
            </div>
        </div>
    </div>
</div>

<!-- Add Test Type Modal -->
<div class="modal fade" id="addTestTypeModal" tabindex="-1" aria-labelledby="addTestTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: rgba(0, 163, 154, 0.1); color: #00A39A;">
                        <i class="bi bi-plus-circle-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="addTestTypeModalLabel">Add Test Report Type</h5>
                        <p class="small text-muted fw-semibold mb-0">Define standard safety test protocol and validity period</p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('test-report-types.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Code / Identifier*</label>
                            <input type="text" name="code" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" placeholder="e.g. FLAM-1610, ASTM-F963" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard / Regulation Citation</label>
                            <input type="text" name="standard_citation" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" placeholder="e.g. 16 CFR 1610">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Test Name / Title*</label>
                            <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" placeholder="e.g. Flammability of Clothing Textiles" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Category</label>
                            <select name="category" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2">
                                <option value="Flammability">Flammability</option>
                                <option value="Chemical">Chemical / Lead</option>
                                <option value="Physical/Mechanical">Physical/Mechanical</option>
                                <option value="Quality/Performance">Quality/Performance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Period (Days)*</label>
                        <div class="input-group">
                            <input type="number" name="default_validity_days" id="addValidityInput" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="365" required>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('addValidityInput').value = 100;">100 Days</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('addValidityInput').value = 365;">365 Days (1 Yr)</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('addValidityInput').value = 730;">730 Days (2 Yrs)</button>
                        </div>
                        <small class="text-muted">Standard safety test validity duration before renewal testing is mandatory.</small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description / Scope</label>
                        <textarea name="description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3" placeholder="Brief technical summary of this test standard..."></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold rounded-pill px-4 py-2 shadow-sm text-white" style="background: linear-gradient(135deg, #124E78, #00A39A); border: none;">Save Test Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Test Type Modal -->
<div class="modal fade" id="editTestTypeModal" tabindex="-1" aria-labelledby="editTestTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 42px; height: 42px; background: rgba(0, 163, 154, 0.1); color: #00A39A;">
                        <i class="bi bi-pencil-fill fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editTestTypeModalLabel">Edit Test Report Type</h5>
                        <p class="small text-muted fw-semibold mb-0" id="editTestTypeNameDisplay"></p>
                    </div>
                </div>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editTestTypeForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Code / Identifier*</label>
                            <input type="text" name="code" id="edit_code" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Standard / Regulation Citation</label>
                            <input type="text" name="standard_citation" id="edit_standard_citation" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Test Name / Title*</label>
                            <input type="text" name="name" id="edit_name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Category</label>
                            <select name="category" id="edit_category" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2">
                                <option value="Flammability">Flammability</option>
                                <option value="Chemical">Chemical / Lead</option>
                                <option value="Physical/Mechanical">Physical/Mechanical</option>
                                <option value="Quality/Performance">Quality/Performance</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Validity Period (Days)*</label>
                        <div class="input-group">
                            <input type="number" name="default_validity_days" id="edit_validity_days" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('edit_validity_days').value = 100;">100 Days</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('edit_validity_days').value = 365;">365 Days (1 Yr)</button>
                            <button type="button" class="btn btn-outline-secondary fw-bold" onclick="document.getElementById('edit_validity_days').value = 730;">730 Days (2 Yrs)</button>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Description / Scope</label>
                        <textarea name="description" id="edit_description" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn fw-bold rounded-pill px-4 py-2 shadow-sm text-white" style="background: linear-gradient(135deg, #124E78, #00A39A); border: none;">Update Test Type</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- View Test Type Modal -->
<div class="modal fade" id="viewTestTypeModal" tabindex="-1" aria-labelledby="viewTestTypeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <span class="badge bg-dark text-white rounded-pill px-3 py-2 text-uppercase fw-bolder tracking-wider" id="view_badge_code"></span>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body px-4 py-4">
                <h4 class="h5 fw-bolder text-dark mb-2" id="view_title"></h4>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary fw-bold" id="view_citation"></span>
                    <span class="badge bg-light text-secondary border fw-bold" id="view_category"></span>
                </div>
                <div class="p-3 bg-light rounded-3 mb-3">
                    <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Standard Validity Period</p>
                    <h5 class="fw-bolder text-dark mb-0" id="view_validity"></h5>
                </div>
                <div>
                    <p class="text-uppercase fw-bold text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Description & Scope</p>
                    <p class="text-secondary small mb-0" id="view_desc"></p>
                </div>
            </div>
            <div class="modal-footer border-top-0 px-4 pb-4">
                <button type="button" class="btn btn-light fw-bold rounded-pill px-4 border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<style>
    .hover-scale-110:hover { transform: scale(1.08); }
    .transition-all { transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1); }
    .hover-shadow-bubble:hover { box-shadow: 0 15px 40px -10px rgba(0, 163, 154, 0.15) !important; transform: translateY(-3px); }
</style>
<script>
    // Live Search Functionality
    document.getElementById('testTypeSearch')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.test-type-card');
        let hasVisible = false;
        
        cards.forEach(card => {
            const searchData = card.getAttribute('data-search') || '';
            if (!query || searchData.includes(query)) {
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

    // Edit Modal Functionality
    let editModalInstance = null;
    function openEditModal(item) {
        document.getElementById('editTestTypeNameDisplay').textContent = item.name;
        document.getElementById('editTestTypeForm').action = '{{ url("test-report-types") }}/' + item.id;
        
        document.getElementById('edit_code').value = item.code || '';
        document.getElementById('edit_name').value = item.name || '';
        document.getElementById('edit_standard_citation').value = item.standard_citation || '';
        document.getElementById('edit_category').value = item.category || 'Flammability';
        document.getElementById('edit_validity_days').value = item.default_validity_days || 365;
        document.getElementById('edit_description').value = item.description || '';
        
        const modalEl = document.getElementById('editTestTypeModal');
        if (window.bootstrap && window.bootstrap.Modal) {
            const modal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        } else {
            modalEl.classList.add('show');
            modalEl.style.display = 'block';
        }
    }

    // View Modal Functionality
    function openViewModal(item) {
        document.getElementById('view_badge_code').textContent = item.code;
        document.getElementById('view_title').textContent = item.name;
        document.getElementById('view_citation').textContent = item.standard_citation || 'CPSC General';
        document.getElementById('view_category').textContent = item.category || 'General';
        
        let valText = item.default_validity_days + ' Days';
        if (item.default_validity_days == 365) valText += ' (1 Year)';
        if (item.default_validity_days == 730) valText += ' (2 Years)';
        document.getElementById('view_validity').textContent = valText;
        document.getElementById('view_desc').textContent = item.description || 'No detailed description provided.';

        const modalEl = document.getElementById('viewTestTypeModal');
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
