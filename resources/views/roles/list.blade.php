@extends('layouts.app')

@section('header', 'User Types / Roles')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">User Types</h2>
    <a href="{{ route('roles.create') }}" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4">
        <i class="bi bi-plus-lg me-2"></i> Add User Type
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success d-flex align-items-center fw-semibold rounded-3 mb-4 border-0 bg-success bg-opacity-10 text-success" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger d-flex align-items-center fw-semibold rounded-3 mb-4 border-0 bg-danger bg-opacity-10 text-danger" role="alert">
        <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
        {{ session('error') }}
    </div>
@endif

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Role Name</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-center" style="font-size: 11px; letter-spacing: 1px;">Users Assigned</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($roles as $role)
                    <tr>
                        <td class="py-3 px-4">
                            <span class="fw-bold text-dark text-capitalize small">{{ $role->name }}</span>
                            @if($role->name === 'super_admin')
                                <span class="badge bg-warning bg-opacity-25 text-dark ms-2 rounded-pill fw-bold" style="font-size: 10px;">Super Admin</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <span class="badge bg-light text-secondary rounded-pill px-3 py-2 border fw-bold">
                                {{ $role->users_count ?? 0 }} Users
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('roles.show', $role) }}" class="btn btn-sm btn-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if($role->name !== 'super_admin')
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit User Type">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" 
                                            style="width: 32px; height: 32px;" 
                                            {{ $role->users_count > 0 ? 'disabled title="Cannot delete user type with assigned users"' : 'title="Delete User Type"' }}
                                            data-bs-toggle="modal" 
                                            data-bs-target="#confirmModal" 
                                            data-action="{{ route('roles.destroy', $role) }}"
                                            data-message="Are you sure you want to delete this User Type?"
                                            data-submessage="This cannot be undone.">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                @else
                                    <span class="btn btn-sm btn-light text-secondary rounded-circle d-flex align-items-center justify-content-center disabled opacity-50" style="width: 32px; height: 32px;" title="Protected System Role">
                                        <i class="bi bi-lock-fill"></i>
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
