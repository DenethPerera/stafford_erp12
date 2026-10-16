<x-guest-layout>
    <div class="mb-4 pb-2">
        <h2 class="fw-bolder text-dark mb-1" style="font-size: 1.5rem; letter-spacing: -0.03em;">Forgot Password</h2>
        <div class="text-secondary fw-semibold mb-0 mt-2" style="font-size: 0.92rem; line-height: 1.5;">
            {{ __('Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.') }}
        </div>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4 alert alert-success rounded-3 fw-semibold" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <!-- Email Address -->
        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="d-block mt-1 w-100" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center justify-content-end mt-4 mb-4">
            <button type="submit" class="btn btn-primary fw-bolder text-uppercase rounded-3 shadow px-4 py-2" style="letter-spacing: 0.08em; background-color: #004ad7; border: none;">
                {{ __('Email Password Reset Link') }}
            </button>
        </div>
    </form>
</x-guest-layout>
