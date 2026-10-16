@extends('layouts.app')

@section('header', 'Profile')

@section('content')
<div class="mb-4">
    <h2 class="h4 fw-bolder text-dark mb-0">Profile</h2>
    <p class="text-secondary fw-semibold mt-1" style="font-size: 0.9rem;">Manage your account settings and preferences.</p>
</div>

<div class="row g-4">
    <div class="col-lg-8 col-xl-6">
        <!-- Update Profile Information -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-sm-5">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <!-- Update Password -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-sm-5">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <!-- Delete Account -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-sm-5">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</div>
@endsection
