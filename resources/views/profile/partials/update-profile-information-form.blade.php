<section>
    <header class="mb-4">
        <h2 class="h5 fw-bold text-dark mb-1">
            {{ __('Profile Information') }}
        </h2>
        <p class="text-secondary fw-semibold small mb-0">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" name="name" type="text" class="d-block mt-1 w-100" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-1" :messages="$errors->get('name')" />
        </div>

        <div class="mb-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="d-block mt-1 w-100" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-1" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="small text-dark fw-semibold mb-1">
                        {{ __('Your email address is unverified.') }}

                        <button form="send-verification" class="btn btn-link p-0 m-0 align-baseline text-decoration-none fw-bold" style="font-size: 0.85rem;">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="small fw-bold text-success mb-0">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary fw-bolder px-4 py-2 shadow-sm rounded-3 text-uppercase" style="letter-spacing: 0.05em;">
                {{ __('Save') }}
            </button>

            @if (session('status') === 'profile-updated')
                <p id="profile-updated-msg" class="text-success fw-bold small mb-0">
                    <i class="bi bi-check-circle-fill me-1"></i>{{ __('Saved.') }}
                </p>
                <script>
                    setTimeout(() => {
                        const msg = document.getElementById('profile-updated-msg');
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
