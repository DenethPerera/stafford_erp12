@extends('layouts.app')

@section('header', 'User Type Details')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h5 fw-bolder text-dark mb-0">User Type: <span class="text-capitalize text-primary">{{ $role->name }}</span></h2>
        <p class="small text-muted fw-semibold mt-1 mb-0">View user type details and privileges</p>
    </div>
    <a href="{{ route('roles.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="row g-4">
    <!-- Role Details -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="d-flex align-items-center text-success fw-bold mb-4">
                    <i class="bi bi-info-circle-fill me-2 fs-5"></i> User Type Info
                </div>
                
                <div class="mb-4">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-1" style="font-size: 10px; letter-spacing: 1px;">Name</label>
                    <div class="fw-bold text-dark fs-5 text-capitalize">{{ $role->name }}</div>
                </div>
                
                <div class="mb-4">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-2" style="font-size: 10px; letter-spacing: 1px;">Status</label>
                    @if($role->name === 'super_admin')
                        <span class="badge bg-warning bg-opacity-25 text-dark rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center">
                            <i class="bi bi-lock-fill me-1"></i> Protected System Role
                        </span>
                    @else
                        <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-2 fw-bold d-inline-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-1"></i> Active Custom Role
                        </span>
                    @endif
                </div>
                
                <div class="mb-4">
                    <label class="text-uppercase fw-bolder text-muted d-block mb-1" style="font-size: 10px; letter-spacing: 1px;">Assigned Users</label>
                    <div class="fw-bold text-dark">{{ $role->users()->count() }} Users</div>
                </div>
                
                @if($role->name !== 'super_admin')
                <div class="pt-4 border-top d-grid">
                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-light text-primary fw-bold rounded-3 d-flex align-items-center justify-content-center">
                        <i class="bi bi-pencil me-2"></i> Edit
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
    
    <!-- Privileges Summary -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div class="card-header bg-light border-bottom-0 p-4 d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center text-success fw-bold">
                    <i class="bi bi-shield-check me-2 fs-5"></i> Assigned Privileges
                </div>
                <a href="{{ route('permissions.grid') }}" class="small fw-bold text-primary text-decoration-none border-bottom border-primary pb-1">
                    Manage Privileges
                </a>
            </div>
            
            <div class="card-body p-4">
                @php
                    $menus = [
                        'users' => 'User Management',
                        'roles' => 'User Privileges & Roles',
                        'profile' => 'My Profile',
                        'lab_reports' => 'Lab Reports'
                    ];
                    $rolePermissions = $role->permissions->pluck('name')->toArray();
                    $hasAny = false;
                @endphp
                
                <div class="d-flex flex-column gap-3">
                    @foreach($menus as $menuKey => $menuName)
                        @php
                            $hasView = in_array("view {$menuKey}", $rolePermissions);
                            $hasCreate = in_array("create {$menuKey}", $rolePermissions);
                            $hasEdit = in_array("edit {$menuKey}", $rolePermissions);
                            $hasDelete = in_array("delete {$menuKey}", $rolePermissions);
                            
                            $isSuperAdmin = $role->name === 'super_admin';
                            
                            if ($isSuperAdmin) {
                                $hasView = $hasCreate = $hasEdit = $hasDelete = true;
                            }
                        @endphp
                        
                        @if($hasView || $hasCreate || $hasEdit || $hasDelete || $isSuperAdmin)
                            @php $hasAny = true; @endphp
                            <div class="bg-light rounded-4 p-3 border">
                                <h4 class="fw-bold text-secondary mb-3 small d-flex align-items-center">
                                    <i class="bi bi-folder2-open text-muted me-2 fs-5"></i>
                                    {{ $menuName }}
                                </h4>
                                
                                <div class="row g-2">
                                    <div class="col-6 col-sm-3">
                                        <div class="bg-white rounded-3 p-2 border text-center">
                                            <div class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Access</div>
                                            @if($hasView)
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-dash text-muted fs-5"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="bg-white rounded-3 p-2 border text-center">
                                            <div class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Add</div>
                                            @if($hasCreate)
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-dash text-muted fs-5"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="bg-white rounded-3 p-2 border text-center">
                                            <div class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Edit</div>
                                            @if($hasEdit)
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-dash text-muted fs-5"></i>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="col-6 col-sm-3">
                                        <div class="bg-white rounded-3 p-2 border text-center">
                                            <div class="text-uppercase fw-bolder text-muted mb-1" style="font-size: 10px; letter-spacing: 1px;">Delete</div>
                                            @if($hasDelete)
                                                <i class="bi bi-check-circle-fill text-success fs-5"></i>
                                            @else
                                                <i class="bi bi-dash text-muted fs-5"></i>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                    
                    @if(!$hasAny)
                        <div class="text-center py-5">
                            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px;">
                                <i class="bi bi-shield-slash fs-1 text-muted"></i>
                            </div>
                            <h5 class="fw-bold text-secondary mb-1">No Privileges Assigned</h5>
                            <p class="small text-muted fw-semibold">This user type cannot access any modules yet.</p>
                            
                            <a href="{{ route('permissions.grid') }}" class="btn btn-light text-success fw-bold rounded-pill mt-3 px-4 border">
                                <i class="bi bi-plus-lg me-1"></i> Assign Privileges Now
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
