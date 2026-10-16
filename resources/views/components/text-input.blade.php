@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'form-control rounded-3 shadow-sm border-secondary-subtle']) !!}>
