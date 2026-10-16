<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn btn-primary fw-bold text-uppercase tracking-wider rounded-pill px-4 py-2']) }}>
    {{ $slot }}
</button>
