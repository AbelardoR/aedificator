@props(['value'])

<label {{ $attributes->merge(['class' => 'app-input-label']) }}>
    {{ $value ?? $slot }}
</label>
