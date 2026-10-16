@extends('layouts.app')

@section('header', 'User Privileges')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h5 fw-bolder text-dark mb-0">User Privileges</h2>
        <p class="small text-muted fw-semibold mt-1">Assign Privileges to User Types</p>
    </div>
</div>

<!-- Assignment Form -->
<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-body p-4">
        <div class="d-flex align-items-center text-success fw-bold mb-4">
            <i class="bi bi-shield-check me-2 fs-5"></i> Assign Privileges to User Type
        </div>

        <form action="{{ route('permissions.assign') }}" method="POST">
            @csrf
            
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <label class="form-label text-danger text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">User Type *</label>
                    <select name="role_id" required class="form-select bg-light border-light fw-semibold text-dark shadow-sm">
                        <option value="">Select User Type</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }} class="text-capitalize">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label class="form-label text-danger text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Access Menu *</label>
                    <select name="menu" required class="form-select bg-light border-light fw-semibold text-dark shadow-sm">
                        <option value="">Select menus</option>
                        @foreach($menus as $key => $name)
                            <option value="{{ $key }}" {{ old('menu') == $key ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text mt-1" style="font-size: 10px;">Select a menu to assign permissions</div>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label text-danger text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">User Privileges *</label>
                <div class="d-flex flex-wrap gap-4 bg-light p-3 rounded-4 border">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="privileges[view]" value="1" id="privilegeView">
                        <label class="form-check-label fw-bold text-dark" for="privilegeView">
                            Access <span class="text-danger">*</span>
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="privileges[create]" value="1" id="privilegeAdd">
                        <label class="form-check-label fw-bold text-dark" for="privilegeAdd">
                            Add
                        </label>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="privileges[edit]" value="1" id="privilegeEdit">
                        <label class="form-check-label fw-bold text-dark" for="privilegeEdit">
                            Edit
                        </label>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="privileges[delete]" value="1" id="privilegeDelete">
                        <label class="form-check-label fw-bold text-dark" for="privilegeDelete">
                            Delete
                        </label>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                <button type="reset" class="btn btn-light fw-bold rounded-pill px-4 text-muted border">
                    Clear
                </button>
                <button type="submit" id="assign-privilege-btn" class="btn btn-primary fw-bold rounded-pill px-4 shadow-sm d-flex align-items-center">
                    <i class="bi bi-plus-lg me-1"></i> Add Privilege
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Datatable of Assigned Privileges -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">User Type</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Menu</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-center" style="font-size: 11px; letter-spacing: 1px;">Access</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-center" style="font-size: 11px; letter-spacing: 1px;">Add</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-center" style="font-size: 11px; letter-spacing: 1px;">Edit</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-center" style="font-size: 11px; letter-spacing: 1px;">Delete</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($assignedPrivileges as $privilege)
                    <tr>
                        <td class="py-3 px-4 fw-bold text-dark text-capitalize small">
                            {{ $privilege['role_name'] }}
                        </td>
                        <td class="py-3 px-4 fw-semibold text-secondary small">
                            {{ $privilege['menu_name'] }}
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($privilege['access'])
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Yes</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($privilege['add'])
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Yes</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($privilege['edit'])
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Yes</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            @if($privilege['delete'])
                                <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-2">Yes</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <button type="button" 
                                        class="btn btn-sm btn-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"
                                        data-bs-toggle="modal" data-bs-target="#viewModal"
                                        data-role-name="{{ $privilege['role_name'] }}"
                                        data-menu-name="{{ $privilege['menu_name'] }}"
                                        data-access="{{ $privilege['access'] ? 'true' : 'false' }}"
                                        data-add="{{ $privilege['add'] ? 'true' : 'false' }}"
                                        data-edit="{{ $privilege['edit'] ? 'true' : 'false' }}"
                                        data-delete="{{ $privilege['delete'] ? 'true' : 'false' }}"
                                        title="View Privileges">
                                    <i class="bi bi-eye"></i>
                                </button>
                                
                                <button type="button" onclick="editPrivilege({{ json_encode($privilege) }})" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit Privileges">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                
                                <button type="button" 
                                        class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" 
                                        style="width: 32px; height: 32px;" 
                                        title="Delete Privileges"
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-action="{{ route('permissions.revoke') }}"
                                        data-method="POST"
                                        data-message="Are you sure you want to revoke these privileges?"
                                        data-submessage="This will completely revoke all assigned privileges for this menu."
                                        data-inputs='{"role_id":"{{ $privilege['role_id'] }}", "menu":"{{ $privilege['menu_key'] }}"}'>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-5 text-center text-muted fw-semibold small">
                            No privileges assigned yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- View Modal -->
<div class="modal fade" id="viewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-light border-bottom-0 pb-0">
                <h5 class="modal-title fw-bolder d-flex align-items-center">
                    <i class="bi bi-shield-check text-success me-2"></i>
                    Privilege Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-1" style="font-size: 10px; letter-spacing: 1px;">User Type</label>
                    <div class="fw-bold text-dark text-capitalize" id="modal-role-name"></div>
                </div>
                
                <div class="mb-4">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-1" style="font-size: 10px; letter-spacing: 1px;">Access Menu</label>
                    <div class="fw-bold text-dark" id="modal-menu-name"></div>
                </div>
                
                <div class="bg-light rounded-3 p-3 border">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-3" style="font-size: 10px; letter-spacing: 1px;">Granted Privileges</label>
                    
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-secondary">Access / View</span>
                        <span id="modal-access-badge"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-secondary">Add / Create</span>
                        <span id="modal-add-badge"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="small fw-bold text-secondary">Edit / Update</span>
                        <span id="modal-edit-badge"></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="small fw-bold text-secondary">Delete / Remove</span>
                        <span id="modal-delete-badge"></span>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light border-top-0 pt-0">
                <button type="button" class="btn btn-white fw-bold rounded-pill px-4 border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Handle modal data population
    document.getElementById('viewModal').addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        
        document.getElementById('modal-role-name').textContent = button.getAttribute('data-role-name');
        document.getElementById('modal-menu-name').textContent = button.getAttribute('data-menu-name');
        
        function updateBadge(id, isGranted) {
            var el = document.getElementById(id);
            if (isGranted === 'true') {
                el.innerHTML = '<span class="text-success fw-bold small"><i class="bi bi-check-circle-fill me-1"></i> Yes</span>';
            } else {
                el.innerHTML = '<span class="text-danger fw-bold small"><i class="bi bi-x-circle-fill me-1"></i> No</span>';
            }
        }
        
        updateBadge('modal-access-badge', button.getAttribute('data-access'));
        updateBadge('modal-add-badge', button.getAttribute('data-add'));
        updateBadge('modal-edit-badge', button.getAttribute('data-edit'));
        updateBadge('modal-delete-badge', button.getAttribute('data-delete'));
    });

    function editPrivilege(privilege) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        document.querySelector('select[name="role_id"]').value = privilege.role_id;
        document.querySelector('select[name="menu"]').value = privilege.menu_key;
        
        document.querySelector('input[name="privileges[view]"]').checked = privilege.access;
        document.querySelector('input[name="privileges[create]"]').checked = privilege.add;
        document.querySelector('input[name="privileges[edit]"]').checked = privilege.edit;
        document.querySelector('input[name="privileges[delete]"]').checked = privilege.delete;
        
        const submitBtn = document.getElementById('assign-privilege-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i> Update Privilege';
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-success');
        }
    }
    
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        const submitBtn = document.getElementById('assign-privilege-btn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="bi bi-plus-lg me-1"></i> Add Privilege';
            submitBtn.classList.remove('btn-success');
            submitBtn.classList.add('btn-primary');
        }
    });
</script>
@endpush
@endsection
