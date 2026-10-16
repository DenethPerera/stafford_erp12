<x-guest-layout>
    <div class="mb-4 pb-2">
        <h2 class="fw-bolder text-dark mb-1" style="font-size: 1.5rem; letter-spacing: -0.03em;">Confirm Password</h2>
        <div class="text-secondary fw-semibold mb-0 mt-2" style="font-size: 0.92rem; line-height: 1.5;">
            {{ __('This is a secure area of the application. Please confirm your password before continuing.') }}
        </div>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" novalidate>
        @csrf

        <!-- Password -->
        <div class="mb-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="d-block mt-1 w-100"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="d-flex justify-content-end mt-4 mb-4">
            <button type="submit" class="btn btn-primary fw-bolder text-uppercase rounded-3 shadow px-4 py-2" style="letter-spacing: 0.08em; background-color: #004ad7; border: none;">
                {{ __('Confirm') }}
            </button>
        </div>
    </form>
</x-guest-layout>
