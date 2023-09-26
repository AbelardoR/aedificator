@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'app-input-error border-in-error']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
