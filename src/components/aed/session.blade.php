@props(['status' => ''])
@if ($status !='')
    <div {{ $attributes->merge(['class' => 'app-status border-in-'.$status]) }}>
        {{ ($slot) ? $slot : $status }}
    </div>
@endif
