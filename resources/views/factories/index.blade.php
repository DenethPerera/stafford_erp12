@extends('layouts.app')

@section('header', 'Factories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Registered Factories</h2>
    <div class="d-flex align-items-center gap-3">
        <div class="position-relative">
            <input type="text" id="factorySearch" class="form-control rounded-pill bg-white shadow-sm border-0 pe-5" placeholder="Search factories..." style="width: 250px;">
            <i class="bi bi-search position-absolute text-muted" style="right: 15px; top: 50%; transform: translateY(-50%);"></i>
        </div>
        <button type="button" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4" data-bs-toggle="modal" data-bs-target="#addFactoryModal">
            <i class="bi bi-plus-lg me-2"></i> Add Factory
        </button>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="factoriesTable">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Factory Details</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($factories as $factory)
                    <tr class="factory-row" data-search="{{ strtolower($factory->name . ' ' . $factory->location . ' ' . $factory->contact_person) }}">
                        <td class="py-3 px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary bg-opacity-10 text-primary rounded-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                                    <i class="bi bi-building fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-1">{{ $factory->name }}</h6>
                                    <div class="small text-muted fw-semibold d-flex align-items-center">
                                        <i class="bi bi-geo-alt-fill me-1"></i> {{ $factory->location }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            @if($factory->status == 'active')
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 rounded-pill px-3 py-2 text-uppercase fw-bolder" style="font-size: 10px;">
                                    Active
                                </span>
                            @else
                                <span class="badge bg-light text-secondary border rounded-pill px-3 py-2 text-uppercase fw-bolder" style="font-size: 10px;">
                                    Inactive
                                </span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('factories.show', $factory) }}" class="btn btn-sm btn-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="View">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <button type="button" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Edit" 
                                    onclick='openEditModal({!! json_encode([
                                        "id" => $factory->id,
                                        "name" => $factory->name,
                                        "registration_no" => $factory->registration_no,
                                        "location" => $factory->location,
                                        "contact_person" => $factory->contact_person,
                                        "contact_phone" => $factory->contact_phone,
                                        "contact_email" => $factory->contact_email,
                                        "status" => $factory->status
                                    ]) !!})'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Delete"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#confirmModal" 
                                    data-action="{{ route('factories.destroy', $factory) }}"
                                    data-message="Are you sure you want to delete &quot;{{ addslashes($factory->name) }}&quot;?"
                                    data-submessage="All associated certificates will also be removed.">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="text-center py-5 text-muted fw-semibold">
                            No factories found.
                        </td>
                    </tr>
                @endforelse
                <tr id="noResultsRow" style="display: none;">
                    <td colspan="3" class="text-center py-5 text-muted fw-semibold">
                        No factories match your search.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Factory Modal -->
<div class="modal fade {{ $errors->any() && !old('_method') ? 'show' : '' }}" id="addFactoryModal" tabindex="-1" aria-labelledby="addFactoryModalLabel" aria-hidden="true" {!! $errors->any() && !old('_method') ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-building fs-5"></i>
                    </div>
                    <h5 class="modal-title fw-bold text-dark" id="addFactoryModalLabel">Add Factory</h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="{{ route('factories.store') }}" method="POST">
                @csrf
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Factory Name</label>
                            <input type="text" name="name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Registration No.</label>
                            <input type="text" name="registration_no" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('registration_no') }}">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Location / Address</label>
                        <input type="text" name="location" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('location') }}">
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Primary Contact</label>
                            <input type="text" name="contact_person" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('contact_person') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('contact_phone') }}">
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Email</label>
                            <input type="email" name="contact_email" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" value="{{ old('contact_email') }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</label>
                            <select name="status" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2">
                                <option value="active" {{ old('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Save Factory</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Factory Modal -->
<div class="modal fade {{ $errors->any() && old('_method') == 'PUT' ? 'show' : '' }}" id="editFactoryModal" tabindex="-1" aria-labelledby="editFactoryModalLabel" aria-hidden="true" {!! $errors->any() && old('_method') == 'PUT' ? 'style="display: block;"' : '' !!}>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-bottom-0 pb-0 px-4 pt-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="bi bi-pencil fs-5"></i>
                    </div>
                    <div>
                        <h5 class="modal-title fw-bold text-dark mb-0" id="editFactoryModalLabel">Edit Factory</h5>
                        <p class="small text-muted fw-semibold mb-0" id="editFactoryNameDisplay"></p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form id="editFactoryForm" action="" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body px-4 py-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Factory Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Registration No.</label>
                            <input type="text" name="registration_no" id="edit_registration_no" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Location / Address</label>
                        <input type="text" name="location" id="edit_location" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                    </div>
                    
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Primary Contact</label>
                            <input type="text" name="contact_person" id="edit_contact_person" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Phone</label>
                            <input type="text" name="contact_phone" id="edit_contact_phone" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                        </div>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Contact Email</label>
                            <input type="email" name="contact_email" id="edit_contact_email" class="form-control bg-light border-light fw-semibold text-dark px-3 py-2">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Status</label>
                            <select name="status" id="edit_status" class="form-select bg-light border-light fw-semibold text-dark px-3 py-2">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 px-4 pb-4">
                    <button type="button" class="btn btn-light fw-bold rounded-pill border px-4 py-2" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary fw-bold rounded-pill px-4 py-2 shadow-sm">Update Factory</button>
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
    document.getElementById('factorySearch')?.addEventListener('input', function(e) {
        const query = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('.factory-row');
        let hasVisible = false;
        
        rows.forEach(row => {
            const searchData = row.getAttribute('data-search');
            if (searchData.includes(query)) {
                row.style.display = '';
                hasVisible = true;
            } else {
                row.style.display = 'none';
            }
        });
        
        const noResultsRow = document.getElementById('noResultsRow');
        if (noResultsRow) {
            noResultsRow.style.display = hasVisible ? 'none' : '';
        }
    });

    // Edit Modal functionality
    let editModalInstance = null;
    function openEditModal(factory) {
        document.getElementById('editFactoryNameDisplay').textContent = factory.name;
        document.getElementById('editFactoryForm').action = '/factories/' + factory.id;
        
        document.getElementById('edit_name').value = factory.name || '';
        document.getElementById('edit_registration_no').value = factory.registration_no || '';
        document.getElementById('edit_location').value = factory.location || '';
        document.getElementById('edit_contact_person').value = factory.contact_person || '';
        document.getElementById('edit_contact_phone').value = factory.contact_phone || '';
        document.getElementById('edit_contact_email').value = factory.contact_email || '';
        document.getElementById('edit_status').value = factory.status || 'active';
        
        if (!editModalInstance) {
            editModalInstance = new bootstrap.Modal(document.getElementById('editFactoryModal'));
        }
        editModalInstance.show();
    }
</script>
@endsection
