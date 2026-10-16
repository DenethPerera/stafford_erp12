@extends('layouts.app')

@section('header', 'Edit User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Edit User: <span class="text-primary">{{ $user->name }}</span></h2>
    <a href="{{ route('users.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center">
        <i class="bi bi-arrow-left me-1"></i> Back to Users
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4" style="max-width: 600px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="name" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('name') ? 'is-invalid' : '' }}">
                @error('name')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('email') ? 'is-invalid' : '' }}">
                @error('email')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4 p-4 rounded-4 border bg-warning bg-opacity-10 border-warning border-opacity-25">
                <h4 class="h6 fw-bolder text-warning-emphasis mb-2 d-flex align-items-center">
                    <i class="bi bi-lock-fill me-2"></i> Update Password (Optional)
                </h4>
                <p class="small fw-semibold text-warning-emphasis mb-4">Leave these fields blank if you do not want to change the user's password.</p>
                
                <div class="mb-3">
                    <label for="password" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">New Password</label>
                    <input type="password" name="password" id="password"
                           class="form-control border-warning border-opacity-25 fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('password') ? 'is-invalid' : '' }}">
                    @error('password')
                        <div class="invalid-feedback fw-semibold mt-1">
                            {{ $message }}
                        </div>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Confirm New Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation"
                           class="form-control border-warning border-opacity-25 fw-semibold text-dark shadow-sm px-4 py-2">
                </div>
            </div>

            <div class="mb-4">
                <label for="role" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Role</label>
                <select name="role" id="role" required
                        class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 text-capitalize {{ $errors->has('role') ? 'is-invalid' : '' }}">
                    <option value="">Select a role...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ (old('role') ?? $user->roles->first()?->name) == $role ? 'selected' : '' }}>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4" id="lab_section" style="display: {{ (old('role') ?? $user->roles->first()?->name) == 'lab' ? 'block' : 'none' }};">
                <label for="lab_id" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Assign Lab</label>
                <select name="lab_id" id="lab_id"
                        class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('lab_id') ? 'is-invalid' : '' }}">
                    <option value="">Select a lab...</option>
                    @foreach($labs as $lab)
                        <option value="{{ $lab->id }}" {{ (old('lab_id') ?? $user->lab_id) == $lab->id ? 'selected' : '' }}>{{ $lab->name }}</option>
                    @endforeach
                </select>
                @error('lab_id')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="pt-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const labSection = document.getElementById('lab_section');

        roleSelect.addEventListener('change', function () {
            if (this.value === 'lab') {
                labSection.style.display = 'block';
            } else {
                labSection.style.display = 'none';
            }
        });
    });
</script>
@endsection
