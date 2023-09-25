@props(['errors'])

@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'app-validation border-in-error']) }}>
        @foreach ($errors->all() as $error)
            <p class="m-0-0"><small>{{ $error }}</small></p>
        @endforeach
    </div>
@endif
