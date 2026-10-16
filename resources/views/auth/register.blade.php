<x-guest-layout>
    <div class="mb-4 pb-2">
        <h2 class="fw-bolder text-dark mb-1" style="font-size: 2rem; letter-spacing: -0.03em;">Create an account</h2>
        <p class="text-secondary fw-semibold mb-0" style="font-size: 0.92rem;">Please fill in the details to register.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" novalidate>
        @csrf

        <!-- Name -->
        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="d-block mt-1 w-100" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <!-- Email Address -->
        <div class="mb-3">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="d-block mt-1 w-100" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div class="mb-3">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="d-block mt-1 w-100"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <!-- Confirm Password -->
        <div class="mb-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="d-block mt-1 w-100"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center justify-content-between mt-4 mb-4">
            <a class="fw-bold text-primary text-decoration-none" style="font-size: 0.88rem;" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <button type="submit" class="btn btn-primary fw-bolder text-uppercase rounded-3 shadow d-flex align-items-center px-4 py-2" style="letter-spacing: 0.08em; background-color: #004ad7; border: none;">
                {{ __('Register') }}
            </button>
        </div>
    </form>
</x-guest-layout>
