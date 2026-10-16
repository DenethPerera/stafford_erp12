<x-guest-layout>
    <div class="mb-4 pb-2">
        <h2 class="fw-bolder text-dark mb-1" style="font-size: 1.5rem; letter-spacing: -0.03em;">Reset Password</h2>
        <p class="text-secondary fw-semibold mb-0" style="font-size: 0.92rem;">Enter your new password below.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" novalidate>
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="d-block mt-1 w-100" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="d-block mt-1 w-100" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="d-block mt-1 w-100" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center justify-content-end mt-4 mb-4">
            <button type="submit" class="btn btn-primary fw-bolder text-uppercase rounded-3 shadow px-4 py-2" style="letter-spacing: 0.08em; background-color: #004ad7; border: none;">
                {{ __('Reset Password') }}
            </button>
        </div>
    </form>
</x-guest-layout>
