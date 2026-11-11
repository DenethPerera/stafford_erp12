<x-guest-layout>
    <h2 class="fw-bold mb-2">Forwarder Login</h2>

    <p class="text-muted mb-4">
        Enter your forwarder email and password.
    </p>

    <form method="POST" action="{{ route('forwarder.login.store') }}">
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>

            <input
                id="email"
                type="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus
            >

            @error('email')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>

            <input
                id="password"
                type="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                autocomplete="current-password"
                required
            >

            @error('password')
                <div class="invalid-feedback">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <button type="submit" class="btn btn-primary w-100">
            Log In
        </button>
    </form>
</x-guest-layout>
