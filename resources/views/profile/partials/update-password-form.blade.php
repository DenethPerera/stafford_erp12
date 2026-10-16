<section>
    <header class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-1">
            {{ __('Update Password') }}
        </h2>
        <p class="text-secondary fw-semibold small mb-0">
            {{ __('Ensure your account is using a long, random password to stay secure.') }}
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <x-input-label for="update_password_current_password" :value="__('Current Password')" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="d-block mt-1 w-100" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-1" />
        </div>

        <div class="mb-3">
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <x-text-input id="update_password_password" name="password" type="password" class="d-block mt-1 w-100" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-1" />
        </div>

        <div class="mb-4">
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="d-block mt-1 w-100" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary fw-bolder px-4 py-2 shadow-sm rounded-3 text-uppercase" style="letter-spacing: 0.05em;">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'password-updated')
                <p id="password-updated-msg" class="text-success fw-bold small mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i>{{ __('Saved.') }}
                </p>
                <script>
                    setTimeout(() => {
                        const msg = document.getElementById('password-updated-msg');
                        if (msg) {
                            msg.style.transition = 'opacity 0.5s ease';
                            msg.style.opacity = '0';
                            setTimeout(() => msg.remove(), 500);
                        }
                    }, 2000);
                </script>
            @endif
        </div>
    </form>
</section>
