@props(['errors', 'duration' => 3000])

@if ($errors->any())
    <div {{ $attributes->merge(['class' => 'app-validation show bg-in-error border-in-error']) }}
        data-duration="{{ $duration }}" >
        @foreach ($errors->all() as $error)
            <p class="m-0-0"><small>{{ $error }}</small></p>
        @endforeach
    </div>
@endif
