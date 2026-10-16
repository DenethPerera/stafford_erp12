@extends('layouts.app')

@section('header', 'User Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Users</h2>
    <a href="{{ route('users.create') }}" class="btn btn-primary fw-bold rounded-pill shadow-sm d-flex align-items-center px-4">
        <i class="bi bi-plus-lg me-2"></i> Add User
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
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Name</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Email</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Role</th>
                    <th class="py-3 px-4 text-muted text-uppercase fw-bolder text-end" style="font-size: 11px; letter-spacing: 1px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td class="py-3 px-4">
                            <span class="fw-bold text-dark small">{{ $user->name }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="text-secondary small fw-semibold">{{ $user->email }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <span class="badge bg-light text-secondary rounded-pill px-3 py-2 border fw-bold text-capitalize">
                                {{ $user->roles->first()?->name ?? 'None' }}
                            </span>
                        </td>
                        <td class="py-3 px-4">
                            <div class="d-flex justify-content-end gap-1">
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-light text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="View Profile">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;" title="Edit User">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm btn-light text-danger rounded-circle d-flex align-items-center justify-content-center" 
                                        style="width: 32px; height: 32px;" 
                                        {{ auth()->id() === $user->id ? 'disabled title="You cannot delete yourself"' : 'title="Delete User"' }}
                                        data-bs-toggle="modal" 
                                        data-bs-target="#confirmModal" 
                                        data-action="{{ route('users.destroy', $user) }}"
                                        data-message="Are you sure you want to delete this user?"
                                        data-submessage="This cannot be undone.">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
