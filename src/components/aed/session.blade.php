@props(['status' => '', 'duration' => 3000])
@if ($status !='')
    <div {{ $attributes->merge(['class' => 'app-status show bg-in-'.$status.' border-in-'.$status]) }}
        data-duration="{{ $duration }}" >
        {{ ($slot) ? $slot : $status }}
    </div>
@endif
