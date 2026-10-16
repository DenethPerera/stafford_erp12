<x-guest-layout>

<style>
/* 
   We removed most custom CSS in favor of Bootstrap 5 utilities.
   These few remaining styles handle specific design elements that don't have 1:1 Bootstrap classes.
*/
.lf-input-wrap { position: relative; }
.lf-icon {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #64748b;
    width: 20px; height: 20px;
    transition: color 0.2s;
}
.form-control.lf-input {
    padding-left: 2.8rem;
    height: 52px;
}
.form-control.lf-input:focus ~ .lf-icon,
.lf-input-wrap:focus-within .lf-icon { color: #0d6efd; }

.lf-eye {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    padding: 0;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    transition: color 0.2s;
}
.lf-eye:hover { color: #0d6efd; }
.lf-eye svg  { width: 20px; height: 20px; }

.lf-btn {
    position: relative;
    overflow: hidden;
}
.lf-btn::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.12) 50%, transparent 100%);
    transform: translateX(-100%);
    transition: transform 0.5s ease;
}
.lf-btn:hover::before { transform: translateX(100%); }
</style>

{{-- Session status message --}}
@if (session('status'))
    <div class="alert alert-success d-flex align-items-center fw-semibold rounded-3 mb-3" role="alert">
        <i class="bi bi-check-circle-fill me-2 fs-5"></i>
        {{ session('status') }}
    </div>
@endif

<div class="mb-3">
    <h2 class="fw-bolder text-dark mb-1" style="font-size: 1.75rem; letter-spacing: -0.03em;">Sign in to your account</h2>
    <p class="text-secondary fw-semibold mb-0" style="font-size: 0.85rem;">Please enter your credentials to access the system.</p>
</div>

<form method="POST" action="{{ route('login') }}" novalidate>
    @csrf

    {{-- ── Username / Email ─────────────────────────── --}}
    <div class="mb-3">
        <label class="form-label fw-bold text-dark mb-1" for="email" style="font-size: 0.85rem;">Username or Email</label>
        <div class="lf-input-wrap">
            <svg class="lf-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/>
            </svg>
            <input
                id="email"
                class="form-control lf-input rounded-3 shadow-sm @error('email') is-invalid @enderror"
                type="email"
                name="email"
                value="{{ old('email') }}"
                placeholder="Enter your username or email"
                required
                autofocus
                autocomplete="username"
            >
        </div>
        @error('email')
            <div class="text-danger fw-semibold mt-1" style="font-size: 0.8rem;">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $message }}
            </div>
        @enderror
    </div>

    {{-- ── Password ──────────────────────────────────── --}}
    <div class="mb-3">
        <label class="form-label fw-bold text-dark mb-1" for="password" style="font-size: 0.85rem;">Password</label>
        <div class="lf-input-wrap">
            <svg class="lf-icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/>
            </svg>
            <input
                id="password"
                class="form-control lf-input rounded-3 shadow-sm pe-5 @error('password') is-invalid @enderror"
                type="password"
                name="password"
                placeholder="Enter your password"
                required
                autocomplete="current-password"
            >
            <button type="button" class="lf-eye" id="pw-toggle" aria-label="Show/hide password" onclick="togglePw()">
                <i class="bi bi-eye fs-5" id="eye-show"></i>
                <i class="bi bi-eye-slash fs-5" id="eye-hide" style="display:none"></i>
            </button>
        </div>
        @error('password')
            <div class="text-danger fw-semibold mt-1" style="font-size: 0.8rem;">
                <i class="bi bi-exclamation-circle-fill me-1"></i> {{ $message }}
            </div>
        @enderror
    </div>

    {{-- ── Remember me + Forgot password ──────────── --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
            <label class="form-check-label fw-bold text-dark user-select-none" for="remember_me" style="font-size: 0.85rem;">
                Remember Me
            </label>
        </div>
        @if (Route::has('password.request'))
            <a class="fw-bold text-primary text-decoration-none" href="{{ route('password.request') }}" style="font-size: 0.85rem;">Forgot Password?</a>
        @endif
    </div>

    {{-- ── SIGN IN button ──────────────────────────── --}}
    <button id="sign-in-btn" type="submit" class="btn btn-primary w-100 fw-bolder text-uppercase rounded-3 shadow mb-3 d-flex align-items-center justify-content-center lf-btn" style="height: 50px; letter-spacing: 0.08em; background-color: #004ad7; border: none;">
        <i class="bi bi-lock-fill me-2 fs-5"></i>
        <span>SIGN IN</span>
        <i class="bi bi-arrow-right position-absolute" style="right: 1.2rem; font-size: 1.2rem;"></i>
    </button>

    {{-- ── Trust footer: 3 cards ────────────────────── --}}
    <div class="row g-2 mb-3">
        <div class="col-4">
            <div class="card h-100 border-light-subtle rounded-3 shadow-sm text-center p-2">
                <div class="mx-auto bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <div class="fw-bold text-dark lh-1 mb-1" style="font-size: 0.7rem;">Secure</div>
                <div class="text-muted fw-semibold" style="font-size: 0.6rem;">256-bit SSL</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card h-100 border-light-subtle rounded-3 shadow-sm text-center p-2">
                <div class="mx-auto bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                    <i class="bi bi-hdd-network-fill"></i>
                </div>
                <div class="fw-bold text-dark lh-1 mb-1" style="font-size: 0.7rem;">Server</div>
                <div class="text-success fw-bold" style="font-size: 0.6rem;">● Online</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card h-100 border-light-subtle rounded-3 shadow-sm text-center p-2">
                <div class="mx-auto bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center mb-1" style="width: 32px; height: 32px;">
                    <i class="bi bi-clock-fill"></i>
                </div>
                <div class="fw-bold text-dark lh-1 mb-1" style="font-size: 0.7rem;">Time</div>
                <div class="text-muted fw-semibold lh-sm" style="font-size: 0.6rem;">{{ now()->format('M d, Y') }}<br>{{ now()->format('h:i A') }}</div>
            </div>
        </div>
    </div>

    {{-- Version badge --}}
    <div class="badge bg-white text-primary border border-primary-subtle rounded-pill px-3 py-2 mx-auto d-flex align-items-center shadow-sm w-auto">
        <i class="bi bi-tag-fill me-2"></i>
        ERP Version 3.0.0
    </div>

</form>

<script>
function togglePw() {
    const inp  = document.getElementById('password');
    const show = document.getElementById('eye-show');
    const hide = document.getElementById('eye-hide');
    if (inp.type === 'password') {
        inp.type = 'text';
        show.style.display = 'none';
        hide.style.display = 'block';
    } else {
        inp.type = 'password';
        show.style.display = 'block';
        hide.style.display = 'none';
    }
}
</script>

</x-guest-layout>