<button {{ $attributes->merge(['type' => 'button', 'class' => 'btn btn-outline-secondary fw-bold text-uppercase tracking-wider rounded-pill px-4 py-2']) }}>
    {{ $slot }}
</button>
