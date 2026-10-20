@extends('layouts.app')

@section('header', 'Add User')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="h5 fw-bolder text-dark mb-0">Add New User</h2>
    <a href="{{ route('users.index') }}" class="btn btn-light fw-bold rounded-pill shadow-sm text-secondary d-flex align-items-center">
        <i class="bi bi-arrow-left me-1"></i> Back to Users
    </a>
</div>

<div class="card border-0 shadow-sm rounded-4 mb-4" style="max-width: 600px;">
    <div class="card-body p-4 p-sm-5">
        <form action="{{ route('users.store') }}" method="POST">
            @csrf

            <div class="mb-4">
                <label for="name" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Name</label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('name') ? 'is-invalid' : '' }}">
                @error('name')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="email" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Email Address</label>
                <input type="email" name="email" id="email" value="{{ old('email') }}" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('email') ? 'is-invalid' : '' }}">
                @error('email')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Password</label>
                <input type="password" name="password" id="password" required
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('password') ? 'is-invalid' : '' }}">
                @error('password')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4">
                <label for="password_confirmation" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Confirm Password</label>
                <input type="password" name="password_confirmation" id="password_confirmation"
                       class="form-control bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2">
            </div>

            <div class="mb-4">
                <label for="role" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Role</label>
                <select name="role" id="role" required
                        class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 text-capitalize {{ $errors->has('role') ? 'is-invalid' : '' }}">
                    <option value="">Select a role...</option>
                    @foreach($roles as $role)
                        <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>{{ $role }}</option>
                    @endforeach
                </select>
                @error('role')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4" id="lab_section" style="display: {{ old('role') == 'lab' ? 'block' : 'none' }};">
                <label for="lab_id" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Assign Lab</label>
                <select name="lab_id" id="lab_id"
                        class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('lab_id') ? 'is-invalid' : '' }}">
                    <option value="">Select a lab...</option>
                    @foreach($labs as $lab)
                        <option value="{{ $lab->id }}" {{ old('lab_id') == $lab->id ? 'selected' : '' }}>{{ $lab->name }}</option>
                    @endforeach
                </select>
                @error('lab_id')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="mb-4" id="factory_section" style="display: {{ old('role') == 'factory' ? 'block' : 'none' }};">
                <label for="mbranchlist_id" class="form-label text-secondary text-uppercase fw-bolder" style="font-size: 11px; letter-spacing: 1px;">Assign Factory</label>
                <select name="mbranchlist_id" id="mbranchlist_id"
                        class="form-select bg-light border-light fw-semibold text-dark shadow-sm px-4 py-2 {{ $errors->has('mbranchlist_id') ? 'is-invalid' : '' }}">
                    <option value="">Select a factory...</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" {{ old('mbranchlist_id') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->br_name }} {{ $branch->br_code ? "({$branch->br_code})" : '' }}
                        </option>
                    @endforeach
                </select>
                @error('mbranchlist_id')
                    <div class="invalid-feedback fw-semibold mt-1">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <div class="pt-2 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary fw-bold rounded-pill shadow-sm px-4 py-2">
                    Create User
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roleSelect = document.getElementById('role');
        const labSection = document.getElementById('lab_section');
        const factorySection = document.getElementById('factory_section');

        function toggleRoleSections() {
            const role = roleSelect.value;
            labSection.style.display = (role === 'lab') ? 'block' : 'none';
            factorySection.style.display = (role === 'factory') ? 'block' : 'none';
        }

        roleSelect.addEventListener('change', toggleRoleSections);
        toggleRoleSections();
    });
</script>
@endsection
