@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'fw-semibold small text-success']) }}>
        {{ $status }}
    </div>
@endif
