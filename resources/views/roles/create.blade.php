@extends('layouts.app')

@section('header', 'Add User Type')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Add New User Type</h2>
    <a href="{{ route('roles.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center">
        <i class="bi bi-arrow-left me-1"></i> Back to User Types
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4" style="max-width: 600px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('roles.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Role Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('name') ? 'is-invalid' : '' }}">
                @error('name')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="pt-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Create User Type
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
