@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-danger small mt-1 list-unstyled']) }}>
        @foreach ((array) $messages as $message)
            <li><i class="bi bi-exclamation-circle me-1"></i>{{ $message }}</li>
        @endforeach
    </ul>
@endif
