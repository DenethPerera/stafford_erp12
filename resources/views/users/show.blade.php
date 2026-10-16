@extends('layouts.app')

@section('header', 'User Profile')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="h5 fw-bolder text-dark mb-0">User Profile: <span class="text-primary text-capitalize">{{ $user->name }}</span></h2>
        <p class="small text-muted fw-semibold mt-1 mb-0">View user account details</p>
    </div>
    <a href="{{ route('users.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center border">
        <i class="bi bi-arrow-left me-1"></i> Back to List
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4 mx-auto" style="max-width: 600px;">
    <div class="card-body p-4 p-sm-5">
        <div class="d-flex align-items-center justify-content-center mb-5">
            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bolder text-uppercase" style="width: 96px; height: 96px; font-size: 2rem;">
                {{ substr($user->name, 0, 2) }}
            </div>
        </div>
        
        <div class="d-flex flex-column gap-4">
            <div class="row border-bottom pb-3">
                <div class="col-sm-4 text-uppercase fw-bolder text-muted d-flex align-items-center" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-person-fill me-2 fs-5"></i> Full Name
                </div>
                <div class="col-sm-8 fw-bold text-dark fs-6 mt-1 mt-sm-0">
                    {{ $user->name }}
                </div>
            </div>
            
            <div class="row border-bottom pb-3">
                <div class="col-sm-4 text-uppercase fw-bolder text-muted d-flex align-items-center" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-envelope-fill me-2 fs-5"></i> Email Address
                </div>
                <div class="col-sm-8 fw-bold text-dark fs-6 mt-1 mt-sm-0">
                    {{ $user->email }}
                </div>
            </div>
            
            <div class="row border-bottom pb-3">
                <div class="col-sm-4 text-uppercase fw-bolder text-muted d-flex align-items-center" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-shield-fill-check me-2 fs-5"></i> User Type
                </div>
                <div class="col-sm-8 mt-1 mt-sm-0">
                    @php
                        $roleName = $user->roles->first()?->name ?? 'None';
                    @endphp
                    <span class="badge rounded-pill px-3 py-2 fw-bold text-capitalize
                        {{ $roleName === 'super_admin' ? 'bg-warning bg-opacity-25 text-dark' : 'bg-success bg-opacity-10 text-success' }}">
                        {{ $roleName }}
                    </span>
                </div>
            </div>
            
            <div class="row border-bottom pb-3">
                <div class="col-sm-4 text-uppercase fw-bolder text-muted d-flex align-items-center" style="font-size: 11px; letter-spacing: 1px;">
                    <i class="bi bi-calendar-check-fill me-2 fs-5"></i> Account Created
                </div>
                <div class="col-sm-8 fw-bold text-secondary mt-1 mt-sm-0">
                    {{ $user->created_at->format('F j, Y g:i A') }}
                </div>
            </div>
        </div>
        
        <div class="pt-4 mt-2 d-flex justify-content-end">
            <a href="{{ route('users.edit', $user) }}" class="btn btn-light text-primary fw-bold rounded-pill px-4 py-2 border d-flex align-items-center">
                <i class="bi bi-pencil me-2"></i> Edit User
            </a>
        </div>
    </div>
</div>
@endsection
