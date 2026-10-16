@props(['value'])

<label {{ $attributes->merge(['class' => 'form-label fw-bold text-dark']) }}>
    {{ $value ?? $slot }}
</label>
