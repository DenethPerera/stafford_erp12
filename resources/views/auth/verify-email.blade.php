<x-guest-layout>
    <div class="mb-4 pb-2">
        <h2 class="fw-bolder text-dark mb-1" style="font-size: 1.5rem; letter-spacing: -0.03em;">Verify Email Address</h2>
        <div class="text-secondary fw-semibold mb-0 mt-2" style="font-size: 0.92rem; line-height: 1.5;">
            {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
        </div>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 alert alert-success d-flex align-items-center fw-semibold rounded-3" role="alert" style="font-size: 0.9rem;">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i>
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="d-flex align-items-center justify-content-between mt-4 mb-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-primary fw-bolder text-uppercase rounded-3 shadow px-4 py-2" style="letter-spacing: 0.08em; background-color: #004ad7; border: none;">
                {{ __('Resend Verification Email') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-link text-secondary text-decoration-none fw-bold" style="font-size: 0.88rem;">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
